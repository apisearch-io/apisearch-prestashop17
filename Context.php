<?php

/**
 * Plugin Name: Apisearch
 * License: MIT
 * Copyright (c) 2020 - 2025 Apisearch SL
 *
 * This software is provided 'as-is', without any express or implied warranty.
 * In no event will the authors be held liable for any damages arising from the use
 * of this software, even if advised of the possibility of such damages.
 *
 * Permission is hereby granted, free of charge, to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to permit persons
 * to whom the Software is provided to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice must be included in all copies
 * or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 * PURPOSE, AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES, OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT,
 * OR OTHERWISE, ARISING FROM, OUT OF, OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 */

namespace Apisearch;

class Context
{
    private $language;
    private $currency;
    private $withTax;
    private $shopId;
    private $loadSales;
    private $loadSuppliers;
    private $debug;
    private $onlyPSProducts;
    private $idCountry;
    private $idState;
    private $zipcode;
    private $groupId;

    public static function fromUrl()
    {
        $context = new self();
        $context->language = self::guessLanguage(\Tools::getValue('lang'));
        $context->debug = \Tools::getValue('debug', false);
        $context->onlyPSProducts = \Tools::getValue('only-ps-products', false);
        $context->shopId = \Tools::getValue('shop', \Context::getContext()->shop->id);
        $context->withTax = \Tools::getValue('tax', !\Configuration::get('AS_SHOW_PRICES_WITHOUT_TAX'));
        $context->currency = self::guessCurrency(\Tools::getValue('currency'));
        $context->loadSales = \Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT') == 1;
        $context->loadSuppliers = \Configuration::get('AS_FIELDS_SUPPLIER_REFERENCES') == 1;
        $context->idCountry = \Tools::getValue('id-country', \Address::initialize()->id_country);
        $context->idState = \Tools::getValue('id-state', \Address::initialize()->id_state);
        $context->zipcode = \Tools::getValue('zipcode', \Address::initialize()->postcode);
        $context->groupId = (int) \Configuration::get('PS_UNIDENTIFIED_GROUP');

        return $context;
    }

    public static function fromCurrentPrestashopContext()
    {
        $prestashopContext = \Context::getContext();

        $context = new self();
        $context->language = $prestashopContext->language->id;
        $context->debug = false;
        $context->onlyPSProducts = false;
        $context->shopId = \Context::getContext()->shop->id;
        $context->groupId = \Context::getContext()->customer->id_default_group;

        // Special scenario
        // Check if the group is included in the groups that, even if is defined that the price should be calculated
        // with tax, should be shown without tax.
        $groupsToShowPricesWithoutTax = explode(',', \Configuration::get('AS_GROUPS_SHOW_NO_TAX'));
        $context->withTax = in_array($context->groupId, $groupsToShowPricesWithoutTax)
            ? false
            : (new \Group($context->groupId))->price_display_method == "0";

        $context->currency = $prestashopContext->currency;
        $context->loadSales = false;
        $context->loadSuppliers = false;

        $cart = $prestashopContext->cart;
        if (
            !is_null($cart->id_address_delivery) &&
            $cart->id_address_delivery > 0
        ) {
            $address = new \Address($cart->id_address_delivery);
        } else {
            $addressId = \Address::getFirstCustomerAddressId($prestashopContext->customer->id);
            if ($addressId) {
                $address = new \Address($addressId);
            } else {
                $address = \Address::initialize();
            }
        }

        $context->idCountry = $address->id_country;
        $context->idState = $address->id_state;
        $context->zipcode = $address->postcode;

        return $context;
    }

    /**
     * @param Context $context
     * @return void
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public static function updatePrestashopContext(Context $context)
    {
        \Context::getContext()->language = $context->language;
    }

    /**
     * @param $currency
     * @return \Currency
     */
    public static function guessCurrency($currency)
    {
        $currencyId = null;
        if ($currency) {
            if (is_numeric($currency)) {
                $currencyId = intval($currency);
            } else {
                $currencyId = \Currency::getIdByIsoCode(strtoupper($currency));
            }
        }

        if (!$currencyId) {
            $currencyId = \Configuration::get('PS_CURRENCY_DEFAULT');
        }

        return new \Currency($currencyId);
    }

    /**
     * @param $language
     * @return \Language
     */
    public static function guessLanguage($language)
    {
        $languageId = null;
        if ($language) {
            if (is_numeric($language)) {
                $languageId = intval($language);
            } else {
                $languageId = \Language::getIdByIso($language);
            }
        }

        if (!$languageId) {
            $context = \Context::getContext();
            $languageId = $context->language->id;
        }

        return new \Language($languageId);
    }

    /**
     * @return \Currency
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return mixed
     */
    public function getLanguageId()
    {
        return $this->language->id;
    }

    /**
     * @return bool
     */
    public function isWithTax()
    {
        return $this->withTax;
    }

    /**
     * @return string
     */
    public function getShopId()
    {
        return $this->shopId;
    }

    /**
     * @return bool
     */
    public function isLoadSales()
    {
        return $this->loadSales;
    }

    /**
     * @return bool
     */
    public function isLoadSuppliers()
    {
        return $this->loadSuppliers;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    public function printOnlyPSProducts()
    {
        return $this->onlyPSProducts;
    }

    /**
     * @return mixed
     */
    public function getIdCountry()
    {
        return $this->idCountry;
    }

    /**
     * @return mixed
     */
    public function getIdState()
    {
        return $this->idState;
    }

    /**
     * @return mixed
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }

    /**
     * @return mixed
     */
    public function getGroupId()
    {
        return $this->groupId;
    }
}