<?php

namespace Apisearch\Model;

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
     * @param $productId
     */
    public function putProductById($productId)
    {
        $product = new \Product($productId);
        if (\Validate::isLoadedObject($product)) {
            $this->doPutProductById($productId);
        }
    }

    /**
     * @param $productId
     */
    public function deleteProductById($productId)
    {
        $product = new \Product($productId);
        if (\Validate::isLoadedObject($product)) {
            $this->doDeleteProductById($productId);
        }
    }

    /**
     * @param $productId
     */
    private function doPutProductById($productId)
    {
        $indices = array();
        foreach (\Context::getContext()->language->getLanguages(true, \Context::getContext()->shop->id) as $lang) {
            $indexId = \Configuration::get('AS_INDEX', $lang['id_lang']);
            if (in_array($indexId, $indices)) {
                continue;
            }

            $indices[] = $indexId;
            $apisearchClient = $this->connection->getConnectionByLanguageId($lang['id_lang']);
            if (false !== $apisearchClient) {

                try {
                    $this->builder->buildItem(
                        $productId,
                        $lang['id_lang'],
                        '',
                        \Context::getContext()->shop->id,
                    function(array $item) use ($apisearchClient) {
                        $apisearchClient->putItems([$item]);
                    });
                } catch (InvalidProductException $_) {
                    syslog(0, $_->getMessage());
                    $apisearchClient->deleteItems([[
                        'id' => $productId,
                        'type' => 'product'
                    ]]);
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
        foreach (\Context::getContext()->language->getLanguages(true, \Context::getContext()->shop->id) as $lang) {
            $indexId = \Configuration::get('AS_INDEX', $lang['id_lang']);
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
