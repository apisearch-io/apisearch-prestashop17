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

namespace PrestaShopCorp\Billing\Builder;

class EnvBuilder
{
    /**
     * @return string
     */
    public function buildBillingEnv($envName)
    {
        switch ($envName) {
            case 'development':
                // Handle by .env in Billing UI
                return null;
            case 'integration':
            case 'prestabulle1':
            case 'prestabulle2':
            case 'prestabulle3':
            case 'prestabulle4':
            case 'prestabulle5':
            case 'prestabulle6':
            case 'preprod':
                return $envName;
                break;
            default:
                return 'production';
        }
    }
}
