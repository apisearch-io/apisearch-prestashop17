<?php

use PHPUnit\Framework\TestCase;
use Prestashop\ModuleLibGuzzleAdapter\Guzzle5\Config;

class Guzzle5ConfigTest extends TestCase
{
    public function testConfigWithoutChangeNeeded()
    {
        $originalConfig = [
            'base_url' => 'http://some-url',
            'verify' => 'path/to/cert',
            'defaults' => [
                'timeout' => 10,
            ],
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals($originalConfig, $actualConfig);
    }

    public function testAllConfigKeys()
    {
        $originalConfig = [
            'base_uri' => 'http://some-url',
            'verify' => 'path/to/cert',
            'timeout' => 10,
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'base_url' => 'http://some-url',
            'verify' => 'path/to/cert',
            'defaults' => [
                'timeout' => 10,
            ],
        ], $actualConfig);
    }

    public function testBaseUri()
    {
        $originalConfig = [
            'base_uri' => 'http://some-url',
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'base_url' => 'http://some-url',
        ], $actualConfig);
    }

    public function testTimeout()
    {
        $originalConfig = [
            'timeout' => 10,
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'defaults' => [
                'timeout' => 10,
            ],
        ], $actualConfig);
    }

    public function testExceptions()
    {
        $originalConfig = [
            'http_errors' => true,
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'defaults' => [
                'exceptions' => true,
            ],
        ], $actualConfig);
    }

    public function testAuthorization()
    {
        $originalConfig = [
            'headers' => [
                'Authorization' => 'Bearer someMegaToken',
            ],
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'defaults' => [
                'headers' => [
                    'Authorization' => 'Bearer someMegaToken',
                ],
            ],
        ], $actualConfig);
    }
}
