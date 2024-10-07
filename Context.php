<?php

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

        return $context;
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
            $context = \Context::getContext();
            $currencyId = $context->currency->id;
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
}