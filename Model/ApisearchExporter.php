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
