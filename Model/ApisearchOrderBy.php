<?php

namespace Apisearch\Model;

class ApisearchOrderBy
{
    const ORDER_BY = [
        'id_asc' => 'ORDER BY p.id_product ASC',
        'id_desc' => 'ORDER BY p.id_product DESC',
        'stock' => 'ORDER BY st.quantity DESC, p.id_product DESC',
        'sales' => 'ORDER BY sales DESC, p.id_product DESC',
        'updated' => 'ORDER BY p.date_upd DESC, p.id_product DESC',
    ];

    /**
     * @return string
     */
    public static function getCurrentOrderBy()
    {
        $orderBy = \Configuration::get('AS_ORDER_BY');
        if (empty($orderBy) || !array_key_exists($orderBy, ApisearchOrderBy::ORDER_BY)) {
            $orderBy = 'id_asc';
        }

        return $orderBy;
    }

    /**
     * @return string[]
     */
    public static function getCurrentOrderByValue()
    {
        return ApisearchOrderBy::ORDER_BY[ApisearchOrderBy::getCurrentOrderBy()];
    }
}