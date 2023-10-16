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

namespace PrestaShopCorp\Billing\Tests\Unit\Builder;

use PHPUnit\Framework\TestCase;
use PrestaShopCorp\Billing\Builder\UrlBuilder;

class UrlBuilderTest extends TestCase
{
    public function testBuildUIUrl()
    {
        $builder = new UrlBuilder('development');
        $this->assertEquals($builder->buildUIUrl(), null);

        $builder = new UrlBuilder('integration');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle1');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing-prestabulle1.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle2');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing-prestabulle2.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle3');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing-prestabulle3.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle4');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing-prestabulle4.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle5');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing-prestabulle5.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle6');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing-prestabulle6.distribution-integration.prestashop.net');

        $builder = new UrlBuilder('preprod');
        $this->assertEquals($builder->buildUIUrl(), 'https://billing.distribution-preprod.prestashop.net');
        $builder = new UrlBuilder();
        $this->assertEquals($builder->buildUIUrl(), 'https://billing.distribution.prestashop.net');
    }

    public function testBuildAPIUrl()
    {
        $builder = new UrlBuilder('development');
        $this->assertEquals($builder->buildAPIUrl('development'), null);
        $builder = new UrlBuilder('development', 'https://www.w3schoo��ls.co�m');
        $this->assertEquals($builder->buildAPIUrl(), 'https://www.w3schools.com');
        $builder = new UrlBuilder('development', 'https://www.w3schools.com');
        $this->assertEquals($builder->buildAPIUrl(), 'https://www.w3schools.com');

        $builder = new UrlBuilder('integration');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle1');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api-psbulle1.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle2');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api-psbulle2.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle3');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api-psbulle3.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle4');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api-psbulle4.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle5');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api-psbulle5.distribution-integration.prestashop.net');
        $builder = new UrlBuilder('prestabulle6');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api-psbulle6.distribution-integration.prestashop.net');

        $builder = new UrlBuilder('preprod');
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api.distribution-preprod.prestashop.net');
        $builder = new UrlBuilder();
        $this->assertEquals($builder->buildAPIUrl(), 'https://billing-api.distribution.prestashop.net');
    }

    public function testBuildAPIGatewayUrl()
    {
        $builder = new UrlBuilder('development');
        $this->assertEquals($builder->buildAPIGatewayUrl('development'), null);
        $builder = new UrlBuilder('development', 'https://www.w3schoo��ls.co�m');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://www.w3schools.com');
        $builder = new UrlBuilder('development', 'https://www.w3schools.com');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://www.w3schools.com');

        $builder = new UrlBuilder('prestabulle1');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-prestabulle1.prestashop.com');
        $builder = new UrlBuilder('prestabulle2');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-prestabulle2.prestashop.com');
        $builder = new UrlBuilder('prestabulle3');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-prestabulle3.prestashop.com');
        $builder = new UrlBuilder('prestabulle4');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-prestabulle4.prestashop.com');
        $builder = new UrlBuilder('prestabulle5');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-prestabulle5.prestashop.com');
        $builder = new UrlBuilder('prestabulle6');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-prestabulle6.prestashop.com');

        $builder = new UrlBuilder('preprod');
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://billing-api-gateway-preprod.prestashop.com');
        $builder = new UrlBuilder();
        $this->assertEquals($builder->buildAPIGatewayUrl(), 'https://api.billing.prestashop.com');
    }
}
