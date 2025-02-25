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

use Apisearch\Model\Product\ProductPrices;
use Apisearch\Context;

set_time_limit(1800);

/**
 * We suppress all possible incoming output data to avoid malformed feed
 */
ob_start();

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');
require_once __DIR__.'/vendor/autoload.php';

function pricesFromProductsId(
    Context $context,
    array $productsId
)
{
    $prices = [];
    foreach ($productsId as $productId) {
        $priceGroup = ProductPrices::getProductPrices($context, $productId, null, true);
        $price = $priceGroup[0];
        $priceWithCurrency = $priceGroup[1];
        $priceNoRound = $priceGroup[2];

        $oldPriceGroup = ProductPrices::getProductPrices($context, $productId, null, false);
        $oldPrice = $oldPriceGroup[0];
        $oldPriceWithCurrency = $oldPriceGroup[1];
        $oldPriceNoRound = $oldPriceGroup[2];

        $discountPercentage = ProductPrices::getDiscount($priceNoRound, $oldPriceNoRound);
        $withDiscount = $discountPercentage !== null;

        $prices[$productId] = [
            'p' => $price,
            'p_c' => $priceWithCurrency,
            'op' => $oldPrice,
            'op_c' => $oldPriceWithCurrency,
            'wd' => $withDiscount,
            'dp' => $discountPercentage
        ];
    }

    return $prices;
}

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Content-Type: application/json; charset=UTF-8");

$ids = \Tools::getValue('ids', '');
$ids = explode(',', $ids);
$ids = array_filter($ids);
if (empty($ids)) {
    echo "[]";
    die();
}

$context = Context::fromCurrentPrestashopContext();
$prices = pricesFromProductsId($context, $ids);

echo json_encode($prices);
