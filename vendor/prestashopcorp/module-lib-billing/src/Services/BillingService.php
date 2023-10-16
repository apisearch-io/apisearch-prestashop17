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

namespace PrestaShopCorp\Billing\Services;

use Module;
use PrestaShopCorp\Billing\Builder\UrlBuilder;
use PrestaShopCorp\Billing\Clients\BillingApiGatewayClient;
use PrestaShopCorp\Billing\Clients\BillingServiceSubscriptionClient;
use PrestaShopCorp\Billing\Wrappers\BillingContextWrapper;

class BillingService
{
    /**
     * Created to make billing API request
     *
     * @var BillingServiceSubscriptionClient
     */
    private $billingServiceSubscriptionClient;

    /**
     * Created to make billing API request
     *
     * @var BillingApiGatewayClient
     */
    private $billingApiGatewayClient;

    /**
     * @var BillingContextWrapper
     */
    private $billingContextWrapper;

    /**
     * @var Module
     */
    private $module;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /*
        If you want to specify your own API URL you should edit the common.yml
        file with the following code

        ps_billings.context_wrapper:
        class: 'PrestaShopCorp\Billing\Wrappers\BillingContextWrapper'
        public: false
        arguments:
            - '@ps_accounts.facade'
            - '@rbm_example.context'
            - true # if true you are in sandbox mode, if false or empty not in sandbox
            - 'development'

        ps_billings.service:
        class: PrestaShopCorp\Billing\Services\BillingService
        public: true
        arguments:
            - '@ps_billings.context_wrapper'
            - '@rbm_example.module'
            - 'v1'
            - 'http://host.docker.internal:3000'
    */
    public function __construct(
        $billingContextWrapper = null,
        $module = null,
        $apiVersion = null,
        $apiUrl = null
    ) {
        $this->setBillingContextWrapper($billingContextWrapper)
            ->setUrlBuilder(new UrlBuilder($this->getBillingContextWrapper()->getBillingEnv(), $apiUrl))
            ->setModule($module);

        $this->setBillingServiceSubscriptionClient(new BillingServiceSubscriptionClient([
            'client' => null,
            'productId' => $this->getModule()->name,
            'apiUrl' => $this->getUrlBuilder()->buildAPIUrl(),
            'apiVersion' => $apiVersion ? $apiVersion : BillingServiceSubscriptionClient::DEFAULT_API_VERSION,
            'token' => $this->getBillingContextWrapper()->getAccessToken(),
            'isSandbox' => $this->getBillingContextWrapper()->isSandbox(),
        ]));

        $this->setBillingApiGatewayClient(new BillingApiGatewayClient([
            'client' => null,
            'productId' => $this->getModule()->name,
            'apiUrl' => $this->getUrlBuilder()->buildAPIGatewayUrl(),
            'apiVersion' => $apiVersion ? $apiVersion : BillingApiGatewayClient::DEFAULT_API_VERSION,
            'token' => $this->getBillingContextWrapper()->getAccessToken(),
            'isSandbox' => $this->getBillingContextWrapper()->isSandbox(),
        ]));
    }

    /**
     * Retrieve the Billing customer associated with the shop
     * on which your module is installed
     *
     * @return array
     */
    public function getCurrentCustomer()
    {
        return $this->getBillingServiceSubscriptionClient()->retrieveCustomerById($this->getBillingContextWrapper()->getShopUuid());
    }

    /**
     * Retrieve the Billing subscription associated with the shop
     * on which your module is installed
     *
     * @return array
     */
    public function getCurrentSubscription()
    {
        return $this->getBillingServiceSubscriptionClient()->retrieveSubscriptionByCustomerId($this->getBillingContextWrapper()->getShopUuid());
    }

    /**
     * @deprecated since 3.0 and will be removed in next major version.
     * @see getProductComponents()
     *
     * Retrieve the plans associated to this module
     *
     * @return array
     */
    public function getModulePlans()
    {
        @trigger_error(
            sprintf(
                '%s is deprecated since version 3.0. Use %s instead.',
                __METHOD__,
                BillingService::class . '->getProductComponents()'
            ),
            E_USER_DEPRECATED
        );

        \Tools::displayError(sprintf(
            '%s is deprecated since version 3.0. Use %s instead.',
            __METHOD__,
            BillingService::class . '->getProductComponents()'
        ));

        return $this->getBillingServiceSubscriptionClient()->retrievePlans($this->getBillingContextWrapper()->getLanguageIsoCode());
    }

    /**
     * Retrieve product components associated to this module
     *
     * @return array
     */
    public function getProductComponents()
    {
        return $this->getBillingApiGatewayClient()->retrieveProductComponents();
    }

    /**
     * setModule
     *
     * @param string $module
     *
     * @return void
     */
    private function setModule($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * getModule
     *
     * @return Module
     */
    private function getModule()
    {
        return $this->module;
    }

    /**
     * setUrlBuilder
     *
     * @param string $urlBuilder
     *
     * @return void
     */
    private function setUrlBuilder($urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;

        return $this;
    }

    /**
     * getUrlBuilder
     *
     * @return UrlBuilder
     */
    private function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * setBillingServiceSubscriptionClient
     *
     * @param BillingServiceSubscriptionClient $billingClient
     *
     * @return void
     */
    private function setBillingServiceSubscriptionClient($billingClient)
    {
        $this->billingServiceSubscriptionClient = $billingClient;
    }

    /**
     * getBillingServiceSubscriptionClient
     *
     * @return BillingServiceSubscriptionClient
     */
    private function getBillingServiceSubscriptionClient()
    {
        return $this->billingServiceSubscriptionClient;
    }

    /**
     * setBillingApiGatewayClient
     *
     * @param BillingApiGatewayClient $billingClient
     *
     * @return void
     */
    private function setBillingApiGatewayClient($billingClient)
    {
        $this->billingApiGatewayClient = $billingClient;
    }

    /**
     * getBillingApiGatewayClient
     *
     * @return BillingApiGatewayClient
     */
    private function getBillingApiGatewayClient()
    {
        return $this->billingApiGatewayClient;
    }

    /**
     * setBillingContextWrapper
     *
     * @param BillingContextWrapper $billingContextWrapper
     *
     * @return void
     */
    private function setBillingContextWrapper(BillingContextWrapper $billingContextWrapper)
    {
        $this->billingContextWrapper = $billingContextWrapper;

        return $this;
    }

    /**
     * getBillingContextWrapper
     *
     * @return BillingContextWrapper
     */
    private function getBillingContextWrapper()
    {
        return $this->billingContextWrapper;
    }
}
