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

class ApisearchManufacturer
{
    private static $manufacturers = array();

    /**
     * @param $manufacturersId
     * @return array
     */
    public static function getManufacturers(
        $manufacturersId,
        Context $context
    )
    {
        if (empty($manufacturersId)) {
            return [];
        }

        $missingManufacturersId = [];
        $alreadyLoadedManufacturers = [];
        $manufacturersId = array_filter($manufacturersId);
        $manufacturersId = array_unique($manufacturersId);

        foreach ($manufacturersId as $manufacturerId) {
            if (array_key_exists($manufacturerId, self::$manufacturers)) {
                $alreadyLoadedManufacturers[$manufacturerId] = self::$manufacturers[$manufacturerId];
            } else {
                $missingManufacturersId[] = $manufacturerId;
            }
        }

        if (empty($missingManufacturersId)) {
            return $alreadyLoadedManufacturers;
        }

        $missingManufacturersIdAsString = implode(',', $missingManufacturersId);
        $prefix = _DB_PREFIX_;

        $sql = "SELECT `name`, active, id_manufacturer
            FROM `{$prefix}manufacturer`
            WHERE `id_manufacturer` in ($missingManufacturersIdAsString)";

        if ($context->isDebug()) {
            echo json_encode([
                'debug' => 'sql manufacturers',
                'sql' => $sql,
            ]);
            echo PHP_EOL;
            ob_flush();
        }

        $manufacturers = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql, true, false);
        foreach ($manufacturers as $manufacturer) {
            $manufacturerIsActive = strval($manufacturer['active']) === "1";
            $idManufacturer = $manufacturer['id_manufacturer'];
            $manufacturerData = $manufacturerIsActive ? $manufacturer : null;
            self::$manufacturers[$idManufacturer] = $manufacturerData;
            $alreadyLoadedManufacturers[$idManufacturer] = $manufacturerData;
        }

        return $alreadyLoadedManufacturers;
    }
}
