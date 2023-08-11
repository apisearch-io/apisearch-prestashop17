<?php

namespace Apisearch\Model;

use PrestaShop\PrestaShop\Core\Localization\Locale;

class ApisearchBuilder
{
    private $avoidProductsWithoutImage;
    private $indexProductPurchaseCount;
    private $indexProductNoStock;
    private $indexSupplierReferences;

    /**
     */
    public function __construct()
    {
        $this->avoidProductsWithoutImage = !\boolval(\Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE'));
        $this->indexProductPurchaseCount = \boolval(\Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT'));
        $this->indexProductNoStock = \boolval(\Configuration::get('AS_INDEX_PRODUCT_NO_STOCK'));
        $this->indexSupplierReferences = \boolval(\Configuration::get('AS_FIELDS_SUPPLIER_REFERENCES'));
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
     * @param          $productsId
     * @param          $langId
     * @param          $version
     * @param          $shopId
     * @param          $loadSales
     * @param          $loadSuppliers
     * @param callable $flushCallable
     * @return void
     */
    public function buildChunkItems(
        $productsId,
        $langId,
        $version,
        $shopId,
        $loadSales,
        $loadSuppliers,
        Callable $flushCallable,
        $debug = false
    )
    {
        $products = ApisearchProduct::getFullProductsById($productsId, $langId, $shopId, $loadSales, $loadSuppliers, $debug);

        $items = array_filter(array_map(function($product) use ($langId, $version) {
            return $this->buildItemFromProduct($product, $langId, $version);
        }, $products));

        if ($debug) {
            echo json_encode([
                'debug' => 'products transformed',
                'ids' => array_values(array_map(function(array $item) {
                    return $item['uuid']['id'];
                }, $items))
            ]);
            echo PHP_EOL;
            ob_flush();
        }

        $items = array_filter($items);

        if (!empty($items)) {
            $flushCallable($items);
        }
    }

    /**
     * @param int $productId
     * @param          $langId
     * @param string $version
     * @param          $loadSales
     * @param          $loadSuppliers
     * @param callable $flushCallable
     *
     * @return array
     *
     * @throw \Exception
     */
    public function buildItem(
        $productId,
        $langId,
        $version,
        $shopId,
        $loadSales,
        $loadSuppliers,
        Callable $flushCallable
    )
    {
        if (!isset($langId)) {
            $langId = \Context::getContext()->language->id;
        }

        $products = ApisearchProduct::getFullProductsById([$productId], $langId, $shopId, $loadSales, $loadSuppliers);
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
        $productId = $product['id_product'];
        $productAvailableForOrder = $product['available_for_order'];
        $outOfStock = $product['real_out_of_stock'] ?? 1;

        $references = array($product['reference']);
        $supplierReferences = $this->indexSupplierReferences ? $product['supplier_referencies'] : [];
        $eans = array($product['ean13']);
        $upcs = array($product['upc']);
        $img = $product['id_image'];
        $hasCombinations = \intval($product['cache_default_attribute'] ?? 0) > 0;
        $idProductAttribute = null;
        $categoriesName = array();
        $categoriesDepth0 = [];
        $categoriesDepth1 = [];
        $categoriesDepth2 = [];

        foreach ($product['categories_id'] as $categoryId) {
            if ($categoryId == \Configuration::get('PS_ROOT_CATEGORY') || $categoryId == \Configuration::get('PS_HOME_CATEGORY')) {
                continue;
            }

            $category = new \Category($categoryId, $langId);
            if (\Validate::isLoadedObject($category)) {
                $categories = $category->getParentsCategories($langId);
                foreach ($categories as $innerCategory) {
                    $innerCategory = new \Category($innerCategory['id_category'], $langId);
                    if (\Validate::isLoadedObject($innerCategory)) {

                        if ($innerCategory->id == \Configuration::get('PS_ROOT_CATEGORY') || $innerCategory->id == \Configuration::get('PS_HOME_CATEGORY')) {
                            continue;
                        }

                        $categoriesName[] = $innerCategory->name;
                        if (\strval($innerCategory->level_depth) == "2") $categoriesDepth0[] = $innerCategory->name;
                        elseif (\strval($innerCategory->level_depth) == "3") $categoriesDepth1[] = $innerCategory->name;
                        elseif (\strval($innerCategory->level_depth) == "4") $categoriesDepth2[] = $innerCategory->name;
                    }
                }
            }
        }

        $attributes = array();
        $colors = [];

        $combinationImg = null;
        $available = false;

        if ($hasCombinations) {

            $quantity = 0;
            $combinations = ApisearchProduct::getAttributeCombinations($productId, $langId);

            foreach ($combinations as $combination) {
                $references[] = $combination['reference'] ?? null;
                $eans[] = $combination['ean13'] ?? null;
                $upcs[] = $combination['upc'] ?? null;

                $quantity += \intval(($combination['quantity'] ?? 0));
                $colors[] = ($combination['is_color_group'] === "1") && !empty($combination['attribute_color'])
                    ? $combination['attribute_color']
                    : null;

                if (isset($combination['default_on'])) {
                    $idProductAttribute = $combination['id_product_attribute'];
                    if (empty($img)) {
                        $img = $combination['id_image'] ?? null;
                    }
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
            $available = $this->getAvailability($productId, $productAvailableForOrder, $outOfStock, $product['minimal_quantity']);
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

        $url = \Context::getContext()->link->getProductLink($productId, null, null, null, $langId);
        $image = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME, $img, 'home_default');
        $price = \Product::getPriceStatic($productId, true, $idProductAttribute);
        $price = \round($price, 2);
        $oldPrice = \Product::getPriceStatic($productId, true, $idProductAttribute, 6, null, false, false);
        $oldPrice = \round($oldPrice, 2);

        $frontFeatures = $product['front_features'] ?? null;
        $frontFeaturesKeyFixed = [];
        $frontFeaturesValues = [];
        if (is_array($frontFeatures)) {
            foreach ($frontFeatures as $key => $value) {
                $frontFeaturesKeyFixed[strtolower(str_replace([' '], ['_'], $key))] = $value;
                $frontFeaturesValues = array_merge($frontFeaturesValues, is_array($value) ? $value : [$value]);
            }
        }

        $eans = self::toArrayOfStrings($eans);
        $upcs = self::toArrayOfStrings($upcs);
        $references = self::toArrayOfStrings($references);
        $categoriesName = array_values(array_unique(array_filter($categoriesName)));
        $description = \Configuration::get('AS_INDEX_DESCRIPTIONS') ? \strip_tags(\strval($product['description_short'])) : null;

        $itemAsArray = array(
            'uuid' => array(
                'id' => \strval($productId),
                'type' => 'product'
            ),
            'metadata' => array(
                'title' => \strval($product['name']),
                'url' => $url,
                'image' => $image,
                'old_price' => $oldPrice,
                // 'old_price_with_currency' => $locale->formatPrice($price, $currency->iso_code),
                // 'price_with_currency' => $locale->formatPrice($oldPrice, $currency->iso_code) ,
                'supplier_reference' => $supplierReferences,
                'show_price' => ($productAvailableForOrder || $product['show_price']), // Checks if the price must be shown
            ),
            'indexed_metadata' => array_merge(array_filter(array(
                'as_version' => \intval($version),
                'price' => \round($price, 2),
                'categories' => $categoriesName,
                'category_level_0' => $categoriesDepth0,
                'category_level_1' => $categoriesDepth1,
                'category_level_2' => $categoriesDepth2,
                'available' => $available,
                'with_discount' => $oldPrice - $price > 0,
                'with_variants' => $hasCombinations,
                'reference' => $references,
                'ean' => $eans,
                'upc' => $upcs,
                'date_add' => \DateTime::createFromFormat('Y-m-d H:i:s', $product['date_add'])->format('U'),
            )), $frontFeaturesKeyFixed),
            'searchable_metadata' => array(
                'name' => \strval($product['name']),
                'categories' => $categoriesName,
                'features' => self::toArrayOfStrings($frontFeaturesValues),
                'description' => $description,
            ),
            'suggest' => $categoriesName,
            'exact_matching_metadata' => array_values(array_filter(array_unique(array_merge(
                array($productId),
                $references, $eans, $upcs,
                $supplierReferences ?? []
            ))))
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
            $attrValuesAsArray = is_array($attrValues) ? $attrValues : [$attrValues];
            $itemAsArray['indexed_metadata'][strtolower(str_replace([' '], ['_'], $attributeName))] = $attrValuesAsArray;
            $itemAsArray['searchable_metadata']['features'] = array_merge(
                $itemAsArray['searchable_metadata']['features'],
                $attrValuesAsArray
            );
        }

        if ($this->indexProductPurchaseCount) {
            // $itemAsArray['indexed_metadata']['quantity_sold'] = \intval($product['sales']); // Deprecated & Deleted. Use sales instead
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
     * @param int    $id
     * @param bool    $availableForOrder
     * @param bool    $outOfStock
     * @param int    $minQuantity
     * @param int $combinationId
     *
     * @return bool
     */
    private function getAvailability($id, $availableForOrder, $outOfStock, $minQuantity, $combinationId = 0)
    {
        if (!$availableForOrder) {
            return false;
        }

        //
        // This value can be
        // 0 -> Not allow selling 0 stock products
        // 1 -> Allow selling 0 stock products
        //
        $defaultOutOfStock = \Configuration::get('PS_ORDER_OUT_OF_STOCK');
        if (\Configuration::get('PS_STOCK_MANAGEMENT')) {
            if ($outOfStock == 2 && $defaultOutOfStock == 1) {
                return true;
            }

            if ($outOfStock == 1) {
                return true;
            }

            // At this point, if there is no stock, can't sell
            if (\Product::getRealQuantity($id, $combinationId) < $minQuantity) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array $array
     * @return array
     */
    private static function toArrayOfStrings(array $array) : array
    {
        return array_values(array_map('strval', array_unique(array_filter($array))));
    }
}
