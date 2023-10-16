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

namespace PrestaShopCorp\Billing\Clients;

/**
 * BillingApiGatewayClient low level client to access to billing API Gateway routes
 */
class BillingApiGatewayClient extends GenericClient
{
    const DEFAULT_API_VERSION = 'v1';

    // https://prestashop-billing.stoplight.io/docs/api-gateway/14a1a9da838ee-retrieve-the-components-of-a-product
    protected $possibleQueryParameters = [
        'lang_iso_code',
        'filter_status',
        'filter_component_type',
    ];

    /**
     * Constructor with parameters
     *
     * @param array{moduleName: string, client: null|BillingServiceSubscriptionClient, apiUrl: string, apiVersion: string, isSandbox: bool, token: string} $optionsParameters
     *
     * @return void
     */
    public function __construct($optionsParameters = [])
    {
        parent::__construct($optionsParameters);
    }

    public function retrieveProductComponents()
    {
        $params = [
            'filter_status' => 'active',
        ];

        $this->setQueryParams($params)
            ->setRoute('/products/' . $this->getproductId() . '/components');

        return $this->get();
    }
}
