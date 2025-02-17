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

namespace Apisearch\Model\Product;

use Apisearch\Context;

class ProductPrices
{
    /**
     * @param Context $context
     * @param $productId
     * @param $idProductAttribute
     * @param $reduction
     * @param $groupId
     * @param $userId
     * @return array
     */
    public static function getProductPrices(Context $context, $productId, $idProductAttribute, $reduction, $groupId = null, $userId = null, $withTax = null)
    {
        if (!$groupId) {
            $groupId = $context->getGroupId();
        }

        $resolvedWithTax = $context->isWithTax();
        if (is_bool($withTax)) {
            $resolvedWithTax = $withTax;
        }

        $specPrice = true;
        $price = \Product::priceCalculation(
            $context->getShopId(), $productId, $idProductAttribute,
            $context->getIdCountry(), $context->getIdState(), $context->getZipcode(),
            $context->getCurrency()->id, $groupId, 1,
            $resolvedWithTax, 6, false, $reduction, false, $specPrice, true, $userId
        );
        $price = \Tools::convertPrice($price, $context->getCurrency());
        $priceRounder = \round($price, 2);
        $priceWithCurrency = \Tools::displayPrice($priceRounder, $context->getCurrency());

        return array($priceRounder, $priceWithCurrency, $price);
    }

    /**
     * @param $price
     * @param $oldPrice
     * @return float|null
     */
    public static function getDiscount($price, $oldPrice)
    {
        $withDiscount = ($oldPrice - $price) > 0;
        $discountPercentage = null;
        if ($withDiscount) {
            $discountPercentage = 100 - (100 * $price) / $oldPrice;
            $discountPercentage = round($discountPercentage);
        }

        return $discountPercentage;
    }
}