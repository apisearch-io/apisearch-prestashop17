<?php

namespace Apisearch\Model;

use Apisearch\Context;

class ApisearchExporter
{
    private $builder;

    /**
     * @param ApisearchBuilder $builder
     */
    public function __construct(ApisearchBuilder $builder)
    {
        $this->builder = $builder;
    }

    public function printItemsByShopAndLang(Context $context)
    {
        $count = 100;
        $offset = 0;
        $version = \strval(rand(1000000000, 9999999999));
        usleep(100000);

        while (true) {
            $products = ApisearchProduct::getProductsId($offset, $count, $context);

            if (!empty($products)) {
                $productsIds = array_map(function(array $product) {
                    return $product['id_product'];
                }, $products);

                if ($context->isDebug()) {
                    echo json_encode([
                        'debug' => 'initial products list',
                        'ids' => $productsIds
                    ]);
                    echo PHP_EOL;
                    ob_flush();
                }

                $this->builder->buildChunkItems($productsIds, $version, $context, function(array $items) use (&$allItems) {
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
}
