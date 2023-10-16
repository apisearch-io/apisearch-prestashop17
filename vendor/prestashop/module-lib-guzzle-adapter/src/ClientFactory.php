<?php

namespace Prestashop\ModuleLibGuzzleAdapter;

use Prestashop\ModuleLibGuzzleAdapter\Guzzle5\Client as Guzzle5Client;
use Prestashop\ModuleLibGuzzleAdapter\Guzzle5\Config as Guzzle5Config;
use Prestashop\ModuleLibGuzzleAdapter\Guzzle7\Client as Guzzle7Client;
use Prestashop\ModuleLibGuzzleAdapter\Guzzle7\Config as Guzzle7Config;

class ClientFactory
{
    /**
     * @var VersionDetection
     */
    private $versionDetection;

    public function __construct(VersionDetection $versionDetection = null)
    {
        $this->versionDetection = $versionDetection ?: new VersionDetection();
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return \Prestashop\ModuleLibGuzzleAdapter\Interfaces\HttpClientInterface
     */
    public function getClient(array $config = [])
    {
        return $this->initClient($config);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return \Prestashop\ModuleLibGuzzleAdapter\Interfaces\HttpClientInterface
     */
    private function initClient(array $config = [])
    {
        if ($this->versionDetection->getGuzzleMajorVersionNumber() >= 7) {
            return Guzzle7Client::createWithConfig(
                Guzzle7Config::fixConfig($config)
            );
        }

        return Guzzle5Client::createWithConfig(
            Guzzle5Config::fixConfig($config)
        );
    }
}
