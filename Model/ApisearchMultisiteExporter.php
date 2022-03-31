<?php

namespace Apisearch\Model;

class ApisearchExporter
{
    private $builder;
    private $connection;

    /**
     * @param ApisearchBuilder    $builder
     * @param ApisearchConnection $connection
     */
    public function __construct(ApisearchBuilder $builder, ApisearchConnection $connection)
    {
        $this->builder = $builder;
        $this->connection = $connection;
    }

    /**
     * Export all shops and return the number of indexed items
     *
     * @return [int, int]
     */
    public function exportAll()
    {
        $multisiteTree = $this->getMultisiteTree();
        $multisiteItems = [];
        foreach ($multisiteTree as $shop => $languages) {
            foreach ($languages as $language) {
                $siteKey = "{$shop}_{$language}";

                $productsID = self::getProductsIDByShopAndLang($shop, $language);
                $allProducts = [];
                $this->builder->buildChunkItems($productsID, $language, '', $shop, function($products) use (&$allProducts) {
                    $allProducts = array_merge($allProducts, $products);
                });

                foreach ($allProducts as $product) {
                    if (!isset($multisiteItems[$product['uuid']['id']]))
                }



                $multisiteProducts[$siteKey] = [
                    'shop' => $shop,
                    'lang' => $language,
                    'products' => $allProducts
                ];
            }
        }
    }

    /**
     * Return the whole multisite tree
     *
     * [
     *      site1: [
     *          "lang1", "lang2"
     *      ],
     *      site2: [
     *          "lang3", "lang4"
     *      ]
     * ]
     *
     * @return array
     */
    public function getMultisiteTree()
    {
        $multiShopIsActive = \Shop::isFeatureActive();
        $tree = [];

        if ($multiShopIsActive) {

            $shopIDs = \Shop::getShops();
            foreach ($shopIDs as $shopID) {
                $tree[$shopID] = \Context::getContext()->language->getLanguages(true, $shopID);
            }

        } else {
            $shopID = \Context::getContext()->shop->id;
            $languages = \Context::getContext()->language->getLanguages(true, $shopID);

            $tree = [$shopID => $languages];
        }

        return $tree;
    }

    /**
     * @param string $shopId
     * @param string $langId
     *
     * @return array
     */
    private function getProductsIDByShopAndLang($shopId, $langId)
    {
        $count = 500;
        $offset = 0;
        $productsId = array();
        while (true) {
            $products = ApisearchProduct::getProductsId($langId, $offset, $count, $shopId);
            if (!empty($products)) {
                $productsId = array_merge($productsId, array_map(function(array $product) {
                    return $product['id_product'];
                }, $products));
                $offset = $offset + $count;
            } else {
                break;
            }
        }

        return $productsId;
    }
}
