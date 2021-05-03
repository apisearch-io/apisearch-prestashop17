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
            'put',
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
            'delete',
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
            'delete',
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

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if ('get' !== $method) {
            $data = json_encode($body, JSON_PRESERVE_ZERO_FRACTION);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new Exception($err);
            return;
        }

        return json_decode($response, true);
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
