<?php

namespace Apisearch\Rates;

use Apisearch\Context;

class Rating
{
    /**
     * @var IntegrationRates
     */
    private static $ratingService = null;

    public static function load()
    {
        $all = [
            SteavisgarantisRates::class
        ];

        foreach ($all as $integration) {
            if ($integration::isValid()) {
                self::$ratingService = $integration;
            }
        }
    }

    public static function getRatings(Context $context, array $ids)
    {
        if (!self::$ratingService) {
            return [];
        }

        return self::$ratingService::loadRates($context, $ids);
    }
}