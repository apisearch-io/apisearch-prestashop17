<?php

namespace Apisearch\Rates;

use Apisearch\Context;

interface IntegrationRates
{
    /**
     * @return bool
     */
    public static function isValid();

    /**
     * @param Context $context
     * @param array $ids
     * @return Rate[]
     */
    public static function loadRates(Context $context, array $ids);
}