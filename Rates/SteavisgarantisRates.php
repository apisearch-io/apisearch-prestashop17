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

namespace Apisearch\Rates;

use Apisearch\Context;

class SteavisgarantisRates implements IntegrationRates
{
    /**
     * @return bool
     */
    public static function isValid()
    {
        $prefix = _DB_PREFIX_;
        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "
                SELECT 1
                FROM information_schema.tables 
                WHERE table_name = '{$prefix}steavisgarantis_average_rating'", true, false);

        return !empty($result);
    }

    /**
     * @param Context $context
     * @param array $ids
     * @return Rate[]
     * @throws \PrestaShopDatabaseException
     */
    public static function loadRates(Context $context, array $ids)
    {
        $prefix = _DB_PREFIX_;
        $productIdsAsString = implode(',', $ids);

        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            "
                SELECT product_id, rate, reviews_nb 
                FROM {$prefix}steavisgarantis_average_rating
                WHERE product_id IN ($productIdsAsString)
                AND id_lang = {$context->getLanguageId()}
                ", true, false);

        $indexed = [];
        foreach ($result as $item) {
            $indexed[$item['product_id']] = new Rate(\intval($item['rate']), \intval($item['reviews_nb']));
        }

        return $indexed;
    }
}