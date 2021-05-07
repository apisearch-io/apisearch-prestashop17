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

require_once __DIR__ . '/model/defaults.php';
require_once __DIR__ . '/model/hooks.php';
require_once __DIR__ . '/model/builder.php';
require_once __DIR__ . '/model/connection.php';

class Apisearch extends Module
{
    private $hooks;
    private $connection;
    protected $config_form = false;

    public function __construct()
    {
        $this->name = Defaults::PLUGIN_NAME;
        $this->tab = 'search_filter';
        $this->version = Defaults::PLUGIN_VERSION;
        $this->author = 'Apisearch Team & Partners';
        $this->need_instance = 0;
        $this->bootstrap = true;
        parent::__construct();
        $this->displayName = $this->l('Apisearch');
        $this->description = $this->l('Search over your products, and give to your users unique, amazing and unforgettable experiences.');
        $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
        $this->connection = new Connection();
        $this->hooks = new Hooks(
            new Builder(),
            $this->connection
        );
    }

    public function install()
    {
        Configuration::updateValue('AS_CLUSTER_URL', '');
        Configuration::updateValue('AS_ADMIN_URL', Defaults::DEFAULT_AS_ADMIN_URL);
        Configuration::updateValue('AS_API_VERSION', Defaults::DEFAULT_AS_API_VERSION);
        Configuration::updateValue('AS_APP', '');
        Configuration::updateValue('AS_INDEX', '');
        Configuration::updateValue('AS_TOKEN', '');
        Configuration::updateValue('AS_SHOP', '');
        Configuration::updateValue('AS_INDEX_PRODUCTS_WITHOUT_IMAGE', Defaults::DEFAULT_INDEX_PRODUCTS_WITHOUT_IMAGE);
        Configuration::updateValue('AS_REAL_TIME_INDEXATION', Defaults::DEFAULT_REAL_TIME_INDEXATION);
        Configuration::updateValue('AS_INDEX_PRODUCT_PURCHASE_COUNT', Defaults::DEFAULT_REAL_TIME_INDEXATION);

        $meta_as = new Meta();
        $meta_as->page = 'module-apisearch-as_search';
        $meta_as->title = $this->l('Apisearch - Search');
        $meta_as->description = $this->l('Search by Apisearch');
        $meta_as->url_rewrite = $this->l('as_search');

        if (!$meta_as->save()) {
            return false;
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('top') &&
            $this->registerHook('actionObjectProductAddAfter') &&
            $this->registerHook('actionObjectProductUpdateAfter') &&
            $this->registerHook('actionObjectProductDeleteBefore') &&
            $this->registerHook('actionObjectOrderUpdateAfter');
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

        $meta_as = Meta::getMetaByPage('module-apisearch-as_search', Context::getContext()->language->id);
        $meta_as = new Meta($meta_as['id_meta']);

        if (!$meta_as->delete()) {
            return false;
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        if (((bool)Tools::isSubmit('submitApisearchModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', Context::getContext()->link->getBaseLink() . ltrim($this->_path, '/'));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output . $this->renderForm();
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
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'col' => 3,
                    'type' => 'text',
                    'label' => $this->l('Apisearch Cluster Url'),
                    'placeholder' => Defaults::DEFAULT_AS_CLUSTER_URL,
                    'name' => 'AS_CLUSTER_URL',
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'label' => $this->l('Apisearch Admin Url'),
                    'placeholder' => Defaults::DEFAULT_AS_ADMIN_URL,
                    'name' => 'AS_ADMIN_URL',
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'label' => $this->l('Apisearch Api Version'),
                    'placeholder' => Defaults::DEFAULT_AS_API_VERSION,
                    'name' => 'AS_API_VERSION',
                ),
                array(
                    'col' => 3,
                    'type' => 'text',
                    'label' => $this->l('App Hash ID'),
                    'name' => 'AS_APP',
                ),
                array(
                    'col' => Language::isMultiLanguageActivated($this->context->shop->id) ? 4 : 3,
                    'type' => 'text',
                    'label' => $this->l('Index Hash ID'),
                    'name' => 'AS_INDEX',
                    'lang' => true
                ),
                array(
                    'col' => Language::isMultiLanguageActivated($this->context->shop->id) ? 4 : 3,
                    'type' => 'text',
                    'label' => $this->l('Management token Hash ID'),
                    'name' => 'AS_TOKEN',
                    'lang' => true
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('Index products without image'),
                    'name' => 'AS_INDEX_PRODUCTS_WITHOUT_IMAGE',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('Real time indexation'),
                    'name' => 'AS_REAL_TIME_INDEXATION',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
                array(
                    'col' => 3,
                    'type' => 'switch',
                    'label' => $this->l('Index product purchase count'),
                    'name' => 'AS_INDEX_PRODUCT_PURCHASE_COUNT',
                    'is_bool' => true,
                    'values' => array(
                        array(
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ),
                        array(
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        )
                    ),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        )
        );
        if (Shop::isFeatureActive()) {
            $configForm['form']['input'][] = array(
                'col' => 4,
                'type' => 'shop',
                'label' => $this->l('Shop association'),
                'name' => 'AS_SHOP',
            );
        }
        return $configForm;
    }

    protected function getConfigFormValues()
    {
        $form_values = array(
            'AS_CLUSTER_URL' => Configuration::get('AS_CLUSTER_URL'),
            'AS_ADMIN_URL' => Configuration::get('AS_ADMIN_URL'),
            'AS_API_VERSION' => Configuration::get('AS_API_VERSION'),
            'AS_APP' => Configuration::get('AS_APP'),
            'AS_INDEX_PRODUCTS_WITHOUT_IMAGE' => Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE'),
            'AS_REAL_TIME_INDEXATION' => Configuration::get('AS_REAL_TIME_INDEXATION'),
            'AS_INDEX_PRODUCT_PURCHASE_COUNT' => Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT'),
        );
        foreach ($this->context->controller->getLanguages() as $language) {
            $form_values['AS_INDEX'][$language['id_lang']] = Configuration::get('AS_INDEX', $language['id_lang']);
            $form_values['AS_TOKEN'][$language['id_lang']] = Configuration::get('AS_TOKEN', $language['id_lang']);
        }
        if (Shop::isFeatureActive()) {
            $form_values['AS_SHOP'] = Configuration::get('AS_SHOP');
        }
        return $form_values;
    }

    protected function postProcess()
    {
        $form_values = $this->getConfigFormValues();
        foreach ($form_values as $key => $value) {
            if (is_array($value)) {

                $post_values = array();
                foreach ($this->context->controller->getLanguages() as $language) {
                    $post_values[$language['id_lang']] = Tools::getValue($key . '_' . $language['id_lang']);
                }

                Configuration::updateValue($key, $post_values);
            } else {
                if ($key == 'AS_SHOP') {
                    $shop_post_values = array(
                        'group' => Tools::getValue('checkBoxShopGroupAsso_module'),
                        'shop' => Tools::getValue('checkBoxShopAsso_module')
                    );
                    Configuration::updateValue($key, json_encode($shop_post_values));
                } else
                    Configuration::updateValue($key, Tools::getValue($key));
            }
        }
    }

    public function hookHeader()
    {
        if ($this->connection->isProperlyConfigured()) {
            $admin_url = Configuration::get('AS_ADMIN_URL');
            $admin_url = $admin_url == ""
                ? Defaults::DEFAULT_AS_ADMIN_URL
                : $admin_url;

            Media::addJsDef(array(
                'admin_url' => $admin_url,
                'index_id' => Configuration::get('AS_INDEX', Context::getContext()->language->id),
            ));

            $this
                ->context
                ->controller->addJS($this->_path . 'views/js/front.js');
        }
    }

    /**
     * @param $params
     */
    public function hookActionObjectProductAddAfter($params)
    {
        if (\boolval(Configuration::get('AS_REAL_TIME_INDEXATION'))) {
            $this->hooks->putProductById($params['object']->id);
        }
    }

    /**
     * @param $params
     */
    public function hookActionObjectProductUpdateAfter($params)
    {
        if (\boolval(Configuration::get('AS_REAL_TIME_INDEXATION'))) {
            $this->hooks->putProductById($params['object']->id);
        }
    }

    /**
     * @param $params
     */
    public function hookActionObjectProductDeleteBefore($params)
    {
        if (\boolval(Configuration::get('AS_REAL_TIME_INDEXATION'))) {
            $this->hooks->deleteProductById($params['object']->id);
        }
    }

    /**
     * @param $params
     */
    public function hookActionObjectOrderUpdateAfter($params)
    {
        if (\boolval(Configuration::get('AS_REAL_TIME_INDEXATION'))) {
            $order = new Order($params['object']->id);
            $orderState = $order->getCurrentOrderState();

            if (Validate::isLoadedObject($order) && isset($orderState)) {
                if ($order->valid && $orderState->logable && $orderState->paid) {
                    foreach ($order->getProducts() as $product) {
                        if (
                            $product['product_quantity'] == $product['product_quantity_in_stock'] &&
                            $product['product_quantity_return'] == 0 &&
                            $product['product_quantity_reinjected'] == 0
                        ) {
                            continue;
                        }

                        $this->hooks->deleteProductById($params['object']->id);
                    }
                }
            }
        }
    }
}
