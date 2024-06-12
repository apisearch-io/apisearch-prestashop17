<?php

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