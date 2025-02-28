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

class ApisearchDefaults
{
    const DEFAULT_AS_ADMIN_URL = 'https://static.apisearch.cloud';
    const PLUGIN_NAME = 'apisearch';
    const PLUGIN_VERSION = '2.3.5';
    const DEFAULT_INDEX_PRODUCTS_WITHOUT_IMAGE = false;
    const DEFAULT_AS_INDEX_PRODUCT_PURCHASE_COUNT = true;
    const DEFAULT_AS_INDEX_PRODUCT_NO_STOCK = false;
    const DEFAULT_INDEX_DESCRIPTIONS = true;
    const DEFAULT_INDEX_LONG_DESCRIPTIONS = false;
    const AS_FIELDS_SUPPLIER_REFERENCES = false;
    const AS_SHOW_PRICES_WITHOUT_TAX = false;
    const AS_GROUP_BY_COLOR = false;
    const AS_DEFAULT_IMAGE_TYPE = 'home_default';
    const AS_DEFAULT_ORDER_BY = 'id_desc';
    const AS_REAL_TIME_PRICES = false;
    const AS_GROUPS_SHOW_NO_TAX = '';
}
