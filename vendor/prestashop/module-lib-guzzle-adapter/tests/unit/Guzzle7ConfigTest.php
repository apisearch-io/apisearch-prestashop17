<?php

use PHPUnit\Framework\TestCase;
use Prestashop\ModuleLibGuzzleAdapter\Guzzle7\Config;

class Guzzle7ConfigTest extends TestCase
{
    public function testConfigWithoutChangeNeeded()
    {
        $originalConfig = [
            'base_uri' => 'http://some-url',
            'verify' => 'path/to/cert',
            'timeout' => 10,
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals($originalConfig, $actualConfig);
    }

    public function testAllConfigKeys()
    {
        $originalConfig = [
            'base_url' => 'http://some-url',
            'verify' => 'path/to/cert',
            'defaults' => [
                'timeout' => 10,
            ],
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'base_uri' => 'http://some-url',
            'verify' => 'path/to/cert',
            'timeout' => 10,
        ], $actualConfig);
    }

    public function testBaseUri()
    {
        $originalConfig = [
            'base_url' => 'http://some-url',
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'base_uri' => 'http://some-url',
        ], $actualConfig);
    }

    public function testTimeout()
    {
        $originalConfig = [
            'defaults' => [
                'timeout' => 10,
            ],
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'timeout' => 10,
        ], $actualConfig);
    }

    public function testExceptions()
    {
        $originalConfig = [
            'defaults' => [
                'exceptions' => true,
            ],
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'http_errors' => true,
        ], $actualConfig);
    }

    public function testAuthorization()
    {
        $originalConfig = [
            'defaults' => [
                'headers' => [
                    'Authorization' => 'Bearer someMegaToken',
                ],
            ],
        ];

        $actualConfig = Config::fixConfig($originalConfig);

        $this->assertEquals([
            'headers' => [
                'Authorization' => 'Bearer someMegaToken',
            ],
        ], $actualConfig);
    }
}
