<?php

class ASSupplier
{
    private static $suppliers = array();

    /**
     * @param $suppliersId
     * @return array
     */
    public static function getSuppliers($suppliersId)
    {
        if (empty($suppliersId)) {
            return [];
        }

        $missingSuppliersId = [];
        $alreadyLoadedSuppliers = [];
        $suppliersId = array_unique($suppliersId);

        foreach ($suppliersId as $supplierId) {
            if (array_key_exists($supplierId, self::$suppliers)) {
                $alreadyLoadedSuppliers[$supplierId] = self::$suppliers[$supplierId];
            } else {
                $missingSuppliersId[] = $supplierId;
            }
        }

        if (empty($missingSuppliersId)) {
            return $alreadyLoadedSuppliers;
        }

        $missingSuppliersIdAsString = implode(',', $missingSuppliersId);
        $prefix = _DB_PREFIX_;

        $sql = "SELECT `name`, active, id_supplier
            FROM `{$prefix}supplier`
            WHERE `id_supplier` in ($missingSuppliersIdAsString)";

        $suppliers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        foreach ($suppliers as $supplier) {
            self::$suppliers[$supplier['id_supplier']] = $supplier['active'] === 1
                ? null
                : $supplier;
            $alreadyLoadedSuppliers[$supplier['id_supplier']] = $supplier;
        }

        return $alreadyLoadedSuppliers;
    }
}
