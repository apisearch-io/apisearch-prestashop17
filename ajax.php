<?php

/**
 * 2007-2020 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');

if (Tools::getIsset('method') && Tools::getIsset('ajax')) {
    if (Tools::getValue('ajax') == true) {
        switch (Tools::getValue('method')) {
            case 'syncProducts':
                syncProducts();
                die(Tools::jsonEncode(true));
        }
    }
}

function syncProducts()
{
    require_once __DIR__ . '/model/exporter.php';
    require_once __DIR__ . '/model/builder.php';
    require_once __DIR__ . '/model/connection.php';
    require_once __DIR__ . '/apisearch.php';

    $apisearch = new Apisearch();
    $exporter = new Exporter(
        new Builder(function($text) use ($apisearch) {
            return $apisearch->l($text);
        }),
        new Connection()
    );

    $result = $exporter->exportAll();
    var_dump($result[0] . ' products indexed');
    var_dump($result[1] . ' put calls done');
}
