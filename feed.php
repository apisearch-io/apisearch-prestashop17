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
set_time_limit(1800);

/**
 * We suppress all possible incoming output data to avoid malformed feed
 */
ob_start();

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');
require_once __DIR__.'/vendor/autoload.php';

use Apisearch\Model\ApisearchExporter;
use Apisearch\Model\ApisearchBuilder;
use Apisearch\Context;
use Apisearch\Rates\Rating;

require_once __DIR__ . '/apisearch.php';

ob_end_clean();
header('Content-Type:text/plain; charset=utf-8');

try {
    Rating::load();
    $exporter = new ApisearchExporter(new ApisearchBuilder());

    $context = Context::fromUrl();
    Context::updatePrestashopContext($context);
    $exporter->printItemsByShopAndLang($context);

} catch (\Throwable $exception) {
    http_response_code(404);
    echo json_encode([
        'error' => 1,
        'message' => $exception->getMessage(),
        'line' => $exception->getLine(),
        'file' => $exception->getFile(),
        'trace' => $exception->getTraceAsString(),
    ]);
}
