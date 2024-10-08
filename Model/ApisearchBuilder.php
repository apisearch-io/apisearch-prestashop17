<?php

namespace Apisearch\Model;

use Apisearch\Context;
use Apisearch\Rates\Rate;
use Apisearch\Rates\Rating;

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
     * @param $productsId
     * @param $version
     * @param Context $context
     * @param callable $flushCallable
     * @return void
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function buildChunkItems(
        $productsId,
        $version,
        Context $context,
        Callable $flushCallable
    )
    {
        $products = ApisearchProduct::getFullProductsById($productsId, $context);
        $rates = Rating::getRatings($context, $productsId);
        foreach ($products as $key => $product) {
            if (array_key_exists($product['id_product'], $rates)) {
                $products[$key]['rate'] = $rates[$product['id_product']];
            }
        }

        $items = array_filter(array_map(function($product) use ($version, $context) {
            if ($context->printOnlyPSProducts()) {
                echo json_encode($product);
                echo PHP_EOL;
                ob_flush();
            } else {
                return $this->buildItemFromProduct($product, $version, $context);
            }
        }, $products));

        if ($context->isDebug()) {
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
     * @param $product
     * @param $version
     * @param Context $context
     * @return array|false
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function buildItemFromProduct($product, $version, Context $context)
    {
        $productId = $product['id_product'];
        $productAvailableForOrder = $product['available_for_order'];
        $outOfStock = $product['real_out_of_stock'] ?? 1;
        $langId = $context->getLanguageId();
        $isB2B = \Configuration::get('AS_B2B');
        $indexImagesPerColor = \Configuration::get('AS_INDEX_IMAGES_PER_COLOR');

        $references = array($product['reference']);
        $supplierReferences = $this->indexSupplierReferences ? $product['supplier_referencies'] : [];
        $eans = array($product['ean13']);
        $upcs = array($product['upc']);
        $mpns = array($product['mpn'] ?? null);
        $img = $product['id_image'];
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

                        if ($innerCategory->active == "0") {
                            continue;
                        }

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
        $minPrice = null;
        $maxPrice = null;
        $finalImagesByColor = array();

        $combinations = ApisearchProduct::getAttributeCombinations($productId, $langId);
        $hasCombinations = count($combinations) > 0;
        if ($hasCombinations) {

            $quantity = 0;
            $minPrice = 99999999999;
            $maxPrice = -1;
            $productAttributesId = array();

            foreach ($combinations as $combination) {
                $references[] = $combination['reference'] ?? null;
                $eans[] = $combination['ean13'] ?? null;
                $upcs[] = $combination['upc'] ?? null;
                $mpns[] = $combination['mpn'] ?? null;

                $combinationQuantity = \intval(($combination['quantity'] ?? 0));
                $quantity += $combinationQuantity;
                $combinationColor = ($combination['is_color_group'] === "1") && !empty($combination['attribute_color'])
                    ? $combination['attribute_color']
                    : null;

                if ($combinationColor) {
                    $colors[] = $combinationColor;
                    $productAttributesId[$combination['attribute_color']] = $combination['id_product_attribute'];
                }

                if (isset($combination['default_on'])) {
                    $idProductAttribute = $combination['id_product_attribute'];
                    if (empty($img)) {
                        $img = $combination['id_image'] ?? null;
                    }
                }

                if (
                    $this->indexProductNoStock ||
                    $combinationQuantity > 0
                ) {
                    if (!isset($attributes[$combination['group_name']]) || (isset($attributes[$combination['group_name']]) && !in_array($combination['attribute_name'], $attributes[$combination['group_name']]))) {
                        $attributes[$combination['group_name']][] = $combination['attribute_name'];
                    }
                }

                $combinationPriceGroup = $this->getProductPrices($context, $productId, $combination['id_product_attribute'], true);
                $combinationPrice = $combinationPriceGroup[0];

                if ($minPrice > $combinationPrice) {
                    $minPrice = $combinationPrice;
                }

                if ($maxPrice < $combinationPrice) {
                    $maxPrice = $combinationPrice;
                }
            }

            $minPrice = \round($minPrice, 2);
            $maxPrice = \round($maxPrice, 2);
            if ($minPrice == $maxPrice) {
                $minPrice = null;
                $maxPrice = null;
            }

            // Only if we have stock, we are going to check availability
            if ($quantity > 0) {
                foreach ($combinations as $combination) {
                    $available = $available || $this->getAvailability($productId, $productAvailableForOrder, $outOfStock, $combination['minimal_quantity'], $combination['id_product_attribute']);
                }
            }

            if ($indexImagesPerColor) {
                $combinationImages = ApisearchProduct::getImagesByProductAttributes(array_values($productAttributesId), $langId);
                foreach ($productAttributesId as $colorHex => $attributeId) {
                    $finalImagesByColor[ltrim($colorHex, '#')] = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME,
                        $combinationImages[$attributeId]
                        , 'home_default');
                }
            }

        } else {
            $available = $this->getAvailability($productId, $productAvailableForOrder, $outOfStock, $product['minimal_quantity']);
        }

        if (!$available && !$this->indexProductNoStock) {
            if ($context->isDebug()) {
                echo json_encode([
                    'uuid' => ['id' => $productId, 'type' => 'product'],
                    'error' => 'Product not available. Discarding.',
                    'data' => [
                        'available_for_order' => $product['available_for_order'],
                        'real_out_of_stock' => $product['real_out_of_stock'],
                        'minimal_quantity' => $product['minimal_quantity']
                    ]
                ]);
                echo PHP_EOL;
                ob_flush();
            }

            return false;
        }

        if (
            $this->avoidProductsWithoutImage &&
            empty($img) &&
            empty($combinationImg)
        ) {
            if ($context->isDebug()) {
                echo json_encode([
                    'uuid' => ['id' => $productId, 'type' => 'product'],
                    'error' => 'Product image not defined. Discarding.',
                ]);
                echo PHP_EOL;
                ob_flush();
            }

            return false;
        }

        $url = \Context::getContext()->link->getProductLink($productId, null, null, null, $langId);
        $image = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME, $img, 'home_default');

        $priceGroup = $this->getProductPrices($context, $productId, $idProductAttribute, true);
        $price = $priceGroup[0];
        $priceWithCurrency = $priceGroup[1];

        $oldPriceGroup = $this->getProductPrices($context, $productId, $idProductAttribute, false);
        $oldPrice = $oldPriceGroup[0];
        $oldPriceWithCurrency = $oldPriceGroup[1];

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
        $mpns = self::toArrayOfStrings($mpns);
        $references = self::toArrayOfStrings($references);
        $categoriesName = array_values(array_unique(array_filter($categoriesName)));
        $description = \Configuration::get('AS_INDEX_DESCRIPTIONS') ? \strip_tags(\strval($product['description_short'])) : null;

        if ($isB2B) {
            /**
             * Groups
             */
            $users = [];
            $keys = [];
            $groups = \Group::getGroups($context->getLanguageId(), $context->getShopId());
            foreach ($groups as $group) {
                $keys[$group['id_group']] = ['id_group' => $group['id_group'], 'id_customer' => null, 'with_tax' => $group['price_display_method']];
            }

            $prefix = _DB_PREFIX_;
            $sql = "
            SELECT sp.id_customer, sp.id_group, g.price_display_method
                FROM {$prefix}specific_price sp
                    INNER JOIN {$prefix}product_shop ps ON ps.id_product = sp.id_product AND ps.id_shop = {$context->getShopId()}
                    INNER JOIN {$prefix}customer c ON c.id_customer = sp.id_customer
                    INNER JOIN {$prefix}group g ON g.id_group = c.id_default_group
                    LEFT JOIN {$prefix}product_lang `pl` ON sp.id_product = pl.id_product AND pl.id_lang = $langId AND pl.id_shop = {$context->getShopId()}
                WHERE sp.id_product = {$productId} AND
                      sp.id_customer > 0
            ";

            $userPrices = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
            foreach ($userPrices as $userPrice) {
                $keys['cus_' . $userPrice['id_customer']] = ['id_group' => $userPrice['id_group'], 'id_customer' => $userPrice['id_customer'], 'with_tax' => $userPrice['price_display_method']];
            }

            foreach ($keys as $key => $groupData) {
                $groupPriceGroup = $this->getProductPrices($context, $productId, $idProductAttribute, true, $groupData['id_group'], $groupData['id_customer'], $groupData['with_tax'] == '0');
                $groupPrice = $groupPriceGroup[0];
                $groupPriceWithCurrency = $groupPriceGroup[1];

                $groupOldPriceGroup = $this->getProductPrices($context, $productId, $idProductAttribute, false, $groupData['id_group'], $groupData['id_customer'], $groupData['with_tax'] == '0');
                $groupOldPrice = $groupOldPriceGroup[0];
                $groupOldPriceWithCurrency = $groupOldPriceGroup[1];

                if (
                    $price == $groupPrice &&
                    $oldPrice == $groupOldPrice
                ) {
                    continue;
                }

                $users[$key] = [
                    'p' => $groupPrice,
                    'pc' => $groupPriceWithCurrency,
                    'op' => $groupOldPrice,
                    'opc' => $groupOldPriceWithCurrency,
                ];
            }
        }

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
                'old_price_with_currency' => $oldPriceWithCurrency,
                'price_with_currency' => $priceWithCurrency,
                'supplier_reference' => $supplierReferences,
                'show_price' => ($productAvailableForOrder || $product['show_price']), // Checks if the price must be shown
                'description' => $description,
                'images_by_color' => $finalImagesByColor,
                'stock' => \Product::getRealQuantity($productId)
            ),
            'indexed_metadata' => array_merge(array_filter(array(
                'as_version' => \intval($version),
                'price' => $price,
                'min_price' => $minPrice,
                'max_price' => $maxPrice,
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
                'mpn' => $mpns,
                'date_add' => \DateTime::createFromFormat('Y-m-d H:i:s', $product['date_add'])->format('U'),
            )), $frontFeaturesKeyFixed),
            'searchable_metadata' => array(
                'name' => \strval($product['name']),
                'categories' => $categoriesName,
                'features' => self::toArrayOfStrings($frontFeaturesValues),
                'description' => $description,
                'tags' => $product['tags'] ?? array()
            ),
            'suggest' => $categoriesName,
            'exact_matching_metadata' => array_values(array_filter(array_unique(array_merge(
                array($productId),
                $references, $eans, $upcs, $mpns,
                $supplierReferences ?? []
            ))))
        );

        if ($isB2B && !empty($users)) {
            $itemAsArray['indexed_metadata']['_users'] = $users;
        }

        if (
            array_key_exists('rate', $product) &&
            $product['rate'] instanceof Rate
        ) {
            $itemAsArray['metadata']['review_count'] = $product['rate']->getNb();
            $itemAsArray['indexed_metadata']['review_stars'] = $product['rate']->getRate();
        }

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

    /**
     * @param Context $context
     * @param $productId
     * @param $idProductAttribute
     * @param $reduction
     * @param $groupId
     * @param $userId
     * @return array
     */
    private function getProductPrices(Context $context, $productId, $idProductAttribute, $reduction, $groupId = null, $userId = null, $withTax = null)
    {
        if (!$groupId) {
            $groupId = (int) \Configuration::get('PS_UNIDENTIFIED_GROUP');
        }

        $resolvedWithTax = $context->isWithTax();
        if (is_bool($withTax)) {
            $resolvedWithTax = $withTax;
        }

        $specPrice = true;
        $price = \Product::priceCalculation(
            $context->getShopId(), $productId, $idProductAttribute,
            $context->getIdCountry(), $context->getIdState(), $context->getZipcode(),
            $context->getCurrency()->id, $groupId, 1,
            $resolvedWithTax, 6, false, $reduction, false, $specPrice, true, $userId
        );
        $price = \Tools::convertPrice($price, $context->getCurrency());
        $price = \round($price, 2);
        $priceWithCurrency = \Tools::displayPrice($price, $context->getCurrency());

        return [$price, $priceWithCurrency];
    }
}
