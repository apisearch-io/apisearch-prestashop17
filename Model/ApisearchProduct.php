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

class ApisearchProduct
{
    /**
     * @param $start
     * @param $limit
     * @param Context $context
     */
    public static function getProductsId(
        $start,
        $limit,
        Context $context
    )
    {
        $prefix = _DB_PREFIX_;
        $orderBy = ApisearchOrderBy::getCurrentOrderByValue();

        $sql = "
            SELECT DISTINCT(p.id_product)
            FROM {$prefix}product p
                     INNER JOIN {$prefix}product_shop ps ON ps.id_product = p.id_product AND ps.id_shop = {$context->getShopId()}
                     LEFT JOIN {$prefix}product_lang pl ON p.id_product = pl.id_product
            WHERE
                pl.`id_lang` = {$context->getLanguageId()} AND
                ps.`visibility` IN ('both', 'search') AND
                ps.`active` = 1
            $orderBy
            LIMIT $start,$limit";

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
    }

    /**
     * @param $productsId
     * @param Context $context
     * @return array
     * @throws \PrestaShopDatabaseException
     */
    public static function getFullProductsById(
        $productsId,
        Context $context
    )
    {
        $prefix = _DB_PREFIX_;
        $productIdsAsString = implode(',', $productsId);
        $langId = $context->getLanguageId();
        $orderBy = ApisearchOrderBy::getCurrentOrderByValue();

        $sql = "
            SELECT
                p.*,
                ps.advanced_stock_management,
                im.id_image,
                pl.*,
                " . ($context->isLoadSales() ? 'psale.quantity as sales' : "0 as sales") .",
                st.out_of_stock as real_out_of_stock,
                " . ($context->isLoadSuppliers() ? 'group_concat(distinct psup.product_supplier_reference SEPARATOR \'|\') as supplier_referencies' : "'' as supplier_referencies") ."
            FROM {$prefix}product p
                INNER JOIN {$prefix}product_shop ps ON ps.id_product = p.id_product AND ps.`id_shop` = {$context->getShopId()}
                LEFT JOIN {$prefix}product_lang `pl` ON p.`id_product` = pl.`id_product` AND pl.`id_lang` = $langId AND pl.`id_shop` = {$context->getShopId()}
                LEFT JOIN {$prefix}image_shop im ON im.id_product = p.id_product AND im.cover = 1 AND im.`id_shop` = {$context->getShopId()}
                LEFT JOIN {$prefix}image_lang iml ON im.id_image = iml.id_image AND iml.id_lang = $langId
                " . ($context->isLoadSales() ? "LEFT JOIN {$prefix}product_sale psale ON (psale.id_product = p.id_product)" : "") . "
                LEFT JOIN {$prefix}stock_available st ON (st.id_product = p.id_product)
                " . ($context->isLoadSuppliers() ? "LEFT JOIN {$prefix}product_supplier psup ON (psup.id_product = psup.id_product)" : "") . "
            WHERE p.id_product IN($productIdsAsString)
            GROUP BY p.id_product
            $orderBy
            ;
        ";

        $products = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
        $productsIndexedById = [];
        $manufacturers = ApisearchManufacturer::getManufacturers(array_column($products, 'id_manufacturer'), $context);
        foreach ($products as $product) {
            $product['manufacturer'] = $manufacturers[$product['id_manufacturer']] ?? '';
            $product['name'] = \strip_tags($product['name'] ?? '');
            $product['description'] = \strip_tags($product['description'] ?? '');
            $product['description_short'] = \strip_tags($product['description_short'] ?? '');
            $product['supplier_referencies'] = explode('|', $product['supplier_referencies'] ?? '');
            if ($product['supplier_referencies'] == [""]) {
                $product['supplier_referencies'] = null;
            }
            $productsIndexedById[$product['id_product']] = $product;
        }

        if ($context->isDebug()) {
            echo json_encode([
                'debug' => 'products hydrated',
                'count' => count($productsIndexedById),
                'ids' => array_keys($productsIndexedById)
            ]);
            echo PHP_EOL;
            ob_flush();
        }

        $sql = "
            SELECT p.id_product, 
                group_concat(distinct(cp.id_category)) as cp_id_categories, 
                group_concat(distinct(t.name)) as tag_names,
                group_concat(distinct fp.id_feature, '~~', fp.id_feature_value SEPARATOR '|') as features,
                group_concat(distinct fl.id_feature, '~~',  fl.name SEPARATOR '|') as features_lang,
                group_concat(distinct fvl.id_feature_value, '~~',  fvl.value SEPARATOR '|') as features_value
            FROM {$prefix}product p
                LEFT JOIN {$prefix}product_tag pt ON (pt.id_product = p.id_product)
                LEFT JOIN {$prefix}tag t ON t.id_tag = `pt`.id_tag
                LEFT JOIN {$prefix}category_product cp ON cp.id_product = p.id_product
                LEFT JOIN {$prefix}feature_product fp ON fp.id_product = p.id_product
                LEFT JOIN {$prefix}feature_lang fl ON (fl.id_feature = fp.id_feature AND fl.id_lang = $langId)
                LEFT JOIN {$prefix}feature_value_lang fvl ON (fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = $langId)
                INNER JOIN {$prefix}product_shop product_shop ON (product_shop.id_product = p.id_product AND product_shop.id_shop = {$context->getShopId()})
            WHERE p.`id_product` IN($productIdsAsString)
            GROUP BY p.id_product
        ";

        $products = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        foreach ($products as $groupsProduct) {
            $productId = $groupsProduct['id_product'];


            if (array_key_exists($productId, $productsIndexedById)) {
                $productsIndexedById[$productId]['categories_id'] = array_filter(explode(',', $groupsProduct['cp_id_categories']));
                $productsIndexedById[$productId]['tags'] = array_filter(explode(',', $groupsProduct['tag_names']));
                $features = array_map(function(string $value) {
                    return explode('~~', $value, 2);
                }, array_unique(explode('|', $groupsProduct['features'])));

                if (!empty($features) && $features[0] !== [""]) {
                    $featuresLangIndexed = [];
                    $featuresLang = array_unique(explode('|', $groupsProduct['features_lang']));
                    foreach ($featuresLang as $featureLang) {
                        $parts = explode('~~', $featureLang, 2);
                        if (count($parts) === 2) {
                            if (!array_key_exists($parts[0], $featuresLangIndexed)) {
                                $featuresLangIndexed[$parts[0]] = [];
                            }
                            $featuresLangIndexed[$parts[0]][] = $parts[1];
                        }
                    }

                    $featuresValueIndexed = [];
                    $featuresValue = array_unique(explode('|', $groupsProduct['features_value']));
                    foreach ($featuresValue as $featureValue) {
                        $parts = explode('~~', $featureValue, 2);
                        if (count($parts) === 2) {
                            $featuresValueIndexed[$parts[0]] = $parts[1];
                        }
                    }

                    $productsIndexedById[$productId]['front_features'] = [];
                    foreach ($features as $feature) {
                        if (count($feature) === 2) {
                            $featureIds = $featuresLangIndexed[$feature[0]] ?? [];
                            foreach ($featureIds as $featureId) {
                                $featureValue = $featuresValueIndexed[$feature[1]] ?? null;
                                if ($featureId && $featuresValue) {
                                    if (!array_key_exists($featureId, $productsIndexedById[$productId]['front_features'])) {
                                        $productsIndexedById[$productId]['front_features'][$featureId] = [];
                                    }

                                    $productsIndexedById[$productId]['front_features'][$featureId][] = $featureValue;
                                }
                            }
                        }
                    }
                }
            }
        }

        if ($context->isDebug()) {
            echo json_encode([
                'debug' => 'products completed',
                'count' => count($productsIndexedById),
                'ids' => array_keys($productsIndexedById)
            ]);
            echo PHP_EOL;
            ob_flush();
        }

        return $productsIndexedById;
    }

