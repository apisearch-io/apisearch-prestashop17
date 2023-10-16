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

namespace PrestaShopCorp\Billing\Wrappers;

use PrestaShop\PsAccountsInstaller\Installer\Facade\PsAccounts;
use PrestaShopCorp\Billing\Config\Config;

class BillingContextWrapper
{
    /**
     * @var PsAccounts
     */
    private $psAccountsService;

    /**
     * @var \Context
     */
    private $context;

    /**
     * Indicate whether you want to work with sandbox or not
     *
     * @var bool
     */
    private $sandbox;

    /**
     * Indicate whether you want to work with sandbox or not
     *
     * @var bool
     */
    private $billingEnv;

    public function __construct(
        $accountFacade = null,
        $context = null,
        $sandbox = false,
        $billingEnv = null
    ) {
        if (null === $context) {
            $context = \Context::getContext();
        }
        $this->setContext($context);
        $this->psAccountsService = $accountFacade ? $accountFacade->getPsAccountsService() : \Module::getInstanceByName(Config::PS_ACCOUNTS_MODULE_NAME)->getService(Config::PS_ACCOUNTS_SERVICE);

        $this->setSandbox($sandbox);
        $this->setBillingEnv($billingEnv);
    }

    /**
     * Get the isoCode from the context language, if null, send 'en' as default value
     *
     * @return string
     */
    public function getLanguageIsoCode()
    {
        return $this->getContext()->language !== null ? $this->getContext()->language->iso_code : Config::I18N_FALLBACK_LOCALE;
    }

    /**
     * @return string|false
     */
    public function getShopUuid()
    {
        return method_exists($this->getPsAccountService(), 'getShopUuid') ? $this->getPsAccountService()->getShopUuid() : $this->getPsAccountService()->getShopUuidV4();
    }

    /**
     * Get the refresh token for connected user.
     *
     * @return string|null
     */
    public function getRefreshToken()
    {
        return $this->getPsAccountService()->getRefreshToken();
    }

    /**
     * Get the refresh token for connected user.
     *
     * @return string|null
     */
    public function getAccessToken()
    {
        return $this->getPsAccountService()->getOrRefreshToken();
    }

    /**
     * Get the email for connected ueser.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getPsAccountService()->getEmail();
    }

    /**
     * Get the uuid of the organization.
     *
     * @return string|null
     */
    public function getOrganizationUuid()
    {
        return $this->getPsAccountService()->getUserUuid();
    }

    /**
     * Get the domain of the shop.
     *
     * @return string|null
     */
    public function getShopDomain()
    {
        return \Tools::getShopDomain();
    }

    /**
     * getSandbox
     *
     * @return bool
     */
    public function isSandbox()
    {
        return $this->sandbox;
    }

    /**
     * getBillingEnv
     *
     * @return string
     */
    public function getBillingEnv()
    {
        return $this->billingEnv;
    }

    /**
     * Get the psAccountService
     *
     * @return PsAccounts
     */
    private function getPsAccountService()
    {
        return $this->psAccountsService;
    }

    /**
     * setContext
     *
     * @param \Context $context
     *
     * @return void
     */
    private function setContext(\Context $context)
    {
        $this->context = $context;
    }

    /**
     * getContext
     *
     * @return \Context
     */
    private function getContext()
    {
        return $this->context;
    }

    /**
     * setSandbox
     *
     * @param bool $sandbox
     *
     * @return void
     */
    private function setSandbox(bool $sandbox)
    {
        $this->sandbox = $sandbox;
    }

    /**
     * setBillingEnv
     *
     * @param string|null $billingEnv
     *
     * @return void
     */
    private function setBillingEnv($billingEnv)
    {
        $this->billingEnv = $billingEnv;
    }
}
