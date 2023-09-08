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

    public static function fromUrl()
    {
        $context = new self();
        $context->language = self::guessLanguage(\Tools::getValue('lang'));
        $context->debug = \Tools::getValue('debug', false);
        $context->shopId = \Tools::getValue('shop', \Context::getContext()->shop->id);
        $context->withTax = \Tools::getValue('tax', 1) === "1";
        $context->currency = self::guessCurrency(\Tools::getValue('currency'));
        $context->loadSales = \Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT') == 1;
        $context->loadSuppliers = \Configuration::get('AS_FIELDS_SUPPLIER_REFERENCES') == 1;

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
}