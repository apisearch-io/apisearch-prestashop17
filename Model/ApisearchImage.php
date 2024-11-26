<?php

namespace Apisearch\Model;

class ApisearchImage
{
    public static function getImageTypes()
    {
        $imageTypes = \Db::getInstance()->executeS('
            SELECT name
            FROM `'._DB_PREFIX_.'image_type`
            WHERE products = 1
        ');

        return array_values(array_filter(array_unique(array_map(function ($type) {
            return $type['name'];
        }, $imageTypes))));
    }

    public static function getCurrentImageType()
    {
        $imageType = \Configuration::get('AS_IMAGE_FORMAT');
        if (empty($imageType)) {
            $imageType = ApisearchDefaults::AS_DEFAULT_IMAGE_TYPE;
        }

        return $imageType;
    }
}