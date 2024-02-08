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
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

require_once __DIR__.'/vendor/autoload.php';

class Apisearch extends Module
{
    /**
     * @var ServiceContainer
     */
    private $container;

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

        if (!$this->isRegisteredInHook('actionUpdateQuantity')) {
            $this->registerHook('actionUpdateQuantity');
        }

        if ($this->container === null) {
            $this->container = new \PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
                $this->name,
                $this->getLocalPath()
            );
        }
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
        Configuration::updateValue('AS_REAL_TIME_INDEXATION', ApisearchDefaults::DEFAULT_REAL_TIME_INDEXATION);
        Configuration::updateValue('AS_INDEX_PRODUCT_PURCHASE_COUNT', ApisearchDefaults::DEFAULT_AS_INDEX_PRODUCT_PURCHASE_COUNT);
        Configuration::updateValue('AS_INDEX_PRODUCT_NO_STOCK', ApisearchDefaults::DEFAULT_AS_INDEX_PRODUCT_NO_STOCK);
        Configuration::updateValue('AS_FIELDS_SUPPLIER_REFERENCES', ApisearchDefaults::AS_FIELDS_SUPPLIER_REFERENCES);
        Configuration::updateValue('AS_INDEX_DESCRIPTIONS', ApisearchDefaults::DEFAULT_INDEX_DESCRIPTIONS);

        $meta_as = new Meta();
        $meta_as->page = 'module-apisearch-as_search';
        $meta_as->title = $this->l('Apisearch - Search');
        $meta_as->description = $this->l('Search by Apisearch');
        $meta_as->url_rewrite = $this->l('as_search');

        if (!$meta_as->save()) {
            return false;
        }

        // Test if MBO is installed, if not, try to install it
        $mboStatus = (new Prestashop\ModuleLibMboInstaller\Presenter)->present();
        if(!$mboStatus["isInstalled"]) {
            try {
                $mboInstaller = new Prestashop\ModuleLibMboInstaller\Installer(_PS_VERSION_);
                /** @var boolean */
                $result = $mboInstaller->installModule();
                // Call the installation of PrestaShop Integration Framework components
                $this->installDependencies();
            } catch (\Exception $e) {
                // Some errors can happen, i.e during initialization or download of the module
                $this->context->controller->errors[] = $e->getMessage();
                return 'Error during MBO installation';            }
        } else {
            $this->installDependencies();
        }

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('top') &&
            $this->registerHook('actionObjectProductAddAfter') &&
            $this->registerHook('actionObjectProductUpdateAfter') &&
            $this->registerHook('actionObjectProductDeleteBefore') &&
            $this->registerHook('actionObjectOrderUpdateAfter') &&
            $this->registerHook('actionUpdateQuantity') &&
            $this->getService('apisearch.ps_accounts_installer')->install()
        ;
    }

    /**
     * Install PrestaShop Integration Framework Components
     */
    public function installDependencies()
    {
        $moduleManager = ModuleManagerBuilder::getInstance()->build();

        /* PS Account */
        if (!$moduleManager->isInstalled("ps_accounts")) {
            $moduleManager->install("ps_accounts");
        } else if (!$moduleManager->isEnabled("ps_accounts")) {
            $moduleManager->enable("ps_accounts");
            $moduleManager->upgrade('ps_accounts');
        } else {
            $moduleManager->upgrade('ps_accounts');
        }

        /* Cloud Sync - PS Eventbus */
        if (!$moduleManager->isInstalled("ps_eventbus")) {
            $moduleManager->install("ps_eventbus");
        } else if (!$moduleManager->isEnabled("ps_eventbus")) {
            $moduleManager->enable("ps_eventbus");
            $moduleManager->upgrade('ps_eventbus');
        } else {
            $moduleManager->upgrade('ps_eventbus');
        }
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

        $meta_as = Meta::getMetaByPage('module-apisearch-as_search', Context::getContext()->language->id);
        $meta_as = new Meta($meta_as['id_meta']);

        if (!$meta_as->delete()) {
            return false;
        }

        return parent::uninstall();
    }

    public function getContent()
    {
        if ((Tools::isSubmit('submitApisearchModule')) == true) {
            $this->postProcess();
        }

        $this->context->smarty->assign('module_dir', Context::getContext()->link->getBaseLink() . ltrim($this->_path, '/'));

        /*********************
         * PrestaShop Account *
         * *******************/

        $accountsService = null;

        try {
            $accountsFacade = $this->getService('apisearch.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        } catch (\PrestaShop\PsAccountsInstaller\Installer\Exception\InstallerException $e) {
            $accountsInstaller = $this->getService('apisearch.ps_accounts_installer');
            $accountsInstaller->install();
            $accountsFacade = $this->getService('apisearch.ps_accounts_facade');
            $accountsService = $accountsFacade->getPsAccountsService();
        }

        try {
            Media::addJsDef([
                'contextPsAccounts' => $accountsFacade->getPsAccountsPresenter()
                    ->present($this->name),
            ]);

            // Retrieve the PrestaShop Account CDN
            $this->context->smarty->assign('urlAccountsCdn', $accountsService->getAccountsCdn());

        } catch (Exception $e) {
            $this->context->controller->errors[] = $e->getMessage();
            return '';
        }

        /**********************
         * PrestaShop Billing *
         * *******************/

        // Load the context for PrestaShop Billing
        $billingFacade = $this->getService('apisearch.ps_billings_facade');
        $partnerLogo = $this->getLocalPath() . 'views/img/apisearch-logo.png';

        // PrestaShop Billing
        Media::addJsDef($billingFacade->present([
            'logo' => $partnerLogo,
            'tosLink' => 'https://apisearch.io/terminos-y-condiciones.html',
            'privacyLink' => 'https://apisearch.io/politica-de-privacidad.html',
            // This field is deprecated, but must be provided to ensure backward compatibility
            'emailSupport' => ''
        ]));

        $this->context->smarty->assign('urlBilling', "https://unpkg.com/@prestashopcorp/billing-cdc/dist/bundle.js");
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

        return $helper->generateForm(array($this->getConfigForm())) . '<script>document.getElementById("go-to-admin").setAttribute("target", "_blank");</script>';
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
        $form_values = array(
            'AS_DISPLAY_SEARCH_BAR' => Configuration::get('AS_DISPLAY_SEARCH_BAR'),
            'AS_CLUSTER_URL' => Configuration::get('AS_CLUSTER_URL'),
            'AS_ADMIN_URL' => Configuration::get('AS_ADMIN_URL'),
            'AS_APP' => Configuration::get('AS_APP'),
            'AS_INDEX_PRODUCTS_WITHOUT_IMAGE' => Configuration::get('AS_INDEX_PRODUCTS_WITHOUT_IMAGE'),
            'AS_REAL_TIME_INDEXATION' => Configuration::get('AS_REAL_TIME_INDEXATION'),
            'AS_INDEX_PRODUCT_PURCHASE_COUNT' => Configuration::get('AS_INDEX_PRODUCT_PURCHASE_COUNT'),
            'AS_INDEX_PRODUCT_NO_STOCK' => Configuration::get('AS_INDEX_PRODUCT_NO_STOCK'),
            'AS_FIELDS_SUPPLIER_REFERENCES' => Configuration::get('AS_FIELDS_SUPPLIER_REFERENCES'),
            'AS_INDEX_DESCRIPTIONS' => Configuration::get('AS_INDEX_DESCRIPTIONS'),
        );
        foreach ($this->context->controller->getLanguages() as $language) {
            $form_values['AS_INDEX'][$language['id_lang']] = Configuration::get('AS_INDEX', $language['id_lang']);
            $form_values['AS_TOKEN'][$language['id_lang']] = Configuration::get('AS_TOKEN', $language['id_lang']);
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
        $indexId = Configuration::get('AS_INDEX', Context::getContext()->language->id);
        if (empty($indexId)) {
            return;
        }

        $displaySearchBar = Configuration::get('AS_DISPLAY_SEARCH_BAR');
        if (!$displaySearchBar) {
            return;
        }

        $this->context->smarty->assign(array(
            'apisearch_admin_url' => ApisearchDefaults::DEFAULT_AS_ADMIN_URL,
            'apisearch_index_id' => Configuration::get('AS_INDEX', Context::getContext()->language->id),
        ));

        return $this->display(__FILE__, 'views/templates/front/search.tpl');
    }

    /**
     * Retrieve the service
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    public function getService($serviceName)
    {
        return $this->container->getService($serviceName);
    }
}
