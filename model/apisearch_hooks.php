<?php

require_once __DIR__ . '/apisearch_defaults.php';

class ApisearchHooks
{
    private $builder;
    private $connection;

    /**
     * @param ApisearchBuilder $builder
     * @param ApisearchConnection $connection
     */
    public function __construct(ApisearchBuilder $builder, ApisearchConnection $connection)
    {
        $this->builder = $builder;
        $this->connection = $connection;
    }

    /**
     * @param Apisearch
     *
     * @param $productId
     */
    public function putProductById($productId)
    {
        $product = new Product($productId);
        if (Validate::isLoadedObject($product)) {

            $product->active
                ? $this->doPutProductById($productId)
                : $this->doDeleteProductById($productId);
        }
    }

    /**
     * @param $productId
     */
    public function deleteProductById($productId)
    {
        $product = new Product($productId);
        if (Validate::isLoadedObject($product)) {
            $this->doDeleteProductById($productId);
        }
    }

    /**
     * @param $productId
     */
    private function doPutProductById($productId)
    {
        $indices = array();
        foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
            $indexId = Configuration::get('AS_INDEX', $lang['id_lang']);
            if (in_array($indexId, $indices)) {
                continue;
            }

            $indices[] = $indexId;
            $apisearchClient = $this->connection->getConnectionByLanguageId($lang['id_lang']);
            if (false !== $apisearchClient) {
                $item = $this->builder->buildItems(
                    [$productId],
                    $lang['id_lang'],
                    '',
                    100,
                    Context::getContext()->shop->id,
                    function(array $items) use ($apisearchClient) {
                        $apisearchClient->putItems($items);
                    }
                );
                if (!empty($item)) {
                    $apisearchClient->putItems([$item]);
                }
            }
        }
    }

    /**
     * @param $productId
     */
    private function doDeleteProductById($productId)
    {
        $indices = array();
        foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
            $indexId = Configuration::get('AS_INDEX', $lang['id_lang']);
            if (in_array($indexId, $indices)) {
                continue;
            }

            $indices[] = $indexId;
            $apisearchClient = $this->connection->getConnectionByLanguageId($lang['id_lang']);
            if (false !== $apisearchClient) {
                $apisearchClient->deleteItems([[
                    'id' => $productId,
                    'type' => 'product'
                ]]);
            }
        }
    }
}
