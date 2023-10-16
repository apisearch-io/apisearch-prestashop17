<?php
/**
 * 2007-2020 PrestaShop and Contributors.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PsAccountsInstaller\Tests\Unit\Installer\Presenter\InstallerPresenter;

use PrestaShop\PsAccountsInstaller\Installer\Installer;
use PrestaShop\PsAccountsInstaller\Installer\Presenter\InstallerPresenter;
use PrestaShop\PsAccountsInstaller\Tests\TestCase;

class PresentTest extends TestCase
{
    /**
     * @test
     *
     * @throws \Exception
     */
    public function itShouldBeCompliantWithPsAccountsPresenter()
    {
        $expected = [
            'psIs17' => $this->faker->boolean,
            'psAccountsInstallLink' => $this->faker->url,
            'psAccountsEnableLink' => $this->faker->url,
            'psAccountsUpdateLink' => $this->faker->url,
            'psAccountsIsInstalled' => $this->faker->boolean,
            'psAccountsIsEnabled' => $this->faker->boolean,
            'psAccountsIsUptodate' => $this->faker->boolean,
            'onboardingLink' => null,
            'user' => [
                'email' => null,
                'emailIsValidated' => false,
                'isSuperAdmin' => true,
            ],
            'currentShop' => null,
            'shops' => [],
            'superAdminEmail' => null,
            'ssoResendVerificationEmail' => null,
            'manageAccountLink' => null,
        ];

        $installer = $this->createConfiguredMock(Installer::class, [
            'isShopVersion17' => $expected['psIs17'],
            'isModuleInstalled' => $expected['psAccountsIsInstalled'],
            'getInstallLink' => $expected['psAccountsInstallLink'],
            'isModuleEnabled' => $expected['psAccountsIsEnabled'],
            'getEnableLink' => $expected['psAccountsEnableLink'],
            'checkModuleVersion' => $expected['psAccountsIsUptodate'],
            'getUpgradeLink' => $expected['psAccountsUpdateLink'],
        ]);

        $presenter = $this->getMockBuilder(InstallerPresenter::class)
            //->disableOriginalConstructor()
            ->setConstructorArgs([$installer, new \Context()])
            ->disableOriginalClone()
            ->disableArgumentCloning()
            ->disallowMockingUnknownTypes()
            ->setMethods([
                'isEmployeeSuperAdmin',
            ])
            ->getMock();

        $presenter->method('isEmployeeSuperAdmin')
            ->willReturn(true);

        /** @var InstallerPresenter $presenter */
        $presenterData = $presenter->present();

        $this->assertArraySubset($expected, $presenterData);
    }
}
