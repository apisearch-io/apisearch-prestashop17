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

namespace PrestaShopCorp\Billing\Presenter;

use Module;
use PrestaShopCorp\Billing\Builder\EnvBuilder;
use PrestaShopCorp\Billing\Builder\UrlBuilder;
use PrestaShopCorp\Billing\Exception\BillingContextException;
use PrestaShopCorp\Billing\Wrappers\BillingContextWrapper;

class BillingPresenter
{
    const CONTEXT_VERSION = 2;

    /**
     * @var EnvBuilder
     */
    private $envBuilder;

    /**
     * @var UrlBuilder
     */
    private $urlBuilder;

    /**
     * @var BillingContextWrapper
     */
    private $billingContextWrapper;

    /**
     * @var \Module
     */
    private $module;

    /**
     * Presenter constructor.
     *
     * @param \Module $module
     * @param PsAccounts $accountFacade
     * @param \Context|null $context
     */
    public function __construct(
        BillingContextWrapper $billingContextWrapper = null,
        Module $module
    ) {
        $this->setModule($module);

        $this->setEnvBuilder(new EnvBuilder());
        $this->setUrlBuilder(new UrlBuilder());

        $this->setBillingContextWrapper($billingContextWrapper);
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function present($params)
    {
        $this->validateContextArgs($params);

        $getEnv = $this->getBillingContextWrapper()->getBillingEnv() ?: '';
        $billingEnv = $this->getEnvBuilder()->buildBillingEnv($getEnv);
        $tosUrl = !empty($params['tosUrl']) ? $params['tosUrl'] : $params['tosLink'];
        $privacyUrl = !empty($params['privacyUrl']) ? $params['privacyUrl'] : $params['privacyLink'];

        return [
            'psBillingContext' => [
                'context' => [
                    'contextVersion' => BillingPresenter::CONTEXT_VERSION,
                    'billingEnv' => $billingEnv,
                    'isSandbox' => $this->getBillingContextWrapper()->isSandbox()
                        ? $this->getBillingContextWrapper()->isSandbox()
                        : false,
                    'i18n' => [
                        'isoCode' => $this->getBillingContextWrapper()->getLanguageIsoCode(),
                    ],
                    'accessToken' => $this->getBillingContextWrapper()->getAccessToken(),
                    'shop' => [
                        'uuid' => $this->getBillingContextWrapper()->getShopUuid(),
                        'domain' => $this->getBillingContextWrapper()->getShopDomain(),
                    ],
                    'organization' => [
                        'uuid' => $this->getBillingContextWrapper()->getOrganizationUuid(),
                        'email' => $this->getBillingContextWrapper()->getEmail(),
                        'logoSrc' => !empty($params['logo']) ? $this->encodeImage($params['logo']) : '',
                    ],
                    'product' => [
                        'id' => $this->getModule()->name,
                        'displayName' => $this->getModule()->displayName,
                        'logoSrc' => $this->encodeImage($this->getModuleLogo()),
                        'privacyUrl' => !empty($privacyUrl) ? $privacyUrl : '',
                        'tosUrl' => !empty($tosUrl) ? $tosUrl : '',
                    ],
                ],
            ],
        ];
    }

    /**
     * Validate the args pass to the method "present" above
     *
     * @param mixed $params
     *
     * @return void
     *
     * @throws BillingContextException when some data are missing
     */
    private function validateContextArgs($params)
    {
        $tosUrl = !empty($params['tosUrl']) ? $params['tosUrl'] : $params['tosLink'];
        $privacyUrl = !empty($params['privacyUrl']) ? $params['privacyUrl'] : $params['privacyLink'];
        if (empty($tosUrl)) {
            throw new BillingContextException('"tosUrl" must be provided (value=' . $tosUrl . ')');
        }
        if (!\Validate::isAbsoluteUrl($tosUrl)) {
            throw new BillingContextException('"tosUrl" must be a valid url (value=' . $tosUrl . ')');
        }
        if (empty($privacyUrl)) {
            throw new BillingContextException('"privacyUrl" must be provided (value=' . $privacyUrl . ')');
        }
        if (!\Validate::isAbsoluteUrl($privacyUrl)) {
            throw new BillingContextException('"privacyUrl" must be a valid url (value=' . $privacyUrl . ')');
        }
    }

    /**
     * @return string
     */
    private function encodeImage($image_path)
    {
        $mime_type = $this->getMimeTypeByExtension($image_path);
        if ($mime_type === null) {
            return $mime_type;
        }

        $image_content = \Tools::file_get_contents($image_path);

        return 'data:' . $mime_type . ';base64,' . base64_encode($image_content);
    }

    /**
     * Return the mime type by the file extension.
     *
     * @param string $fileName
     *
     * @return string
     */
    private function getMimeTypeByExtension(string $fileName)
    {
        $types = [
            'image/gif' => ['gif'],
            'image/jpeg' => ['jpg', 'jpeg'],
            'image/png' => ['png'],
            'image/webp' => ['webp'],
            'image/svg+xml' => ['svg'],
        ];
        $extension = substr($fileName, strrpos($fileName, '.') + 1);

        $mimeType = null;
        foreach ($types as $mime => $exts) {
            if (in_array($extension, $exts)) {
                $mimeType = $mime;
                break;
            }
        }

        return $mimeType;
    }

    /**
     * @return string
     */
    private function getModuleLogo()
    {
        if (@filemtime($this->getModule()->getLocalPath() . 'logo.png')) {
            return $this->getModule()->getLocalPath() . 'logo.png';
        }

        return $this->getModule()->getLocalPath() . 'logo.gif';
    }

    /**
     * setEnvBuilder
     *
     * @param EnvBuilder $envBuilder
     *
     * @return void
     */
    private function setEnvBuilder(EnvBuilder $envBuilder)
    {
        $this->envBuilder = $envBuilder;
    }

    /**
     * getEnvBuilder
     *
     * @return EnvBuilder
     */
    private function getEnvBuilder()
    {
        return $this->envBuilder;
    }

    /**
     * setUrlBuilder
     *
     * @param UrlBuilder $urlBuilder
     *
     * @return void
     */
    private function setUrlBuilder(UrlBuilder $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * getUrlBuilder
     *
     * @return UrlBuilder
     */
    private function getUrlBuilder()
    {
        return $this->urlBuilder;
    }

    /**
     * setBillingContextWrapper
     *
     * @param BillingContextWrapper $billingContextWrapper
     *
     * @return void
     */
    private function setBillingContextWrapper(BillingContextWrapper $billingContextWrapper)
    {
        $this->billingContextWrapper = $billingContextWrapper;
    }

    /**
     * getBillingContextWrapper
     *
     * @return BillingContextWrapper
     */
    private function getBillingContextWrapper()
    {
        return $this->billingContextWrapper;
    }

    /**
     * setModule
     *
     * @param \Module $module
     *
     * @return void
     */
    private function setModule(Module $module)
    {
        $this->module = $module;
    }

    /**
     * getModule
     *
     * @return \Module
     */
    private function getModule()
    {
        return $this->module;
    }
}
