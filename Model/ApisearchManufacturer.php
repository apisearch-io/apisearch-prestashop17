<?php

namespace Apisearch\Model;

class ApisearchManufacturer
{
    private static $manufacturers = array();

    /**
     * @param $manufacturersId
     * @return array
     */
    public static function getManufacturers($manufacturersId)
    {
        if (empty($manufacturersId)) {
            return [];
        }

        $missingManufacturersId = [];
        $alreadyLoadedManufacturers = [];
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

        $manufacturers = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($manufacturers as $manufacturer) {
            self::$manufacturers[$manufacturer['id_manufacturer']] = $manufacturer['active'] === 1
                ? null
                : $manufacturer;
            $alreadyLoadedManufacturers[$manufacturer['id_manufacturer']] = $manufacturer;
        }

        return $alreadyLoadedManufacturers;
    }
}
