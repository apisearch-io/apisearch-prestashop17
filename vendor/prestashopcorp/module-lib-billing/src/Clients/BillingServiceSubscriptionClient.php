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
 * BillingServiceSubscriptionClient low level client to access to billing API routes
 */
class BillingServiceSubscriptionClient extends GenericClient
{
    const DEFAULT_API_VERSION = 'v1';

    protected $possibleQueryParameters = [
        'lang_iso_code',
        'status',
        'limit',
        'offset',
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

    /**
     * retrieveCustomerById
     *
     * @param string $customerId the shop id
     *
     * @return array with success (bool), httpStatus (int), body (array) extract from the response
     */
    public function retrieveCustomerById($customerId)
    {
        $this->setRoute('/customers/' . $customerId);

        return $this->get();
    }

    /**
     * Retrieve the subscription of the customer for your module
     *
     * @param string $customerId the shop id
     *
     * @return array with success (bool), httpStatus (int), body (array) extract from the response
     */
    public function retrieveSubscriptionByCustomerId($customerId)
    {
        $this->setRoute('/customers/' . $customerId . '/subscriptions/' . $this->getproductId());

        return $this->get();
    }

    /**
     * @deprecated since 3.0 and will be removed in next major version.
     * @see getBillingApiGatewayClient()->retrieveProductComponents();
     *
     * Retrieve plans associated with the module
     *
     * @param string $lang the lang of the user
     * @param string $status whether you want to get only "active" plan, or the "archived", or both when set to null  (default: "active")
     * @param int $limit number of plan to return (default: "10")
     * @param string $offset pagination start (default: null)
     *
     * @return array with success (bool), httpStatus (int), body (array) extracted from the response
     */
    public function retrievePlans($lang, $status = 'active', $limit = 10, $offset = null)
    {
        $params = [
            'lang_iso_code' => $lang,
            'status' => $status,
            'limit' => $limit,
        ];

        if ($offset) {
            $params['offset'] = $offset;
        }
        $this->setQueryParams($params)
            ->setRoute('/products/' . $this->getproductId() . '/plans');

        return $this->get();
    }
}
