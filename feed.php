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
