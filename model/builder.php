<?php

require_once __DIR__ . '/defaults.php';

class Builder
{
    /**
     * @var Callable
     */
    private $translator;

    /**
     * @param Closure $translator
     */
    public function __construct(Closure $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param int[] $productsId
     * @param          $langId
     * @param string $version
     * @param int $bulkCount
     * @param callable $flushCallable
     *
     * @return array
     */
    public function buildItems($productsId, $langId, $version, $bulkCount, Callable $flushCallable)
    {
        if (!isset($langId)) {
            $langId = Context::getContext()->language->id;
        }

        $items = array();
        foreach ($productsId as $productId) {
            $item = new Product($productId, true, $langId);

            if (Shop::isFeatureActive()) {
                if (empty(Configuration::get('AS_SHOP')))
                    continue;

                $assoc = json_decode(Configuration::get('AS_SHOP'), 1);
                if (!isset($assoc['shop']) || $assoc['shop'] == false)
                    continue;

                $shops_product = array_column(Product::getShopsByProduct($productId), 'id_shop');
                if (!in_array(Context::getContext()->shop->id, $shops_product)) {
                    $shops_assoc = $assoc['shop'];
                    $shops = array_intersect($shops_product, $shops_assoc);
                    $item = new Product($productId, true, $langId, reset($shops));
                }
            }

            $available = $this->getAvailability($item->id, $item->available_for_order, $item->out_of_stock, $item->minimal_quantity);

            $reference = $item->reference;
            $ean13 = $item->ean13;
            $upc = $item->upc;
            $minimal_quantity = $item->minimal_quantity;
            $price = Product::getPriceStatic($item->id, true, null, Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
            $old_price = Product::getPriceStatic($item->id, true, null, Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
            $img = Product::getCover($item->id);

            if (
                !\boolval(Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE')) &&
                !$this->checkImgExists($img['id_image'])
            ) {
                continue;
            }

            $categories = array();
            foreach ($item->getCategories() as $category_id) {
                if ($category_id == Configuration::get('PS_ROOT_CATEGORY') || $category_id == Configuration::get('PS_HOME_CATEGORY'))
                    continue;

                $category = new Category($category_id, $langId);
                if (Validate::isLoadedObject($category)) {
                    $categories[] = $category->name;
                }
            }

            $attributes = array();
            $features = array();
            if ($item->visibility == 'both' || $item->visibility == 'search') {
                if ($item->hasAttributes()) {
                    $combinations = $item->getAttributeCombinations($langId);
                    foreach ($combinations as $combination) {
                        if ($combination['default_on']) {
                            $id_product_attribute = $combination['id_product_attribute'];
                            $reference = empty($item->reference) ? $combination['reference'] : $item->reference;
                            $ean13 = empty($item->ean13) ? $combination['ean13'] : $item->ean13;
                            $upc = empty($item->upc) ? $combination['upc'] : $item->upc;
                            $minimal_quantity = $combination['minimal_quantity'];
                            $price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
                            $old_price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
                            $available = $this->getAvailability($item->id, $item->available_for_order, $item->out_of_stock, $combination['minimal_quantity'], $combination['id_product_attribute']);

                            $combinations_images = $item->getCombinationImages($langId);
                            if (isset($combinations_images[$combination['id_product_attribute']])) {
                                $id_images = array_column($combinations_images[$combination['id_product_attribute']], 'id_image');
                                if (!empty($id_images) && !in_array($img['id_image'], $id_images)) {
                                    $img = array('id_image' => $id_images[0]);
                                }
                            }
                        }
                        if (!isset($attributes[$combination['group_name']]) || (isset($attributes[$combination['group_name']]) && !in_array($combination['attribute_name'], $attributes[$combination['group_name']])))
                            $attributes[$combination['group_name']][] = $combination['attribute_name'];
                    }
                    if (!$available) {
                        foreach ($combinations as $combination) {
                            if ($this->getAvailability($item->id, $item->available_for_order, $item->out_of_stock, $combination['minimal_quantity'], $combination['id_product_attribute'])) {
                                $id_product_attribute = $combination['id_product_attribute'];
                                $reference = empty($item->reference) ? $combination['reference'] : $item->reference;
                                $ean13 = empty($item->ean13) ? $combination['ean13'] : $item->ean13;
                                $upc = empty($item->upc) ? $combination['upc'] : $item->upc;
                                $minimal_quantity = $combination['minimal_quantity'];
                                $price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
                                $old_price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
                                $available = 1;

                                $combinations_images = $item->getCombinationImages($langId);
                                if (isset($combinations_images[$combination['id_product_attribute']])) {
                                    $id_images = array_column($combinations_images[$combination['id_product_attribute']], 'id_image');
                                    if (!empty($id_images) && !in_array($img['id_image'], $id_images)) {
                                        $img = array('id_image' => $id_images[0]);
                                    }
                                }

                                break;
                            }
                        }
                    }
                }

                $item_features = $item->getFrontFeatures($langId);
                if (!empty($item_features)) {
                    foreach ($item_features as $item_feature) {
                        if (!isset($features[$item_feature['name']]) || (isset($features[$item_feature['name']]) && !in_array($item_feature['value'], $features[$item_feature['name']])))
                            $features[$item_feature['name']][] = $item_feature['value'];
                    }
                }
            }

            $itemAsArray = array(
                'uuid' => array(
                    'id' => $item->id,
                    'type' => 'product'
                ),
                'metadata' => array(
                    'id_product' => (int)$item->id,
                    'id_product_attribute' => isset($id_product_attribute) ? (int)$id_product_attribute : 0,
                    'name' => (string)$item->name,
                    'description' => (string)$item->description,
                    'description_short' => (string)$item->description_short,
                    'brand' => (string)$item->manufacturer_name,
                    'reference' => (string)$reference,
                    'ean' => (string)$ean13,
                    'upc' => (string)$upc,
                    'price' => (string)Tools::displayPrice($price),
                    'old_price' => (string)Tools::displayPrice($old_price),
                    'show_price' => ($item->available_for_order || $item->show_price),
                    'link' => (string)Context::getContext()->link->getProductLink($item),
                    'img' => (string)Context::getContext()->link->getImageLink(isset($item->link_rewrite) ? $item->link_rewrite : Defaults::PLUGIN_NAME, $img['id_image'], 'home_default'),
                    'available' => (bool)$available,
                    'with_discount' => ($old_price - $price > 0),
                    'minimal_quantity' => (int)$minimal_quantity
                ),
                'indexed_metadata' => array(
                    'as-version' => (int)$version,
                    'price' => round($price, 2),
                    'categories' => (array)$categories,
                    'name' => (string)$item->name,
                    'available' => $available,
                    'with_discount' => ($old_price - $price > 0),
                    'quantity_discount' => (int)($old_price - $price),
                    'quantity_sold' => (int)$this->getSold($item->id)
                ),
                'searchable_metadata' => array(
                    'name' => (string)$item->name,
                    'description' => strip_tags($item->description),
                    'description_short' => strip_tags($item->description_short),
                    'brand' => (string)$item->manufacturer_name,
                ),
                'suggest' => array(
                    'name' => (string)$item->name,
                ),
                'exact_matching_metadata' => array((int)$item->id, (string)$reference, (string)$ean13, (string)$upc)
            );

            if (!empty($item->manufacturer_name)) {
                $itemAsArray['indexed_metadata']['brand'] = (string)$item->manufacturer_name;
            }
            if (!empty($item->supplier_name)) {
                $itemAsArray['indexed_metadata']['supplier'] = (string)$item->supplier_name;
            }
            foreach ($attributes as $attributeName => $attrValues) {
                $itemAsArray['indexed_metadata'][Tools::link_rewrite($attributeName)] = (array)$attrValues;
            }
            foreach ($features as $featureName => $featValues) {
                $itemAsArray['indexed_metadata'][Tools::link_rewrite($featureName)] = (array)$featValues;
            }

            $items[$item->id] = $itemAsArray;
            $numberOfItems = count($items);
            if ($numberOfItems >= $bulkCount) {
                $flushCallable($items);
                $items = [];
            }
        }

        if (count($items) > 0) {
            $flushCallable($items);
        }
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
     * @param $imageId
     *
     * @return bool
     */
    private function checkImgExists($imageId)
    {
        $image = new Image($imageId);

        return (
            Configuration::get('PS_LEGACY_IMAGES') && file_exists(_PS_PROD_IMG_DIR_ . $image->id_product . '-' . $image->id . '.' . $image->image_format) ||
            file_exists(_PS_PROD_IMG_DIR_ . $image->getImgPath() . '.' . $image->image_format)
        );
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

    /**
     * @param $text
     *
     * @return mixed
     */
    private function translate($text)
    {
        return ($this->translator)($text);
    }
}
