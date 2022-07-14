<?php

namespace Apisearch\Model;

class ApisearchBuilder
{
    private $avoidProductsWithoutImage;
    private $indexProductPurchaseCount;
    private $indexProductNoStock;

    /**
     */
    public function __construct()
    {
        $this->avoidProductsWithoutImage = !\boolval(\Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE'));
        $this->indexProductPurchaseCount = \boolval(\Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT'));
        $this->indexProductNoStock = \boolval(\Configuration::get('AS_INDEX_PRODUCT_NO_STOCK'));
    }

    /**
     * @param int[] $productsId
     * @param          $langId
     * @param string $version
     * @param int $batch
     * @param callable $flushCallable
     *
     * @return array
     */
    public function buildItems($productsId, $langId, $version, $batch, $shopId, Callable $flushCallable)
    {
        if (!isset($langId)) {
            $langId = \Context::getContext()->language->id;
        }

        $chunks = array_chunk($productsId, $batch);
        array_walk($chunks, function($productsId) use ($langId, $version, $shopId, $flushCallable) {
            $this->buildChunkItems($productsId, $langId, $version, $shopId, $flushCallable);
        });
    }

    /**
     * @param int[] $productsId
     * @param          $langId
     * @param string $version
     * @param callable $flushCallable
     *
     * @return array
     */
    public function buildChunkItems($productsId, $langId, $version, $shopId, Callable $flushCallable)
    {
        $products = ApisearchProduct::getFullProductsById($productsId, $langId, $shopId);
        $items = array_filter(array_map(function($product) use ($langId, $version) {
            return $this->buildItemFromProduct($product, $langId, $version);
        }, $products));

        $items = array_filter($items);

        if (!empty($items)) {
            $flushCallable($items);
        }
    }

    /**
     * @param int $productId
     * @param          $langId
     * @param string $version
     * @param callable $flushCallable
     *
     * @return array
     *
     * @throw \Exception
     */
    public function buildItem($productId, $langId, $version, $shopId, Callable $flushCallable)
    {
        if (!isset($langId)) {
            $langId = \Context::getContext()->language->id;
        }

        $products = ApisearchProduct::getFullProductsById([$productId], $langId, $shopId);
        if (empty($products)) {
            throw new InvalidProductException();
        }

        $product = reset($products);
        $item = $this->buildItemFromProduct($product, $langId, $version);

        if (empty($item)) {
            throw new InvalidProductException();
        }

        $flushCallable($item);
    }

    /**
     * @param array $product
     * @param int $langId
     * @param string $version
     *
     * @return array|false
     */
    public function buildItemFromProduct($product, $langId, $version)
    {
        if (!$product['active'] || !in_array($product['visibility'], ['search', 'both'])) {
            return false;
        }

        $productId = $product['id_product'];
        $productAvailableForOrder = $product['available_for_order'];
        $outOfStock = $product['out_of_stock'] ?? false;

        $reference = $product['reference'];
        $ean13 = $product['ean13'];
        $upc = $product['upc'];
        $img = $product['id_image'];
        $hasCombinations = \intval($product['cache_default_attribute'] ?? 0) > 0;
        $idProductAttribute = null;

        $categoriesName = array();
        foreach ($product['categories_id'] as $categoryId) {
            if ($categoryId == \Configuration::get('PS_ROOT_CATEGORY') || $categoryId == \Configuration::get('PS_HOME_CATEGORY'))
                continue;

            $category = new \Category($categoryId, $langId);
            if (\Validate::isLoadedObject($category)) {
                $categoriesName[] = $category->name;
            }
        }

        $attributes = array();
        $colors = [];

        $combinations = ApisearchProduct::getAttributeCombinations($productId, $langId);
        $combinationImg = null;
        $available = false;

        if ($hasCombinations) {

            $quantity = 0;

        foreach ($combinations as $combination) {
            $quantity += \intval(($combination['quantity'] ?? 0));
            $colors[] = ($combination['is_color_group'] === "1") && !empty($combination['attribute_color'])
                ? $combination['attribute_color']
                : null;

            if (isset($combination['default_on'])) {
                $idProductAttribute = $combination['id_product_attribute'];
                $reference = empty($product['reference']) ? $combination['reference'] : $product['reference'];
                $ean13 = empty($product['ean13']) ? $combination['ean13'] : $product['ean13'];
                $upc = empty($product['upd']) ? $combination['upc'] : $product['upd'];
                $outOfStock = $combination['out_of_stock'] ?? false;
                $img = $combination['id_image'] ?? $img;
            }

            if (!isset($attributes[$combination['group_name']]) || (isset($attributes[$combination['group_name']]) && !in_array($combination['attribute_name'], $attributes[$combination['group_name']]))) {
                $attributes[$combination['group_name']][] = $combination['attribute_name'];
            }
        }

        // Only if we have stock, we are going to check availability
        if ($quantity > 0) {
            foreach ($combinations as $combination) {
                $minimalQuantity = $combination['minimal_quantity'];
                $idProductAttribute = $combination['id_product_attribute'];
                $available = $available || $this->getAvailability($productId, $productAvailableForOrder, $outOfStock, $minimalQuantity, $idProductAttribute);
            }
        }

        } else {
            $available = $this->getAvailability($productId, $productAvailableForOrder, $product['out_of_stock'], $product['minimal_quantity']);
        }

        if (!$available && !$this->indexProductNoStock) {
            return false;
        }

        if (
            $this->avoidProductsWithoutImage &&
            empty($img) &&
            empty($combinationImg)
        ) {
            return false;
        }

        $link = \Context::getContext()->link->getProductLink($productId, null, null, null, $langId);
        $image = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME, $img, 'home_default');
        $price = \Product::getPriceStatic($productId, true, $idProductAttribute, \Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
        $price = \round($price, 2);
        $oldPrice = \Product::getPriceStatic($productId, true, $idProductAttribute, \Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
        $oldPrice = \round($oldPrice, 2);

        $frontFeatures = $product['front_features'] ?? null;
        $frontFeaturesKeyFixed = [];
        if (is_array($frontFeatures)) {
            foreach ($frontFeatures as $key => $value) {
                $frontFeaturesKeyFixed[strtolower(str_replace([' '], ['_'], $key))] = $value;
            }
        }

        $itemAsArray = array(
            'uuid' => array(
                'id' => \strval($productId),
                'type' => 'product'
            ),
            'metadata' => array(
                'name' => \strval($product['name']),
                'show_price' => ($productAvailableForOrder || $product['show_price']), // Deprecated
                'link' => $link, // Deprecated
                'url' => $link,
                'img' => $image,
                'old_price' => $oldPrice,
            ),
            'indexed_metadata' => array_merge(array(
                'as_version' => \intval($version),
                'price' => \round($price, 2),
                'categories' => $categoriesName,
                'available' => $available,
                'with_discount' => $oldPrice - $price > 0,
                'with_variants' => $hasCombinations,
                'reference' => \strval($reference),
                'ean' => \strval($ean13),
                'upc' => \strval($upc),
            ), $frontFeaturesKeyFixed),
            'searchable_metadata' => array(
                'name' => \strval($product['name']),
                'categories' => $categoriesName,
            ),
            'suggest' => array_values(array_unique(array_filter(array_merge(array(
                // \strval($product['name']),
            ),
                $categoriesName
            )))),
            'exact_matching_metadata' => array(
                \strval($productId),
                \strval($reference),
                \strval($ean13),
                \strval($upc)
            )
        );

        $colors = array_filter($colors, function($value) {
            return is_string($value) && !empty($value);
        });

        if (!empty($colors)) {
            $colors = array_map('trim', $colors);
            $colors = array_map(function($color) {
                return ltrim($color, '#');
            }, $colors);
            $colors = array_unique($colors);
            $colors = array_values($colors);
            $itemAsArray['indexed_metadata']['color_hex'] = $colors;
        }

        #
        # Setting optional values
        #
        if (!empty($product['manufacturer'])) {
            $itemAsArray['indexed_metadata']['brand'] = \strval($product['manufacturer']['name']);
            $itemAsArray['searchable_metadata']['brand'] = \strval($product['manufacturer']['name']);
            $itemAsArray['suggest'][] = \strval($product['manufacturer']['name']);
        } elseif (!empty($product['supplier'])) {
            $itemAsArray['indexed_metadata']['brand'] = \strval($product['supplier']['name']);
            $itemAsArray['searchable_metadata']['brand'] = \strval($product['supplier']['name']);
            $itemAsArray['suggest'][] = \strval($product['supplier']['name']);
        }

        foreach ($attributes as $attributeName => $attrValues) {
            $itemAsArray['indexed_metadata'][strtolower(str_replace([' '], ['_'], $attributeName))] = (array)$attrValues;
        }

        if ($this->indexProductPurchaseCount) {
            $itemAsArray['indexed_metadata']['quantity_sold'] = \intval($product['sales']); // deprecated
            $itemAsArray['indexed_metadata']['sales'] = \intval($product['sales']);
        }

        #
        # Filtering empty values from search blocks
        #
        $itemAsArray['searchable_metadata'] = array_filter($itemAsArray['searchable_metadata'], function($data) {
            return !empty($data);
        });

        $itemAsArray['exact_matching_metadata'] = array_values(array_filter($itemAsArray['exact_matching_metadata'], function($data) {
            return !empty($data);
        }));

        return $itemAsArray;
    }

    /**
     * @param     $id
     * @param     $available_for_order
     * @param     $out_of_stock
     * @param     $minimal_quantity
     * @param int $combination_id
     *
     * @return bool
     */
    private function getAvailability($id, $available_for_order, $out_of_stock, $minimal_quantity, $combination_id = 0)
    {
        $available = false;
        if ($available_for_order) {
            if (\Configuration::get('PS_STOCK_MANAGEMENT')) {
                if ((\Configuration::get('PS_ORDER_OUT_OF_STOCK') && $out_of_stock == 2) || $out_of_stock == 1) {
                    $available = true;
                } else {
                    if (\Product::getRealQuantity($id, $combination_id) >= $minimal_quantity) {
                        $available = true;
                    }
                }
            } else {
                $available = true;
            }
        }

        return $available;
    }
}
