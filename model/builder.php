<?php

require_once __DIR__ . '/defaults.php';
require_once __DIR__ . '/as_product.php';

class Builder
{
    private $avoidProductsWithoutImage;
    private $indexProductPurchaseCount;

    /**
     */
    public function __construct()
    {
        $this->avoidProductsWithoutImage = !\boolval(Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE'));
        $this->indexProductPurchaseCount = \boolval(Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT'));
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
            $langId = Context::getContext()->language->id;
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
        $products = ASProduct::getFullProductsById($productsId, $langId, $shopId);
        $items = array_filter(array_map(function($product) use ($langId, $version) {
            return $this->buildItemFromProduct($product, $langId, $version);
        }, $products));

        if (!empty($items)) {
            $flushCallable($items);
        }
    }

    /**
     * @param array $product
     * @param int $langId
     * @param string $version
     *
     * @return array
     */
    public function buildItemFromProduct($product, $langId, $version)
    {
        $productId = $product['id_product'];
        $productAvailableForOrder = $product['available_for_order'];
        $productOutOfStock = $product['out_of_stock'];

        $product['available'] = $this->getAvailability($productId, $productAvailableForOrder, $productOutOfStock, $product['minimal_quantity']);
        $reference = $product['reference'];
        $ean13 = $product['ean13'];
        $upc = $product['upc'];
        $minimal_quantity = $product['minimal_quantity'];
        $img = $product['id_image'];
        $price = Product::getPriceStatic($productId, true, null, Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
        $old_price = Product::getPriceStatic($productId, true, null, Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);

        if (
            $this->avoidProductsWithoutImage &&
            empty($img)
        ) {
            return false;
        }

        $categoriesName = array();
        foreach ($product['categories_id'] as $categoryId) {
            if ($categoryId == Configuration::get('PS_ROOT_CATEGORY') || $categoryId == Configuration::get('PS_HOME_CATEGORY'))
                continue;

            $category = new Category($categoryId, $langId);
            if (Validate::isLoadedObject($category)) {
                $categoriesName[] = $category->name;
            }
        }

        $attributes = array();
        $features = array();
        if (in_array($product['visibility'], array('both', 'search'))) {
            $combinations = ASProduct::getAttributeCombinations($productId, $langId);
            $available = false;

            foreach ($combinations as $combination) {
                if (isset($combination['default_on'])) {
                    $id_product_attribute = $combination['id_product_attribute'];
                    $reference = empty($product['reference']) ? $combination['reference'] : $product['reference'];
                    $ean13 = empty($product['ean13']) ? $combination['ean13'] : $product['ean13'];
                    $upc = empty($product['upd']) ? $combination['upc'] : $product['upd'];
                    $minimal_quantity = $combination['minimal_quantity'];
                    $price = Product::getPriceStatic($productId, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
                    $old_price = Product::getPriceStatic($productId, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
                    $available = $this->getAvailability($productId, $productAvailableForOrder, $productOutOfStock, $combination['minimal_quantity'], $combination['id_product_attribute']);
                    $img = $combination['id_image'] ?? '';
                }
                if (!isset($attributes[$combination['group_name']]) || (isset($attributes[$combination['group_name']]) && !in_array($combination['attribute_name'], $attributes[$combination['group_name']]))) {
                    $attributes[$combination['group_name']][] = $combination['attribute_name'];
                }
            }

            if (!$available) {
                foreach ($combinations as $combination) {
                    if ($this->getAvailability($product['id_product'], $productAvailableForOrder, $productOutOfStock, $combination['minimal_quantity'], $combination['id_product_attribute'])) {
                        $id_product_attribute = $combination['id_product_attribute'];
                        $reference = empty($product['reference']) ? $combination['reference'] : $product['reference'];
                        $ean13 = empty($product['ean13']) ? $combination['ean13'] : $product['ean13'];
                        $upc = empty($product['upd']) ? $combination['upc'] : $product['upd'];
                        $minimal_quantity = $combination['minimal_quantity'];
                        $price = Product::getPriceStatic($productId, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
                        $old_price = Product::getPriceStatic($productId, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
                        $available = 1;
                        $img = $combination['id_image'] ?? '';

                        break;
                    }
                }
            }
        }

        $link = (string)Context::getContext()->link->getProductLink($productId);
        $image = (string)Context::getContext()->link->getImageLink($product['link_rewrite'] ?? Defaults::PLUGIN_NAME, $img, 'home_default');

        $itemAsArray = array(
            'uuid' => array(
                'id' => $productId,
                'type' => 'product'
            ),
            'metadata' => array(
                'id_product' => $productId,
                'id_product_attribute' => isset($id_product_attribute) ? (int)$id_product_attribute : 0,
                'name' => $product['name'],
                'description' => $product['description'],
                'description_short' => $product['description_short'],
                'reference' => (string)$reference,
                'ean' => (string)$ean13,
                'upc' => (string)$upc,
                'show_price' => ($productAvailableForOrder || $product['show_price']),
                'link' => $link,
                'img' => $image,
                'available' => (bool)$available,
                'with_discount' => ($old_price - $price > 0),
                'minimal_quantity' => (int)$minimal_quantity,
                'quantity_discount' => (int)($old_price - $price),
                'old_price' => round($old_price, 2),
            ),
            'indexed_metadata' => array(
                'as_version' => (int)$version,
                'price' => round($price, 2),
                'categories' => $categoriesName,
                'available' => $available,
                'with_discount' => ($old_price - $price > 0)
            ),
            'searchable_metadata' => array(
                'name' => $product['name'],
                'description' => $product['description'],
                'description_short' => $product['description_short'],
            ),
            'suggest' => array(
                'name' => $product['name'],
            ),
            'exact_matching_metadata' => array(
                \strval($productId),
                \strval($reference),
                \strval($ean13),
                \strval($upc)
            )
        );

        #
        # Setting optional values
        #
        if (!empty($product['manufacturer'])) {
            $itemAsArray['indexed_metadata']['brand'] = $product['manufacturer']['name'];
            $itemAsArray['searchable_metadata']['brand'] = $product['manufacturer']['name'];
        }
        
        if (!empty($product['supplier'])) {
            $itemAsArray['indexed_metadata']['brand'] = $product['supplier']['name'];
            $itemAsArray['searchable_metadata']['brand'] = $product['supplier']['name'];
        }

        foreach ($attributes as $attributeName => $attrValues) {
            $itemAsArray['indexed_metadata'][Tools::link_rewrite($attributeName)] = (array)$attrValues;
        }

        foreach ($features as $featureName => $featValues) {
            $itemAsArray['indexed_metadata'][Tools::link_rewrite($featureName)] = (array)$featValues;
        }

        if ($this->indexProductPurchaseCount) {
            $itemAsArray['indexed_metadata']['quantity_sold'] = $this->getSold($productId);
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
            if (Configuration::get('PS_STOCK_MANAGEMENT')) {
                if ((Configuration::get('PS_ORDER_OUT_OF_STOCK') && $out_of_stock == 2) || $out_of_stock == 1) {
                    $available = true;
                } else {
                    if (Product::getRealQuantity($id, $combination_id) >= $minimal_quantity) {
                        $available = true;
                    }
                }
            } else {
                $available = true;
            }
        }

        return $available;
    }

    /**
     * @param $productId
     *
     * @return mixed
     */
    public function getSold($productId)
    {
        return Db::getInstance()->getValue('
            SELECT COUNT(od.product_quantity - od.product_quantity_refunded - od.product_quantity_return - od.product_quantity_reinjected)
            FROM ' . _DB_PREFIX_ . 'order_detail od
            LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.id_order = od.id_order)
            LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON (os.id_order_state = o.current_state)
            WHERE od.product_id = ' . $productId . '
            AND o.valid = 1
            AND os.logable = 1
            AND os.paid = 1'
        );
    }
}
