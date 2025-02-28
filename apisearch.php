<?php

/**
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2020 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

use Apisearch\Model\ApisearchDefaults;
use Apisearch\Model\ApisearchImage;
use Apisearch\Model\ApisearchOrderBy;

require_once __DIR__.'/vendor/autoload.php';

class Apisearch extends Module
{
    public function __construct()
    {
        $this->name = ApisearchDefaults::PLUGIN_NAME;
        $this->tab = 'search_filter';
        $this->version = ApisearchDefaults::PLUGIN_VERSION;
        $this->author = 'Apisearch (https://apisearch.io)';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Apisearch');
        $this->description = $this->l('module_description');
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        Configuration::updateValue('AS_CLUSTER_URL', '');
        Configuration::updateValue('AS_ADMIN_URL', ApisearchDefaults::DEFAULT_AS_ADMIN_URL);
        Configuration::updateValue('AS_APP', '');
        Configuration::updateValue('AS_INDEX', '');
        Configuration::updateValue('AS_TOKEN', '');
        Configuration::updateValue('AS_SHOP', '');
        Configuration::updateValue('AS_INDEX_PRODUCTS_WITHOUT_IMAGE', ApisearchDefaults::DEFAULT_INDEX_PRODUCTS_WITHOUT_IMAGE);
        Configuration::updateValue('AS_INDEX_PRODUCT_PURCHASE_COUNT', ApisearchDefaults::DEFAULT_AS_INDEX_PRODUCT_PURCHASE_COUNT);
        Configuration::updateValue('AS_INDEX_PRODUCT_NO_STOCK', ApisearchDefaults::DEFAULT_AS_INDEX_PRODUCT_NO_STOCK);
        Configuration::updateValue('AS_FIELDS_SUPPLIER_REFERENCES', ApisearchDefaults::AS_FIELDS_SUPPLIER_REFERENCES);
        Configuration::updateValue('AS_INDEX_DESCRIPTIONS', ApisearchDefaults::DEFAULT_INDEX_DESCRIPTIONS);
        Configuration::updateValue('AS_INDEX_LONG_DESCRIPTIONS', ApisearchDefaults::DEFAULT_INDEX_LONG_DESCRIPTIONS);
        Configuration::updateValue('AS_B2B', false);
        Configuration::updateValue('AS_INDEX_IMAGES_PER_COLOR', false);
        Configuration::updateValue('AS_SHOW_PRICES_WITHOUT_TAX', ApisearchDefaults::AS_SHOW_PRICES_WITHOUT_TAX);
        Configuration::updateValue('AS_GROUP_BY_COLOR', ApisearchDefaults::AS_GROUP_BY_COLOR);
        Configuration::updateValue('AS_IMAGE_FORMAT', ApisearchDefaults::AS_DEFAULT_IMAGE_TYPE);
        Configuration::updateValue('AS_ORDER_BY', ApisearchDefaults::AS_DEFAULT_ORDER_BY);
        Configuration::updateValue('AS_REAL_TIME_PRICES', ApisearchDefaults::AS_REAL_TIME_PRICES);
        Configuration::updateValue('AS_GROUPS_SHOW_NO_TAX', ApisearchDefaults::AS_GROUPS_SHOW_NO_TAX);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('top');
    }

    public function uninstall()
    {
        Configuration::deleteByName('AS_CLUSTER_URL');
        Configuration::deleteByName('AS_ADMIN_URL');
        Configuration::deleteByName('AS_API_VERSION');
        Configuration::deleteByName('AS_APP');
        Configuration::deleteByName('AS_INDEX');
        Configuration::deleteByName('AS_TOKEN');
        Configuration::deleteByName('AS_SHOP');
        Configuration::deleteByName('AS_INDEX_PRODUCTS_WITHOUT_IMAGE');
        Configuration::deleteByName('AS_REAL_TIME_INDEXATION');
        Configuration::deleteByName('AS_INDEX_PRODUCT_PURCHASE_COUNT');
        Configuration::deleteByName('AS_INDEX_PRODUCT_NO_STOCK');
        Configuration::deleteByName('AS_INDEX_DESCRIPTIONS');
        Configuration::deleteByName('AS_INDEX_LONG_DESCRIPTIONS');
        Configuration::deleteByName('AS_B2B');
        Configuration::deleteByName('AS_INDEX_IMAGES_PER_COLOR');
        Configuration::deleteByName('AS_SHOW_PRICES_WITH_TAX');
        Configuration::deleteByName('AS_GROUP_BY_COLOR');
        Configuration::deleteByName('AS_IMAGE_FORMAT');
        Configuration::deleteByName('AS_ORDER_BY');
        Configuration::deleteByName('AS_REAL_TIME_PRICES');
        Configuration::deleteByName('AS_GROUPS_SHOW_NO_TAX');

        return parent::uninstall();
    }

    public function getContent()
    {
        if ((Tools::isSubmit('submitApisearchModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', Context::getContext()->link->getBaseLink() . ltrim($this->_path, '/'));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/config_form.tpl');

        return $this->renderForm() . $output;
    }

    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitApisearchModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        Configuration::loadConfiguration();

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    protected function getConfigForm()
    {
        $configForm = array('form' => array(
            'legend' => array(
                'title' => $this->l('settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('display_search_bar'),
                    'name' => 'AS_DISPLAY_SEARCH_BAR',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => Language::isMultiLanguageActivated($this->context->shop->id) ? 4 : 3,
                    'type' => 'text',
                    'label' => $this->l('index_hash_id'),
                    'name' => 'AS_INDEX',
                    'lang' => true
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('index_products_without_image'),
                    'name' => 'AS_INDEX_PRODUCTS_WITHOUT_IMAGE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('index_non_available_products'),
                    'name' => 'AS_INDEX_PRODUCT_NO_STOCK',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('index_products_purchase_count'),
                    'name' => 'AS_INDEX_PRODUCT_PURCHASE_COUNT',
                    'desc' => $this->l('index_products_purchase_count_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('index_supplier_references'),
                    'name' => 'AS_FIELDS_SUPPLIER_REFERENCES',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),

                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('index_short_descriptions'),
                    'name' => 'AS_INDEX_DESCRIPTIONS',
                    'desc' => $this->l('index_short_descriptions_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('index_long_descriptions'),
                    'name' => 'AS_INDEX_LONG_DESCRIPTIONS',
                    'desc' => $this->l('index_long_descriptions_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                /*
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('enable_b2b'),
                    'name' => 'AS_B2B',
                    'desc' => $this->l('enable_b2b_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                */
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('enable_index_images_per_color'),
                    'name' => 'AS_INDEX_IMAGES_PER_COLOR',
                    'desc' => $this->l('enable_index_images_per_color_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('prices_without_tax'),
                    'name' => 'AS_SHOW_PRICES_WITHOUT_TAX',
                    'desc' => $this->l('prices_without_tax_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('group_by_color'),
                    'name' => 'AS_GROUP_BY_COLOR',
                    'desc' => $this->l('group_by_color_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'select',
                    'label' => $this->l('image_format'),
                    'name' => 'AS_IMAGE_FORMAT',
                    'desc' => $this->l('image_format_help'),
                    'options' => array(
                        'query' => array_map(function ($type) {
                            return array(
                                'id' => $type,
                                'name' => $type,
                            );
                        }, ApisearchImage::getImageTypes()),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'select',
                    'label' => $this->l('order_by'),
                    'name' => 'AS_ORDER_BY',
                    'desc' => $this->l('order_by_help'),
                    'options' => array(
                        'query' => array_map(function ($type) {
                            return array(
                                'id' => $type,
                                'name' => $this->l('order_by_' . $type),
                            );
                        }, array_keys(ApisearchOrderBy::ORDER_BY)),
                        'id' => 'id',
                        'name' => 'name',
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('real_time_prices'),
                    'name' => 'AS_REAL_TIME_PRICES',
                    'desc' => $this->l('real_time_prices_help'),
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('no')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'class' => 'as_groups_show_no_tax',
                    'type' => 'select',
                    'label' => $this->l('groups_show_no_tax'),
                    'name' => 'AS_GROUPS_SHOW_NO_TAX[]',
                    'desc' => $this->l('groups_show_no_tax_help'),
                    'multiple' => true,
                    'options' => array(
                        'query' => array_map(function(array $group) {
                            return array(
                                'id' => $group['id_group'],
                                'name' => $group['name']
                            );
                        }, Group::getGroups(Context::getContext()->language->id)),
                        'id' => 'id',
                        'name' => 'name'
                    )
                )
            ),
            'buttons' => array(
                array(
                    'type' => 'button',
                    'title' => $this->l('go_to_admin'),
                    'href' => 'https://apisearch.cloud',
                    'id' => 'go-to-admin'
                ),
                array(
                    'type' => 'submit',
                    'title' => $this->l('save'),
                    'class' => 'pull-right',
                )
            )
        )
        );

        return $configForm;
    }

    protected function getConfigFormValues()
    {
        $formValues = array(
            'AS_DISPLAY_SEARCH_BAR' => Configuration::get('AS_DISPLAY_SEARCH_BAR'),
            'AS_CLUSTER_URL' => Configuration::get('AS_CLUSTER_URL'),
            'AS_ADMIN_URL' => Configuration::get('AS_ADMIN_URL'),
            'AS_APP' => Configuration::get('AS_APP'),
            'AS_INDEX_PRODUCTS_WITHOUT_IMAGE' => Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE'),
            'AS_INDEX_PRODUCT_PURCHASE_COUNT' => Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT'),
            'AS_INDEX_PRODUCT_NO_STOCK' => Configuration::get('AS_INDEX_PRODUCT_NO_STOCK'),
            'AS_FIELDS_SUPPLIER_REFERENCES' => Configuration::get('AS_FIELDS_SUPPLIER_REFERENCES'),
            'AS_INDEX_DESCRIPTIONS' => Configuration::get('AS_INDEX_DESCRIPTIONS'),
            'AS_INDEX_LONG_DESCRIPTIONS' => Configuration::get('AS_INDEX_LONG_DESCRIPTIONS'),
            'AS_B2B' => Configuration::get('AS_B2B'),
            'AS_INDEX_IMAGES_PER_COLOR' => Configuration::get('AS_INDEX_IMAGES_PER_COLOR'),
            'AS_SHOW_PRICES_WITHOUT_TAX' => Configuration::get('AS_SHOW_PRICES_WITHOUT_TAX'),
            'AS_GROUP_BY_COLOR' => Configuration::get('AS_GROUP_BY_COLOR'),
            'AS_IMAGE_FORMAT' => ApisearchImage::getCurrentImageType(),
            'AS_ORDER_BY' => ApisearchOrderBy::getCurrentOrderBy(),
            'AS_REAL_TIME_PRICES' => Configuration::get('AS_REAL_TIME_PRICES'),
            'AS_GROUPS_SHOW_NO_TAX[]' => explode(',', Configuration::get('AS_GROUPS_SHOW_NO_TAX')),
        );

        foreach ($this->context->controller->getLanguages() as $language) {
            $formValues['AS_INDEX'][$language['id_lang']] = Configuration::get('AS_INDEX', $language['id_lang']);
            $formValues['AS_TOKEN'][$language['id_lang']] = Configuration::get('AS_TOKEN', $language['id_lang']);
        }

        return $formValues;
    }

    protected function postProcess()
    {
        $formValues = $this->getConfigFormValues();
        foreach ($formValues as $key => $value) {
            if (is_array($value) && !in_array($key, ['AS_GROUPS_SHOW_NO_TAX[]'])) {

                $postValues = array();
                foreach ($this->context->controller->getLanguages() as $language) {
                    $postValues[$language['id_lang']] = Tools::getValue($key . '_' . $language['id_lang']);
                }

                Configuration::updateValue($key, $postValues);
            } else {
                $value = Tools::getValue($key);
                if ($key === 'AS_GROUPS_SHOW_NO_TAX[]') {
                    $key = 'AS_GROUPS_SHOW_NO_TAX';
                    $value = implode(',', Tools::getValue('AS_GROUPS_SHOW_NO_TAX', []));
                }
                Configuration::updateValue($key, $value);
            }
        }
    }

    public function hookHeader()
    {
        $indexId = Configuration::get('AS_INDEX', Context::getContext()->language->id);
        if (empty($indexId)) {
            return;
        }

        $displaySearchBar = Configuration::get('AS_DISPLAY_SEARCH_BAR');
        if (!$displaySearchBar) {
            return;
        }

        $isB2B = Configuration::get('AS_B2B');
        if ($isB2B) {
            $currentIdGroup = \Tools::getValue('apisearch_group_id');
            $currentIdGroup = empty($currentIdGroup)
                ? $this->context->customer->id_default_group
                : $currentIdGroup;

            $currentIdCustomer = \Tools::getValue('apisearch_customer_id');
            $currentIdCustomer = empty($currentIdCustomer)
                ? $this->context->customer->id
                : $currentIdCustomer;

            if (!empty($currentIdCustomer)) {
                $currentIdCustomer = 'cus_' . $currentIdCustomer;
            }
        } else {
            $currentIdGroup = null;
            $currentIdCustomer = null;
        }

        $this->context->smarty->assign(array(
            'apisearch_admin_url' => ApisearchDefaults::DEFAULT_AS_ADMIN_URL,
            'apisearch_index_id' => Configuration::get('AS_INDEX', Context::getContext()->language->id),
            'group_id' => $currentIdGroup != Configuration::get('PS_UNIDENTIFIED_GROUP')
                ? $currentIdGroup
                : null,
            'customer_id' => $currentIdCustomer,
            'base_url' => Context::getContext()->shop->getBaseURL(true),
            'real_time_prices' => Configuration::get('AS_REAL_TIME_PRICES') === "1",
        ));

        return $this->display(__FILE__, 'views/templates/front/search.tpl');
    }
}
