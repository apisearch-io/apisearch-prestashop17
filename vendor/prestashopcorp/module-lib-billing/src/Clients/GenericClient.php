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

use GuzzleHttp\Psr7\Request;
use Prestashop\ModuleLibGuzzleAdapter\ClientFactory;
use PrestaShopCorp\Billing\Clients\Handler\HttpResponseHandler;
use PrestaShopCorp\Billing\Exception\MissingMandatoryParametersException;
use PrestaShopCorp\Billing\Exception\QueryParamsException;

/**
 * Construct the client used to make call to maasland.
 */
abstract class GenericClient
{
    /**
     * If set to false, you will not be able to catch the error
     * guzzle will show a different error message.
     *
     * @var bool
     */
    protected $catchExceptions = false;

    /**
     * Guzzle Client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Api route.
     *
     * @var string
     */
    protected $route;

    /**
     * Api url.
     *
     * @var string
     */
    protected $apiUrl;

    /**
     * Set how long guzzle will wait a response before end it up.
     *
     * @var int
     */
    protected $timeout = 10;

    /**
     * Version of the API
     *
     * @var string
     */
    protected $apiVersion;

    /**
     * Technical name of the module
     *
     * @var string
     */
    protected $productId;

    /**
     * @var array<string, string>
     */
    protected $queryParameters = [];

    /**
     * @var array<int, string>
     */
    protected $possibleQueryParameters = [];

    protected $mandatoryOptionsParameters = [
        'productId',
        'client',
        'apiUrl',
        'token',
        'isSandbox',
        'apiVersion',
    ];

    protected $possibleOptionsParameters = [
        'productId',
        'client',
        'apiUrl',
        'token',
        'isSandbox',
        'apiVersion',
        'timeout',
        'catchExceptions',
    ];

    /**
     * GenericClient constructor.
     */
    public function __construct($optionsParameters = [])
    {
        $checkededOptionsParams = array_diff_ukey(array_flip($this->mandatoryOptionsParameters), $optionsParameters, 'strcasecmp');
        if (!empty($checkededOptionsParams)) {
            throw new MissingMandatoryParametersException($checkededOptionsParams);
        }

        $filteredOptionsParams = array_intersect_key($optionsParameters, array_flip($this->possibleOptionsParameters));

        extract($filteredOptionsParams, EXTR_PREFIX_SAME, 'generic');

        // Client can be provided for tests or some specific use case
        if (!isset($client) || null === $client) {
            $this->setClientUrl($apiUrl);
            $this->setTimeout(isset($timeout) ? $timeout : $this->getTimeout());
            $this->setCatchExceptions(isset($catchExceptions) ? $catchExceptions : $this->getCatchExceptions());

            $clientParams = [
                'base_url' => $this->getClientUrl(),
                'defaults' => [
                    'timeout' => $this->getTimeout(),
                    'exceptions' => $this->getCatchExceptions(),
                    'headers' => [
                        'Accept' => 'application/json',
                        'Authorization' => 'Bearer ' . (string) $token,
                        'User-Agent' => 'module-lib-billing v3 (' . $productId . ')',
                    ],
                ],
            ];

            if (true === $isSandbox) {
                $clientParams['defaults']['headers']['Sandbox'] = 'true';
            }
            $client = (new ClientFactory())->getClient($clientParams);
        }

        $this->setClient($client)
            ->setproductId($productId)
            ->setApiVersion($apiVersion);
    }

    /**
     * Wrapper of method post from guzzle client.
     *
     * @param array $options payload
     *
     * @return array return response or false if no response
     */
    protected function get($options = [])
    {
        $response = $this->getClient()->sendRequest(new Request('GET', $this->getRoute(), $options));
        $responseHandler = new HttpResponseHandler();

        return $responseHandler->handleResponse($response);
    }

    /**
     * Setter for client.
     *
     * @return void
     */
    protected function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Setter for exceptions mode.
     *
     * @param bool $bool
     *
     * @return void
     */
    protected function setCatchExceptions($bool)
    {
        $this->catchExceptions = (bool) $bool;

        return $this;
    }

    /**
     * Setter for route.
     *
     * @param string $route
     *
     * @return void
     */
    protected function setRoute($route)
    {
        $this->route = $route;
        if ($this->getQueryParameters()) {
            $this->route .= $this->getQueryParameters();
        }

        return $this;
    }

    /**
     * Setter for timeout.
     *
     * @param int $timeout
     *
     * @return void
     */
    protected function setTimeout(int $timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * Setter for apiVersion.
     *
     * @param string $apiVersion
     *
     * @return void
     */
    protected function setApiVersion($apiVersion)
    {
        $this->apiVersion = $apiVersion;

        return $this;
    }

    /**
     * @param array $params
     *
     * @return $this
     */
    protected function setQueryParams(array $params)
    {
        $notAllowedParameters = array_diff_key($params, array_flip($this->possibleQueryParameters));
        if (!empty($notAllowedParameters)) {
            throw new QueryParamsException($notAllowedParameters, $this->possibleQueryParameters);
        }

        $filteredParams = array_intersect_key($params, array_flip($this->possibleQueryParameters));
        $this->queryParameters = '?' . http_build_query(array_merge($this->queryParameters, $filteredParams));

        return $this;
    }

    /**
     * Setter for productId
     *
     * @param string $productId
     *
     * @return void
     */
    protected function setproductId($productId)
    {
        $this->productId = $productId;

        return $this;
    }

    protected function setClientUrl($apiUrl)
    {
        $this->apiUrl = $apiUrl;

        return $this;
    }

    /**
     * Getter for exceptions mode.
     *
     * @return bool
     */
    protected function getCatchExceptions()
    {
        return $this->catchExceptions;
    }

    /**
     * Getter for client.
     *
     * @return ClientInterface
     */
    protected function getClient()
    {
        return $this->client;
    }

    /**
     * Getter for route.
     *
     * @return string
     */
    protected function getRoute()
    {
        if ($this->getApiVersion()) {
            return $this->getApiVersion() . $this->route;
        }

        return $this->route;
    }

    /**
     * Getter for client url.
     *
     * @return string
     */
    protected function getClientUrl()
    {
        return $this->apiUrl;
    }

    /**
     * Getter for full url (client url & route).
     *
     * @return string
     */
    protected function getUrl()
    {
        return $this->getClientUrl() . $this->getRoute();
    }

    /**
     * Getter for timeout.
     *
     * @return int
     */
    protected function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * Getter for apiVersion.
     *
     * @return string
     */
    protected function getApiVersion()
    {
        return $this->apiVersion;
    }

    /**
     * @return array<string, string>
     */
    protected function getQueryParameters()
    {
        return $this->queryParameters;
    }

    /**
     * Getter for productId
     *
     * @return string
     */
    protected function getproductId()
    {
        return $this->productId;
    }
}
