<?php

class Exporter
{
    private $builder;
    private $connection;

    /**
     * @param Builder    $builder
     * @param Connection $connection
     */
    public function __construct(Builder $builder, Connection $connection)
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

            $productsId = self::getExportableProducts($langId);
            if (empty($productsId)) {
                continue;
            }

            /**
             * Items
             * Id_land
             */
            $version = \strval(rand(1000000000, 9999999999));
            $bulkNumber = 100;
            $this->builder->buildItems($productsId, $langId, $version, $bulkNumber, function(array $items) use ($apisearchClient, &$numberOfIndexedItems, &$numberOfPutCalls) {
                $apisearchClient->putItems($items);
                $numberOfIndexedItems += count($items);
                $numberOfPutCalls++;
                var_dump('Memory used ' . memory_get_usage());
            });

            $apisearchClient->deleteItemsByQuery(array(
                'q' => '',
                'filters' => array(
                    'version' => array(
                        'field' => 'indexed_metadata.as-version',
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

    /**
     * @return array
     */
    public function getIndicesClientByLanguageId()
    {
        $indicesClients = array();
        $indicesId = [];
        foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
            $langId = $lang['id_lang'];
            $indexId = Configuration::get('AS_INDEX', $langId);
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
     * @param string $langId
     *
     * @return array
     */
    public function getExportableProducts($langId)
    {
        return Shop::isFeatureActive()
            ? static::getFeaturedShopProducts($langId)
            : static::getNonFeaturedShopProducts($langId);
    }

    /**
     * @param string $langId
     *
     * @return array
     */
    private function getFeaturedShopProducts($langId)
    {
        if (empty(Configuration::get('AS_SHOP'))) {
            return array();
        }

        $assoc = json_decode(Configuration::get('AS_SHOP'), 1);
        if (!isset($assoc['shop']) || $assoc['shop'] == false) {
            return array();
        }

        $shop_og = Context::getContext()->shop->id;
        $allProductsId = array();
        foreach ($assoc['shop'] as $shop_id) {
            Shop::setContext(Shop::getContext(), $shop_id);
            $allProductsId = array_merge($allProductsId, $this->getNonFeaturedShopProducts($langId));
        }

        Shop::setContext(Shop::getContext(), $shop_og);

        return array_unique($allProductsId);
    }

    /**
     * @param string $langId
     *
     * @return array
     */
    private function getNonFeaturedShopProducts($langId)
    {
        $count = 100;
        $offset = 0;
        $productsId = array();
        while (true) {
            $products = Product::getProducts($langId, $offset, $count, 'id_product', 'asc', false, true);
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

        return $productsId;
    }
}
