<?php

namespace PrestaShopCorp\Billing\Tests\Unit\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Psr7\Response as Psr7Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use PHPUnit\Framework\TestCase;
use Prestashop\ModuleLibGuzzleAdapter\Interfaces\HttpClientInterface;
use PrestaShopCorp\Billing\Clients\BillingApiGatewayClient;
use Psr\Http\Message\RequestInterface;

class testApiGatewayClient extends Client implements HttpClientInterface
{
    public static $response;

    public function __construct($configClient, $mockResponse)
    {
        parent::__construct($configClient);
        testApiGatewayClient::$response = $mockResponse;
    }

    public function sendRequest(RequestInterface $request)
    {
        return new Psr7Response(200, [], json_encode(testApiGatewayClient::$response));
    }
}

class BillingApiGatewayClientTest extends TestCase
{
    protected $componentPlan;
    protected $componentAddon;

    protected function setUp()
    {
        parent::setUp();

        $this->componentPlan = [
            'items' => [[
                'id' => 'rbm-advanced-EUR-Monthly',
                'productId' => 'rbm_example',
                'componentType' => 'plan',
                'status' => 'active',
                'isUsageBased' => false,
                'pricingModel' => 'flat_fee',
                'price' => 1000,
                'tiers' => [],
                'billingPeriodUnit' => 'month',
                'trialPeriodValue' => 10,
                'trialPeriodUnit' => 'day',
                'freeQuantity' => 0,
                'mandatoryComponentIds' => [],
            ],
            [
                'id' => 'rbm-free-GBP-Monthly',
                'productId' => 'rbm_example',
                'componentType' => 'plan',
                'status' => 'active',
                'isUsageBased' => false,
                'pricingModel' => 'flat_fee',
                'price' => 300,
                'tiers' => [],
                'billingPeriodUnit' => 'month',
                'freeQuantity' => 0,
                'mandatoryComponentIds' => [],
            ], ],
          'total' => 2,
        ];

        $this->componentAddon = [];
    }

    public function testRetrieveProductComponentsPlan()
    {
        $billingClient = $this->getBillingApiGatewayClient($this->componentPlan);
        $resultPlan = $billingClient->retrieveProductComponents();

        // Test the format and the content
        $this->assertEquals($resultPlan['success'], true);
        $this->assertEquals($resultPlan['httpStatus'], 200);
        $this->assertEquals($resultPlan['body'], $this->componentPlan);
    }

    /**
     * getBillingApiGatewayClient
     *
     * @param $mockData
     *
     * @return BillingApiGatewayClient
     */
    private function getBillingApiGatewayClient($mockData)
    {
        $response = new Response(200, [], Stream::factory(json_encode($mockData)));
        $mock = new Mock([
            $response,
        ]);

        $client = new testApiGatewayClient([
            'base_url' => 'http://localhost/',
            'defaults' => [
                'timeout' => 20,
                'exceptions' => true,
                'headers' => [
                    'Accept' => 'application/json',
                    'Authorization' => 'Bearer token',
                ],
            ],
        ], $mockData);

        $client->getEmitter()->attach($mock);

        return new BillingApiGatewayClient([
            'client' => $client,
            'productId' => 'rbm_example',
            'apiVersion' => BillingApiGatewayClient::DEFAULT_API_VERSION,
            'apiUrl' => 'http://localhost/',
            'token' => 'token',
            'isSandbox' => false,
        ]);
    }
}
