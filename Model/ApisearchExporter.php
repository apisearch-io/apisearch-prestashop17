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
        $apisearchClients = self::getIndicesClientByLanguageId();
        $numberOfIndexedItems = 0;
        $numberOfPutCalls = 0;

        foreach ($apisearchClients as $langId => $apisearchClient) {

            $productsIdByShopId = self::getExportableProducts($langId);
            if (empty($productsIdByShopId)) {
                continue;
            }

            /**
             * Items
             * Id_land
             */
            $version = \strval(rand(1000000000, 9999999999));
            $bulkNumber = 100;

            foreach ($productsIdByShopId as $shopId => $productsId) {
                $this->builder->buildItems($productsId, $langId, $version, $bulkNumber, $shopId, function(array $items) use ($apisearchClient, &$numberOfIndexedItems, &$numberOfPutCalls) {
                    $apisearchClient->putItems($items);
                    $numberOfIndexedItems += count($items);
                    $numberOfPutCalls++;
                });
            }

            $apisearchClient->deleteItemsByQuery(array(
                'q' => '',
                'filters' => array(
                    'version' => array(
                        'field' => 'indexed_metadata.as_version',
                        'values' => array($version),
                        'application_type' => 16
                    )
                )
            ));
        }

        return array(
            $numberOfIndexedItems,
            $numberOfPutCalls
        );
    }

    public function printItemsByShopAndLang($shopId, $langIsoCode)
    {
        $count = 100;
        $offset = 0;
        $version = \strval(rand(1000000000, 9999999999));
        $langId = $this->getLangIdByIsoCode($langIsoCode);
        $loadSales = \Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT') == 1;
        $loadSuppliers = \Configuration::get('AS_FIELDS_SUPPLIER_REFERENCES') == 1;

        while (true) {
            $products = ApisearchProduct::getProductsId($langId, $offset, $count, $shopId);

            if (!empty($products)) {
                $productsIds = array_map(function(array $product) {
                    return $product['id_product'];
                }, $products);

                $this->builder->buildChunkItems($productsIds, $langId, $version, $shopId, $loadSales, $loadSuppliers, function(array $items) use (&$allItems) {
                    foreach ($items as $item) {
                        echo json_encode($item);
                        echo PHP_EOL;
                        ob_flush();
                    }
                });

                $offset = $offset + $count;
            } else {
                break;
            }
        }
    }


    public function getLangIdByIsoCode($langIsoCode)
    {
        $apisearchClients = self::getIndicesClientByLanguageIsoCode();
        if (!array_key_exists($langIsoCode, $apisearchClients)) {
            throw new \Exception('Language not found');
        }

        return $apisearchClients[$langIsoCode]['lang_id'];
    }

    /**
     * @return array
     */
    public function getIndicesClientByLanguageId()
    {
        $indicesClients = array();
        $indicesId = [];
        foreach (\Context::getContext()->language->getLanguages(true, \Context::getContext()->shop->id) as $lang) {
            $langId = $lang['id_lang'];
            $indexId = \Configuration::get('AS_INDEX', $langId);
            if (in_array($indexId, $indicesId)) {
                continue;
            }

            $indicesId[] = $indexId;
            $indicesClients[$langId] = $this
                ->connection
                ->getConnectionByLanguageId($langId);
        }

        return array_filter($indicesClients);
    }

    /**
     * @return array
     */
    public function getIndicesClientByLanguageIsoCode()
    {
        $indicesClients = array();
        $indicesId = [];
        foreach (\Context::getContext()->language->getLanguages(true, \Context::getContext()->shop->id) as $lang) {
            $langIsoCode = $lang['iso_code'];
            $langId = $lang['id_lang'];
            $indexId = \Configuration::get('AS_INDEX', $langId);
            if (in_array($indexId, $indicesId)) {
                continue;
            }

            $indicesId[] = $indexId;
            $indicesClients[$langIsoCode] = [
                'lang_id' => $langId,
                'connection' => $this
                    ->connection
                    ->getConnectionByLanguageId($langId)
            ];
        }

        return array_filter($indicesClients);
    }

    /**
     * @param string $langId
     *
     * @return array
     */
    public function getExportableProducts($langId)
    {
        return \Shop::isFeatureActive()
            ? static::getFeaturedShopProducts($langId)
            : static::getNonFeaturedShopProducts($langId, \Context::getContext()->shop->id);
    }

    /**
     * @param string $langId
     *
     * @return array
     */
    private function getFeaturedShopProducts($langId)
    {
        if (empty(\Configuration::get('AS_SHOP'))) {
            return array();
        }

        $assoc = json_decode(\Configuration::get('AS_SHOP'), 1);
        if (!isset($assoc['shop']) || $assoc['shop'] == false) {
            return array();
        }

        $shopOg = \Context::getContext()->shop->id;
        $productsIdByShopId = array();
        foreach ($assoc['shop'] as $shopId) {
            \Shop::setContext(\Shop::getContext(), $shopId);
            $productsIdByShopId[$shopId] = $this->getNonFeaturedShopProducts($langId, $shopId);
        }

        \Shop::setContext(\Shop::getContext(), $shopOg);

        return $productsIdByShopId;
    }

    /**
     * @param string $langId
     * @param string $shopId
     *
     * @return array
     */
    private function getNonFeaturedShopProducts($langId, $shopId)
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
                $count = count($products);
                $offset = $offset + $count;
            } else {
                break;
            }
        }

        return [$shopId => $productsId];
    }
}
