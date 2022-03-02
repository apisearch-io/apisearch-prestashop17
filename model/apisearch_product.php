<?php

require_once __DIR__ . '/apisearch_manufacturer.php';
require_once __DIR__ . '/apisearch_supplier.php';

class ApisearchProduct
{
    /**
     * @param $langId
     * @param $start
     * @param $limit
     * @param $shopId
     */
    public static function getProductsId(
        $langId,
        $start,
        $limit,
        $shopId
    )
    {
        $prefix = _DB_PREFIX_;
        $sql = "
            SELECT DISTINCT(p.id_product)
            FROM {$prefix}product p
                     INNER JOIN {$prefix}product_shop ps ON ps.id_product = p.id_product ".($shopId ? "AND ps.id_shop = $shopId" : '') ."
                     LEFT JOIN {$prefix}product_lang pl ON p.id_product = pl.id_product
            WHERE pl.`id_lang` = $langId AND ps.`visibility` IN ('both', 'catalog') AND ps.`active` = 1
            ORDER BY id_product ASC
            LIMIT $start,$limit";

        return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * @param int[] $productsId
     * @param $langId
     *
     * @return mixed
     */
    public static function getFullProductsById($productsId, $langId, $shopId)
    {
        $prefix = _DB_PREFIX_;
        $productIdsAsString = implode(',', $productsId);
        $sql = "
            SELECT p.*, ps.advanced_stock_management, im.id_image, pl.*
            FROM {$prefix}product p
                LEFT JOIN {$prefix}product_lang `pl` ON p.`id_product` = pl.`id_product` AND pl.`id_lang` = $langId
                LEFT JOIN {$prefix}product_shop `c` ON p.`id_product` = c.`id_product` AND c.`id_shop` = $shopId
                LEFT JOIN {$prefix}product_shop ps ON ps.id_product = p.id_product
                LEFT JOIN {$prefix}image_shop im ON im.id_product = p.id_product AND im.cover = 1
                LEFT JOIN {$prefix}image_lang iml ON im.id_image = iml.id_image AND iml.id_lang = $langId AND im.`id_shop` = $shopId
                INNER JOIN {$prefix}product_shop product_shop ON (product_shop.id_product = p.id_product AND product_shop.id_shop = $shopId)
            WHERE p.id_product IN($productIdsAsString);
        ";

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        $productsIndexedById = [];
        $manufacturers = ApisearchManufacturer::getManufacturers(array_column($products, 'id_manufacturer'));
        $suppliers = ApisearchManufacturer::getManufacturers(array_column($products, 'id_supplier'));
        foreach ($products as $product) {
            $product['manufacturer'] = $manufacturers[$product['id_manufacturer']] ?? '';
            $product['supplier'] = $suppliers[$product['id_supplier']] ?? '';
            $product['name'] = \strip_tags($product['name'] ?? '');
            $product['description'] = \strip_tags($product['description'] ?? '');
            $product['description_short'] = \strip_tags($product['description_short'] ?? '');
            $productsIndexedById[$product['id_product']] = $product;
        }

        $sql = "
            SELECT p.id_product, 
                group_concat(distinct(cp.id_category)) as cp_id_categories, 
                group_concat(distinct(t.name)) as tag_names,
                group_concat(fp.id_feature, '~~', fp.id_feature_value SEPARATOR '>><<') as features,
                group_concat(fl.id_feature, '~~',  fl.name SEPARATOR '>><<') as features_lang,
                group_concat(fvl.id_feature_value, '~~',  fvl.value SEPARATOR '>><<') as features_value
            FROM {$prefix}product p
                LEFT JOIN {$prefix}product_tag pt ON (pt.id_product = p.id_product)
                LEFT JOIN {$prefix}tag t ON t.id_tag = `pt`.id_tag
                LEFT JOIN {$prefix}category_product cp ON cp.id_product = p.id_product
                LEFT JOIN {$prefix}feature_product fp ON fp.id_product = p.id_product
                LEFT JOIN {$prefix}feature_lang fl ON (fl.id_feature = fp.id_feature AND fl.id_lang = $langId)
                LEFT JOIN {$prefix}feature_value_lang fvl ON (fvl.id_feature_value = fp.id_feature_value AND fvl.id_lang = $langId)
                INNER JOIN {$prefix}product_shop product_shop ON (product_shop.id_product = p.id_product AND product_shop.id_shop = $shopId)
            WHERE p.`id_product` IN($productIdsAsString)
            GROUP BY p.id_product
        ";

        $products = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        foreach ($products as $groupsProduct) {
            $productId = $groupsProduct['id_product'];
            if (array_key_exists($productId, $productsIndexedById)) {
                $productsIndexedById[$productId]['categories_id'] = array_filter(explode(',', $groupsProduct['cp_id_categories']));
                $productsIndexedById[$productId]['tags'] = array_filter(explode(',', $groupsProduct['tag_names']));
                $features = array_map(function(string $value) {
                    return explode('~~', $value, 2);
                }, array_unique(explode('>><<', $groupsProduct['features'])));

                if (!empty($features) && $features[0] !== [""]) {
                    $featuresLangIndexed = [];
                    $featuresLang = array_unique(explode('>><<', $groupsProduct['features_lang']));
                    foreach ($featuresLang as $featureLang) {
                        $parts = explode('~~', $featureLang, 2);
                        if (count($parts) === 2) {
                            $featuresLangIndexed[$parts[0]] = $parts[1];
                        }
                    }

                    $featuresValueIndexed = [];
                    $featuresValue = array_unique(explode('>><<', $groupsProduct['features_value']));
                    foreach ($featuresValue as $featureValue) {
                        $parts = explode('~~', $featureValue, 2);
                        if (count($parts) === 2) {
                            $featuresValueIndexed[$parts[0]] = $parts[1];
                        }
                    }

                    $productsIndexedById[$productId]['front_features'] = [];
                    foreach ($features as $feature) {
                        if (count($feature) === 2) {
                            $featureId = $featuresLangIndexed[$feature[0]] ?? null;
                            $featureValue = $featuresValueIndexed[$feature[1]] ?? null;
                            if ($featureId && $featuresValue) {
                                $productsIndexedById[$productId]['front_features'][$featureId] = $featureValue;
                            }
                        }
                    }
                }
            }
        }

        return $productsIndexedById;
    }

    /**
     * Get all available product attributes combinations.
     *
     * @param int $id_lang Language id
     * @param bool $groupByIdAttributeGroup
     *
     * @return array Product attributes combinations
     */
    public static function getAttributeCombinations($productId, $id_lang, $groupByIdAttributeGroup = true)
    {
        if (!Combination::isFeatureActive()) {
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
                pai.id_product_attribute,
                i.id_image
            FROM {$prefix}product_attribute pa
            LEFT JOIN `{$prefix}product_attribute_combination` pac ON pac.`id_product_attribute` = pa.`id_product_attribute`
            LEFT JOIN `{$prefix}attribute` a ON a.`id_attribute` = pac.`id_attribute`
            LEFT JOIN `{$prefix}attribute_group` ag ON ag.`id_attribute_group` = a.`id_attribute_group`
            LEFT JOIN `{$prefix}attribute_lang` al ON (a.`id_attribute` = al.`id_attribute` AND al.`id_lang` = $id_lang)
            LEFT JOIN `{$prefix}attribute_group_lang` agl ON (ag.`id_attribute_group` = agl.`id_attribute_group` AND agl.`id_lang` = $id_lang)
            LEFT JOIN `{$prefix}product_attribute_image` pai ON pai.id_product_attribute = pa.id_product_attribute
            LEFT JOIN `{$prefix}image_lang` il ON (il.`id_image` = pai.`id_image`)
            LEFT JOIN `{$prefix}image` i ON (i.`id_image` = pai.`id_image`)
            WHERE pa.`id_product` = $productId
            GROUP BY pa.id_product_attribute" . ($groupByIdAttributeGroup ? ',ag.`id_attribute_group`' : '');

        $res = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);

        //Get quantity of each variations
        foreach ($res as $key => $row) {
            $res[$key]['quantity'] = StockAvailable::getQuantityAvailableByProduct($row['id_product'], $row['id_product_attribute']);
        }

        return $res;
    }
}
