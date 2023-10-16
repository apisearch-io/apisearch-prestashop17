<?php

/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace PrestaShopCorp\Billing\Builder;

class UrlBuilder
{
    private $envName = null;
    private $apiUrl = null;

    public function __construct($envName = null, $apiUrl = null)
    {
        $this->envName = $envName;
        $this->apiUrl = $apiUrl;
    }

    /**
     * buildUIUrl
     *
     * @return string
     */
    public function buildUIUrl()
    {
        switch ($this->getEnvName()) {
            case 'development':
                // Handle by .env in Billing UI
                return null;
            case 'integration':
                return 'https://billing.distribution-' . $this->getEnvName() . '.prestashop.net';
                break;
            case 'prestabulle1':
            case 'prestabulle2':
            case 'prestabulle3':
            case 'prestabulle4':
            case 'prestabulle5':
            case 'prestabulle6':
                return 'https://billing-' . $this->getEnvName() . '.distribution-integration.prestashop.net';
                break;
            case 'preprod':
                return 'https://billing.distribution-' . $this->getEnvName() . '.prestashop.net';
                break;
            default:
                return 'https://billing.distribution.prestashop.net';
        }
    }

    /**
     * buildAPIUrl
     *
     * @return string
     */
    public function buildAPIUrl()
    {
        switch ($this->getEnvName()) {
            case 'development':
                return $this->getApiUrl() ? filter_var($this->getApiUrl(), FILTER_SANITIZE_URL) : null;
            case 'integration':
                return 'https://billing-api.distribution-' . $this->getEnvName() . '.prestashop.net';
                break;
            case 'prestabulle1':
            case 'prestabulle2':
            case 'prestabulle3':
            case 'prestabulle4':
            case 'prestabulle5':
            case 'prestabulle6':
                return 'https://billing-api-' . str_replace('prestabulle', 'psbulle', $this->getEnvName()) . '.distribution-integration.prestashop.net';
                break;
            case 'preprod':
                return 'https://billing-api.distribution-' . $this->getEnvName() . '.prestashop.net';
                break;
            default:
                return 'https://billing-api.distribution.prestashop.net';
        }
    }

    /**
     * buildAPIGatewayUrl
     *
     * @return string
     */
    public function buildAPIGatewayUrl()
    {
        switch ($this->getEnvName()) {
            case 'development':
                return $this->getApiUrl() ? filter_var($this->getApiUrl(), FILTER_SANITIZE_URL) : null;
            case 'prestabulle1':
            case 'prestabulle2':
            case 'prestabulle3':
            case 'prestabulle4':
            case 'prestabulle5':
            case 'prestabulle6':
            case 'preprod':
                return 'https://billing-api-gateway-' . $this->getEnvName() . '.prestashop.com';
                break;
            default:
                return 'https://api.billing.prestashop.com';
        }
    }

    /**
     * getEnvName
     *
     * @return string
     */
    private function getEnvName()
    {
        return $this->envName;
    }

    /**
     * getApiUrl
     *
     * @return string
     */
    private function getApiUrl()
    {
        return $this->apiUrl;
    }
}