    /**
     * @param $productId
     * @param $idLang
     * @return array|bool|\mysqli_result|\PDOStatement|resource|null
     * @throws \PrestaShopDatabaseException
     */
    public static function getProductAvailableColors($productId, $idLang)
    {
        if (!\Combination::isFeatureActive()) {
            return [];
        }

        $prefix = _DB_PREFIX_;
        $sql = "
            SELECT DISTINCT a.color
            FROM {$prefix}product_attribute pa
            LEFT JOIN `{$prefix}product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
            LEFT JOIN `{$prefix}attribute` a ON a.`id_attribute` = pac.`id_attribute`
            LEFT JOIN `{$prefix}attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
            LEFT JOIN `{$prefix}attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = $idLang)
            LEFT JOIN `{$prefix}attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = $idLang)
            WHERE pa.`id_product` = $productId";

        $colors = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        return array_map(function($color) {
            return $color['color'];
        }, $colors);
    }

    /**
     * @param $productId
     * @param $idLang
     * @param $colorToFilterBy
     * @return array|bool|\mysqli_result|\PDOStatement|resource
     * @throws \PrestaShopDatabaseException
     */
    public static function getAttributeCombinations($productId, $idLang, $colorToFilterBy)
    {
        if (!\Combination::isFeatureActive()) {
            return [];
        }

        $prefix = _DB_PREFIX_;
        $sql = "
            SELECT
                pa.*,
                ag.`id_attribute_group`, 
                ag.`is_color_group`,
                agl.`name` AS group_name,
                al.`name` AS attribute_name,
                a.`id_attribute`,
                a.`color` AS attribute_color,
                pai.id_product_attribute as id_product_attribute_image,
                i.id_image
            FROM {$prefix}product_attribute pa
            LEFT JOIN `{$prefix}product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
            LEFT JOIN `{$prefix}attribute` a ON a.`id_attribute` = pac.`id_attribute`
            LEFT JOIN `{$prefix}attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
            LEFT JOIN `{$prefix}attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = $idLang)
            LEFT JOIN `{$prefix}attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = $idLang)
            LEFT JOIN `{$prefix}product_attribute_image` pai ON pai.id_product_attribute = pa.id_product_attribute
            LEFT JOIN `{$prefix}image_lang` il ON (il.`id_image` = pai.`id_image`)
            LEFT JOIN `{$prefix}image` i ON (i.`id_image` = pai.`id_image`) AND i.cover = 1
            WHERE pa.`id_product` = $productId";

        $res = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);

        /**
         * When we want to filter by color, we must group all combinations by their id_product_attribute value.
         * When one of this attribute rows defines the color, the whole group takes this color.
         * After that, we only take these products from groups with that specific color.
         */
        if ($colorToFilterBy) {
            /**
             * Grouping
             */
            $groups = [];
            foreach ($res as $row) {
                $groupId = $row['id_product_attribute'];
                if (!isset($groups[$groupId])) {
                    $groups[$groupId] = array(
                        'color' => null,
                        'products' => array()
                    );
                }

                if (!empty($row['attribute_color'])) {
                    $groups[$groupId]['color'] = $row['attribute_color'];
                }

                $groups[$groupId]['products'][] = $row;
            }

            $res = [];
            foreach ($groups as $group) {
                if ($group['color'] !== $colorToFilterBy) {
                    continue;
                }

                $res = array_merge($res, $group['products']);
            }
        }

        //Get quantity of each variation
        foreach ($res as $key => $row) {
            $res[$key]['quantity'] = \StockAvailable::getQuantityAvailableByProduct(\intval($row['id_product']), \intval($row['id_product_attribute']));
        }

        return $res;
    }

    public static function getImagesByProductAttributes($attributes, $idLang)
    {
        if (!\Combination::isFeatureActive() || empty($attributes)) {
            return [];
        }

        $result = \Db::getInstance()->executeS(
            '
            SELECT pai.`id_image`, pai.`id_product_attribute`
            FROM `' . _DB_PREFIX_ . 'product_attribute_image` pai
            LEFT JOIN `' . _DB_PREFIX_ . 'image_lang` il ON (il.`id_image` = pai.`id_image`)
            LEFT JOIN `' . _DB_PREFIX_ . 'image` i ON (i.`id_image` = pai.`id_image`)
            WHERE pai.`id_product_attribute` IN (' . implode(',', $attributes) . ') AND il.`id_lang` = ' . (int) $idLang . ' ORDER by i.`position`'
        );

        $images = array();
        foreach ($result as $item) {
            if (!isset($images[$item['id_product_attribute']])) {
                $images[$item['id_product_attribute']] = $item['id_image'];
            }
        }

        return $images;
    }
}
