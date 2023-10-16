{*
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2018 PrestaShop SA
*  @version  Release: $Revision$
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
<prestashop-accounts></prestashop-accounts>
<div id="ps-billing"></div>
<div id="ps-modal"></div>

<script src="{$urlAccountsCdn|escape:'htmlall':'UTF-8'}" rel=preload></script>
<script src="{$urlBilling|escape:'htmlall':'UTF-8'}" rel=preload></script>

<script>
    /*********************
     * PrestaShop Account *
     * *******************/
    window?.psaccountsVue?.init();

    // Check if Account is associated before displaying Billing component
    if(window.psaccountsVue.isOnboardingCompleted() == true)
    {
        /*********************
         * PrestaShop Billing *
         * *******************/
        window.psBilling.initialize(window.psBillingContext.context, '#ps-billing', '#ps-modal', (type, data) => {
            // Event hook listener
            switch (type) {
                // Hook triggered when the subscription is created
                case window.psBilling.EVENT_HOOK_TYPE.SUBSCRIPTION_CREATED:
                    console.log('subscription created', data);
                    break;
                // Hook when the subscription is updated
                case window.psBilling.EVENT_HOOK_TYPE.SUBSCRIPTION_UPDATED:
                    console.log('subscription updated', data);
                    break;
                // Hook triggered when the subscription is cancelled
                case window.psBilling.EVENT_HOOK_TYPE.SUBSCRIPTION_CANCELLED:
                    console.log('subscription cancelled', data);
                    break;
            }
        });
    }
</script>