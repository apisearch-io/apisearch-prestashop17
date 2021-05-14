<?php

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
    }

    /**
     * Reset index.
     *
     * @throws Exception
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
     * @throws Exception
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

        if ('GET' !== $method) {
            $data = json_encode($body);
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'ignore_errors' => true,
                    'header' => "Content-type: application/json\r\n".
                        "Accept: application/json\r\n".
                        "Connection: close\r\n".
                        'Content-length: '.strlen($data)."\r\n",
                    'content' => $data,
                ],
            ]);

            $data = file_get_contents($url, false, $context);
        } else {
            $data = file_get_contents($url);
        }

        $code = $this->parseResponseStatusCode($http_response_header['0']);

        if ('2' !== substr($code, 0, 1)) {
            $dataArray = json_decode($data, true);
            throw new Exception($dataArray['message'] ?? '', $dataArray['code'] ?? 500);
        }

        return json_decode($data, true);
    }

    /**
     * Parse response header and return value.
     *
     * @param string $header
     *
     * @return int
     */
    private function parseResponseStatusCode($header)
    {
        try {
            list(, $code, $status) = explode(' ', $header, 3);

            return (int)$code;
        } catch (Exception $exception) {
            // Silent pass
        }

        return null;
    }
}
