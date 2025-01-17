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

class ApisearchImage
{
    public static function getImageTypes()
    {
        $imageTypes = \Db::getInstance()->executeS('
            SELECT name
            FROM `'._DB_PREFIX_.'image_type`
            WHERE products = 1
        ');

        $values = array_values(array_filter(array_unique(array_map(function ($type) {
            return $type['name'];
        }, $imageTypes))));

        if (in_array('home_default', $values)) {
            $values = array_flip($values);
            unset($values['home_default']);
            $values = array_flip($values);
            array_unshift($values, 'home_default');
        }

        return $values;
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