<?php

/**
 * Plugin Name: Apisearch
 * License: MIT
 * Copyright (c) 2020 - 2025 Apisearch SL
 *
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use
 * of this software, even if advised of the possibility of such damages.
 *
 * Permission is hereby granted, free of charge, to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to permit persons
 * to whom the Software is provided to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice must be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE, AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT,
 * OR OTHERWISE, ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace Apisearch\Model;

use Apisearch\Context;
use Apisearch\Model\Product\ProductPrices;
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
        $normalizedItems = array();
        foreach ($items as $item) {
            if (isset($item['uuid'])) {
                $normalizedItems[] = $item;
            } else {
                $item = array_filter($item);
                $normalizedItems = array_merge($normalizedItems, $item);
            }
        }

        if (!empty($normalizedItems)) {
            $flushCallable($normalizedItems);
        }
    }

    /**
     * @param $product
     * @param $version
     * @param Context $context
     * @param $colorToFilterBy
     * @return array|array[]|\array[][]|false|false[]|\false[][]
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function buildItemFromProduct($product, $version, Context $context, $colorToFilterBy = null)
    {
        $productId = $product['id_product'];
        $langId = $context->getLanguageId();
        $imageType = ApisearchImage::getCurrentImageType();

        /**
         * Let's check possible colors
         */
        if ($colorToFilterBy === null && \Configuration::get('AS_GROUP_BY_COLOR')) {
            $colors = ApisearchProduct::getProductAvailableColors($productId, $langId);
            $colors = array_filter($colors);
            if (count($colors) > 1) {
                return array_map(function($color) use ($product, $version, $context) {
                    return $this->buildItemFromProduct($product, $version, $context, $color);
                }, $colors);
            }

        }

        $productAvailableForOrder = $product['available_for_order'];
        $outOfStock = $product['real_out_of_stock'] ?? 1;
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
        $categoriesDepth0 = array();
        $categoriesDepth1 = array();
        $categoriesDepth2 = array();

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
        $colors = array();

        $combinationImg = null;
        $available = false;
        $minPrice = null;
        $maxPrice = null;
        $finalImagesByColor = array();

        $combinations = ApisearchProduct::getAttributeCombinations($productId, $langId, $colorToFilterBy);
        $hasCombinations = count($combinations) > 0;
        $productAttributesId = array();
        if ($hasCombinations) {

            $quantity = 0;
            $minPrice = 99999999999;
            $maxPrice = -1;
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

                $combinationPriceGroup = ProductPrices::getProductPrices($context, $productId, $combination['id_product_attribute'], true);
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

            if (!$colorToFilterBy && $indexImagesPerColor) {
                $combinationImages = ApisearchProduct::getImagesByProductAttributes(array_values($productAttributesId), $langId);
                foreach ($productAttributesId as $colorHex => $attributeId) {
                    $finalImagesByColor[ltrim($colorHex, '#')] = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME,
                        $combinationImages[$attributeId]
                        , $imageType);
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


        if ($colorToFilterBy) {
            $combinationImages = ApisearchProduct::getImagesByProductAttributes(array_values($productAttributesId), $langId);
            if (
                isset($productAttributesId[$colorToFilterBy]) &&
                isset($combinationImages[$productAttributesId[$colorToFilterBy]])
            ) {
                $imageId = $combinationImages[$productAttributesId[$colorToFilterBy]];
                $image = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME, $imageId, $imageType);
            } else {
                $image = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME, $img, $imageType);
            }

            $firstCombinationIdProductAttribute = $hasCombinations ? $combinations[0]['id_product_attribute'] : null;
            $url = \Context::getContext()->link->getProductLink($productId, null, null, null, $langId, $context->getShopId(), $firstCombinationIdProductAttribute);
        } else {
            $image = \Context::getContext()->link->getImageLink($product['link_rewrite'] ?? ApisearchDefaults::PLUGIN_NAME, $img, $imageType);
            $url = \Context::getContext()->link->getProductLink($productId, null, null, null, $langId, $context->getShopId());
        }

        $priceGroup = ProductPrices::getProductPrices($context, $productId, $idProductAttribute, true);
        $price = $priceGroup[0];
        $priceWithCurrency = $priceGroup[1];
        $priceNoRound = $priceGroup[2];

        $oldPriceGroup = ProductPrices::getProductPrices($context, $productId, $idProductAttribute, false);
        $oldPrice = $oldPriceGroup[0];
        $oldPriceWithCurrency = $oldPriceGroup[1];
        $oldPriceNoRound = $oldPriceGroup[2];

        $discountPercentage = ProductPrices::getDiscount($priceNoRound, $oldPriceNoRound);
        $withDiscount = $discountPercentage !== null;

        $frontFeatures = $product['front_features'] ?? null;
        $frontFeaturesKeyFixed = array();
        $frontFeaturesValues = array();
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

        // If long description is enabled, return long description
        // If long description is disabled or long description is empty, return short description if is enabled
        $loadLongDescription = \Configuration::get('AS_INDEX_LONG_DESCRIPTIONS');
        $loadShortDescription = \Configuration::get('AS_INDEX_DESCRIPTIONS');
        $description = null;
        if ($loadLongDescription && !empty($product['description'])) {
            $description = \strip_tags(\strval($product['description']));
        }

        if (empty($description) && $loadShortDescription && !empty($product['description_short'])) {
            $description = \strip_tags(\strval($product['description_short']));
        }

        if ($isB2B) {
            /**
             * Groups
             */
            $users = array();
            $keys = array();
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
                $groupPriceGroup = ProductPrices::getProductPrices($context, $productId, $idProductAttribute, true, $groupData['id_group'], $groupData['id_customer'], $groupData['with_tax'] == '0');
                $groupPrice = $groupPriceGroup[0];
                $groupPriceWithCurrency = $groupPriceGroup[1];
                $groupPriceNoRound = $groupPriceGroup[2];

                $groupOldPriceGroup = ProductPrices::getProductPrices($context, $productId, $idProductAttribute, false, $groupData['id_group'], $groupData['id_customer'], $groupData['with_tax'] == '0');
                $groupOldPrice = $groupOldPriceGroup[0];
                $groupOldPriceWithCurrency = $groupOldPriceGroup[1];
                $groupOldPriceNoRound = $groupOldPriceGroup[2];

                if (
                    $price == $groupPrice &&
                    $oldPrice == $groupOldPrice
                ) {
                    continue;
                }

                $groupDiscountPercentage = ProductPrices::getDiscount($groupPriceNoRound, $groupOldPriceNoRound);
                $groupWithDiscount = $groupDiscountPercentage !== null;

                $users[$key] = [
                    'p' => $groupPrice,
                    'pc' => $groupPriceWithCurrency,
                ];

                if ($groupWithDiscount) {
                    $users[$key]['op'] = $groupOldPrice;
                    $users[$key]['opc'] = $groupOldPriceWithCurrency;
                    $users[$key]['d'] = $groupDiscountPercentage;
                }
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
                'with_discount' => $withDiscount,
                'discount_percentage' => $discountPercentage,
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
                $supplierReferences ?? array()
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

        if ($colorToFilterBy) {
            $itemAsArray['uuid']['id'] = $productId . '-' . trim($colorToFilterBy, '# ');
        }

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
