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