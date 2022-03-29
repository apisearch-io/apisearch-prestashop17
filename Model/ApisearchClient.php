<?php

namespace Apisearch\Model;

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

use GuzzleHttp\Client;
use GuzzleHttp\Message\Request;

/**
 * Class ApisearchClient
 */
class ApisearchClient
{
    private $host;
    private $version;
    private $appUUID;
    private $indexUUID;
    private $tokenUUID;
    private $client;

    /**
     * Set credentials.
     *
     * @param string $appUUID
     * @param string $indexUUID
     * @param string $tokenUUID
     */
    public function setCredentials(
        $appUUID,
        $indexUUID,
        $tokenUUID
    )
    {
        $this->appUUID = $appUUID;
        $this->indexUUID = $indexUUID;
        $this->tokenUUID = $tokenUUID;
    }

    /**
     * Repository constructor.
     *
     * @param string $host
     * @param string $version
     */
    public function __construct(
        $host,
        $version
    )
    {
        $this->host = $host;
        $this->version = $version;
        $this->client = new Client();
    }

    /**
     * Reset index.
     *
     * @throws \Exception
     */
    public function resetIndex()
    {
        $this->request(
            '/{{app_uuid}}/indices/{{index_uuid}}/reset',
            'POST'
        );
    }

    /**
     * Generate item documents.
     *
     * @param array[] $items
     */
    public function putItems(array $items)
    {
        $this->request(
            '/{{app_uuid}}/indices/{{index_uuid}}/items',
            'PUT',
            [],
            array_values($items)
        );
    }

    /**
     * Delete item documents by uuid.
     *
     * @param array[] $uuids
     */
    public function deleteItems(array $uuids)
    {
        $this->request(
            '/{{app_uuid}}/indices/{{index_uuid}}/items',
            'DELETE',
            [],
            array_values($uuids)
        );
    }

    /**
     * Delete items by query
     *
     * @param array $query
     */
    public function deleteItemsByQuery(array $query)
    {
        return $this->request(
            '/{{app_uuid}}/indices/{{index_uuid}}/items/by-query',
            'DELETE',
            [],
            $query
        );
    }

    /**
     * Compose unique id.
     *
     * @param array $itemUUID
     *
     * @return string
     */
    private function composeUUID(array $itemUUID)
    {
        return $itemUUID['id'] . '~' . $itemUUID['type'];
    }

    /**
     * Make a request and return response.
     *
     * @param string $endpoint
     * @param string $method
     * @param array  $parameters
     * @param array  $body
     *
     * @return string
     *
     * @throws \Exception
     */
    private function request(
        $endpoint,
        $method,
        array $parameters = [],
        array $body = []
    )
    {
        $method = strtoupper($method);
        $endpoint = str_replace([
            '{{app_uuid}}',
            '{{index_uuid}}',
            '{{token_uuid}}',
        ], [
            $this->appUUID,
            $this->indexUUID,
            $this->tokenUUID,
        ], $endpoint);

        $url = sprintf('%s/%s/%s?token=%s',
            rtrim($this->host, '/'),
            trim($this->version, '/'),
            ltrim($endpoint, '/'),
            $this->tokenUUID
        );

        $parameters = array_map('urlencode', $parameters);
        foreach ($parameters as $parameterKey => $parameterValue) {
            $url .= "&$parameterKey=$parameterValue";
        }

        $isPost = 'GET' !== $method;
        $client = $this->client;
        $options = [];
        if ($isPost) {
            $options['json'] = $body;
        }

        $request = $client->createRequest(
            $method,
            $url,
            $options
        );

        $response = $this
            ->client
            ->send($request);

        $statusCode = $response->getStatusCode();
        $data = $response->getBody()->getContents();
        $dataArray = json_decode($data, true);

        if ('2' !== substr($statusCode, 0, 1)) {
            throw new \Exception(
                $dataArray['message'] ?? '',
                $dataArray['code'] ?? 500
            );
        }

        return json_decode($data, true);
    }
}
