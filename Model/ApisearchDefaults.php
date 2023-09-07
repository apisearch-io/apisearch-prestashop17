<?php

namespace Apisearch\Model;

class ApisearchDefaults {
    const DEFAULT_AS_CLUSTER_URL = 'https://eu1.apisearch.cloud';
    const DEFAULT_AS_ADMIN_URL = 'https://static.apisearch.cloud';

    /**
    const DEFAULT_AS_CLUSTER_URL = 'http://localhost:8400';
    const DEFAULT_AS_ADMIN_URL = 'http://localhost:8300';
    */

    const DEFAULT_AS_API_VERSION = 'v1';
    const PLUGIN_NAME = 'apisearch';
    const PLUGIN_VERSION = '2.0.9';

    const DEFAULT_INDEX_PRODUCTS_WITHOUT_IMAGE = false;
    const DEFAULT_REAL_TIME_INDEXATION = false;
    const DEFAULT_AS_INDEX_PRODUCT_PURCHASE_COUNT = false;
    const DEFAULT_AS_INDEX_PRODUCT_NO_STOCK = false;
    const DEFAULT_INDEX_DESCRIPTIONS = false;
    const AS_FIELDS_SUPPLIER_REFERENCES = false;
}
