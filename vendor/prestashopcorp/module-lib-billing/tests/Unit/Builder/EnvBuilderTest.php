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
use PrestaShopCorp\Billing\Builder\EnvBuilder;

class EnvBuilderTest extends TestCase
{
    public function testBuildUIUrl()
    {
        $builder = new EnvBuilder();
        $this->assertEquals($builder->buildBillingEnv('development'), null);
        $this->assertEquals($builder->buildBillingEnv('integration'), 'integration');
        $this->assertEquals($builder->buildBillingEnv('prestabulle1'), 'prestabulle1');
        $this->assertEquals($builder->buildBillingEnv('prestabulle2'), 'prestabulle2');
        $this->assertEquals($builder->buildBillingEnv('prestabulle3'), 'prestabulle3');
        $this->assertEquals($builder->buildBillingEnv('prestabulle4'), 'prestabulle4');
        $this->assertEquals($builder->buildBillingEnv('prestabulle5'), 'prestabulle5');
        $this->assertEquals($builder->buildBillingEnv('prestabulle6'), 'prestabulle6');
        $this->assertEquals($builder->buildBillingEnv('preprod'), 'preprod');
        $this->assertEquals($builder->buildBillingEnv('unknown'), 'production');
        $this->assertEquals($builder->buildBillingEnv(''), 'production');
    }
}
