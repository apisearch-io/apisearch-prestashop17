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
 *  @author    PrestaShop SA <contact@prestashop.com>
 *  @copyright 2007-2020 PrestaShop SA
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
  exit;
}

class Apisearch extends Module {

  protected $config_form = false;

  public function __construct() {
    $this->name = 'apisearch';
    $this->tab = 'search_filter';
    $this->version = '1.1.0';
    $this->author = 'eComm360';
    $this->need_instance = 0;

    $this->bootstrap = true;

    parent::__construct();

    $this->displayName = $this->l('Apisearch');
    $this->description = $this->l('Search over your products, and give to your users unique, amazing and unforgettable experiences.');

    $this->ps_versions_compliancy = array('min' => '1.5', 'max' => _PS_VERSION_);
  }

  public function install() {
    Configuration::updateValue('AS_APP', '');
    Configuration::updateValue('AS_INDEX', '');
    Configuration::updateValue('AS_TOKEN', '');
    Configuration::updateValue('AS_SHOP', '');

    return parent::install() && $this->registerHook('header') && $this->registerHook('top') && $this->registerHook('actionObjectProductAddAfter') && $this->registerHook('actionObjectProductUpdateAfter') && $this->registerHook('actionObjectProductDeleteBefore') && $this->registerHook('actionObjectOrderUpdateAfter');
  }

  public function uninstall() {
    Configuration::deleteByName('AS_APP');
    Configuration::deleteByName('AS_INDEX');
    Configuration::deleteByName('AS_TOKEN');
    Configuration::deleteByName('AS_SHOP');

    return parent::uninstall();
  }

  public function getContent() {
    if (((bool) Tools::isSubmit('submitApisearchModule')) == true) {
      $this->postProcess();
    }

    $this->context->smarty->assign('module_dir', $this->_path);

    $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

    return $output . $this->renderForm();
  }

  protected function renderForm() {
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

  protected function getConfigForm() {
    $configForm = array('form' => array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
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

  protected function getConfigFormValues() {
    $form_values = array('AS_APP' => Configuration::get('AS_APP'));
    foreach ($this->context->controller->getLanguages() as $language) {
      $form_values['AS_INDEX'][$language['id_lang']] = Configuration::get('AS_INDEX', $language['id_lang']);
      $form_values['AS_TOKEN'][$language['id_lang']] = Configuration::get('AS_TOKEN', $language['id_lang']);
    }
    if (Shop::isFeatureActive()) {
      $form_values['AS_SHOP'] = Configuration::get('AS_SHOP');
    }
    return $form_values;
  }

  protected function postProcess() {
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

  public function hookHeader() {
    $peticiones = $this->getPeticiones();
    if ($peticiones > 0) {
      $this->context->controller->addCSS($this->_path . 'views/css/font-awesome.min.css');

      Media::addJsDef(array(
          'index_id' => Configuration::get('AS_INDEX', Context::getContext()->language->id),
          'static_token' => Tools::getToken(false),
          'url_search' => urlencode($this->context->link->getPageLink('search')),
          'url_cart' => urlencode($this->context->link->getPageLink('cart')),
          'show_more' => urlencode($this->l('Show more')),
          'show_less' => urlencode($this->l('Show less')),
          'results' => urlencode($this->l('Results:')),
          'empty_results' => urlencode($this->l('Empty results for:')),
          'clear_filters' => urlencode($this->l('Clear filters')),
          'add_to_cart' => urlencode($this->l('Add to cart')),
      ));
      $this->context->controller->addJS($this->_path . 'views/js/front.js');
    }
  }

  public function hookTop() {
    $peticiones = $this->getPeticiones();
    if ($peticiones > 0) {
      return $this->display(__FILE__, 'views/templates/front/searchbar.tpl');
    }
  }

  public function hookActionObjectProductAddAfter($params) {
    $product = new Product($params['object']->id);
    if (Validate::isLoadedObject($product) && $product->active) {
      $indexs = array();
      foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
        $index_id = Configuration::get('AS_INDEX', $lang['id_lang']);
        if (in_array($index_id, $indexs))
          continue;

        $indexs[] = $index_id;

        $apisearch_client = $this->conn($lang['id_lang']);

        if ($apisearch_client === false) {
          continue;
        }

        $item = $this->buildItems(array(array('id_product' => $product->id)), $lang['id_lang']);
        $apisearch_client->putItem($item);
        $apisearch_client->flush();
      }
    }
  }

  public function hookActionObjectProductUpdateAfter($params) {
    $product = new Product($params['object']->id);
    if (Validate::isLoadedObject($product) && $product->active) {
      $indexs = array();
      foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
        $index_id = Configuration::get('AS_INDEX', $lang['id_lang']);
        if (in_array($index_id, $indexs))
          continue;

        $indexs[] = $index_id;

        $apisearch_client = $this->conn($lang['id_lang']);

        if ($apisearch_client === false) {
          continue;
        }

        $item = $this->buildItems(array(array('id_product' => $product->id)), $lang['id_lang']);
        $apisearch_client->putItem($item);
        $apisearch_client->flush();
      }
    }
  }

  public function hookActionObjectProductDeleteBefore($params) {
    $product = new Product($params['object']->id);
    if (Validate::isLoadedObject($product) && $product->active) {
      $indexs = array();
      foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
        $index_id = Configuration::get('AS_INDEX', $lang['id_lang']);
        if (in_array($index_id, $indexs))
          continue;

        $indexs[] = $index_id;

        $apisearch_client = $this->conn($lang['id_lang']);

        if ($apisearch_client === false) {
          continue;
        }

        $uuid = array(
            'id' => $product->id,
            'type' => 'product'
        );

        $apisearch_client->deleteItem($uuid);
        $apisearch_client->flush();
      }
    }
  }

  public function hookActionObjectOrderUpdateAfter($params) {
    $order = new Order($params['object']->id);
    $order_state = $order->getCurrentOrderState();

    if (Validate::isLoadedObject($order) && isset($order_state)) {
      if ($order->valid && $order_state->logable && $order_state->paid) {
        foreach ($order->getProducts() as $product) {
          if ($product['product_quantity'] == $product['product_quantity_in_stock'] && $product['product_quantity_return'] == 0 && $product['product_quantity_reinjected'] == 0)
            continue;
          
          $product_obj = new Product($product['product_id']);
          if (Validate::isLoadedObject($product_obj) && $product_obj->active) {
            $indexs = array();
            foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
              $index_id = Configuration::get('AS_INDEX', $lang['id_lang']);
              if (in_array($index_id, $indexs))
                continue;

              $indexs[] = $index_id;

              $apisearch_client = $this->conn($lang['id_lang']);

              if ($apisearch_client === false) {
                continue;
              }

              $item = $this->buildItems(array(array('id_product' => $product_obj->id)), $lang['id_lang']);
              $apisearch_client->putItem($item);
              $apisearch_client->flush();
            }
          }
        }
      }
    }
  }

  public function conn($id_lang = '') {
    require_once _PS_MODULE_DIR_ . $this->name . '/classes/apisearch_client.php';

    if (empty($id_lang)) {
      $id_lang = Context::getContext()->language->id;
    }

    $app_id = Configuration::get('AS_APP');
    $index_id = Configuration::get('AS_INDEX', $id_lang);
    $token = Configuration::get('AS_TOKEN', $id_lang);

    if (empty($index_id) || empty($token)) {
      return false;
    }

    $apisearch_client = new ApisearchClient('https://eu1.apisearch.io', 'v1');
    $apisearch_client->setCredentials($app_id, $index_id, $token);

    return $apisearch_client;
  }

  public function getPeticiones() {
    $apisearch_client = $this->conn();

    if ($apisearch_client === false) {
      return 0;
    }

//    echo'<pre>';print_r($apisearch_client->getUsage());die;
    return 10;
  }

  public function buildItems($products, $id_lang) {
    $only_one = count($products) > 1 ? 0 : 1;

    if (!isset($id_lang))
      $id_lang = Context::getContext()->language->id;

    $version = rand();

    $items = array();
    foreach ($products as $product) {
      $item = new Product($product['id_product'], true, $id_lang);

      if (Shop::isFeatureActive()) {
        if (empty(Configuration::get('AS_SHOP')))
          continue;

        $assoc = json_decode(Configuration::get('AS_SHOP'), 1);
        if (!isset($assoc['shop']) || $assoc['shop'] == false)
          continue;

        $shops_product = array_column(Product::getShopsByProduct($product['id_product']), 'id_shop');
        if (!in_array(Context::getContext()->shop->id, $shops_product)) {
          $shops_assoc = $assoc['shop'];
          $shops = array_intersect($shops_product, $shops_assoc);
          $item = new Product($product['id_product'], true, $id_lang, reset($shops));
        }
      }
      
      $available = $this->getAvailability($item->id, $item->available_for_order, $item->out_of_stock, $item->minimal_quantity);

      $reference = $item->reference;
      $ean13 = $item->ean13;
      $upc = $item->upc;
      $minimal_quantity = $item->minimal_quantity;
      $price = Product::getPriceStatic($item->id, true, null, Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
      $old_price = Product::getPriceStatic($item->id, true, null, Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
      $img = Product::getCover($item->id);
      
      if (!$this->checkImgExists($img['id_image'])) {
        continue;
      }

      $categories = array();
      foreach ($item->getCategories() as $category_id) {
        if ($category_id == Configuration::get('PS_ROOT_CATEGORY') || $category_id == Configuration::get('PS_HOME_CATEGORY'))
          continue;

        $category = new Category($category_id, $id_lang);
        if (Validate::isLoadedObject($category))
          $categories[] = $category->name;
      }

      $attributes = array();
      $features = array();
      if ($item->visibility == 'both' || $item->visibility == 'search') {
        if ($item->hasAttributes()) {
          $combinations = $item->getAttributeCombinations($id_lang);
          foreach ($combinations as $combination) {
            if ($combination['default_on']) {
              $id_product_attribute = $combination['id_product_attribute'];
              $reference = empty($item->reference) ? $combination['reference'] : $item->reference;
              $ean13 = empty($item->ean13) ? $combination['ean13'] : $item->ean13;
              $upc = empty($item->upc) ? $combination['upc'] : $item->upc;
              $minimal_quantity = $combination['minimal_quantity'];
              $price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
              $old_price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
              $available = $this->getAvailability($item->id, $item->available_for_order, $item->out_of_stock, $combination['minimal_quantity'], $combination['id_product_attribute']);

              $combinations_images = $item->getCombinationImages($id_lang);
              if (isset($combinations_images[$combination['id_product_attribute']])) {
                $id_images = array_column($combinations_images[$combination['id_product_attribute']], 'id_image');
                if (!empty($id_images) && !in_array($img['id_image'], $id_images)) {
                  $img = array('id_image' => $id_images[0]);
                }
              }
            }
            if (!isset($attributes[$combination['group_name']]) || (isset($attributes[$combination['group_name']]) && !in_array($combination['attribute_name'], $attributes[$combination['group_name']])))
              $attributes[$combination['group_name']][] = $combination['attribute_name'];
          }
          if (!$available) {
            foreach ($combinations as $combination) {
              if ($this->getAvailability($item->id, $item->available_for_order, $item->out_of_stock, $combination['minimal_quantity'], $combination['id_product_attribute'])) {
                $id_product_attribute = $combination['id_product_attribute'];
                $reference = empty($item->reference) ? $combination['reference'] : $item->reference;
                $ean13 = empty($item->ean13) ? $combination['ean13'] : $item->ean13;
                $upc = empty($item->upc) ? $combination['upc'] : $item->upc;
                $minimal_quantity = $combination['minimal_quantity'];
                $price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'));
                $old_price = Product::getPriceStatic($item->id, true, $combination['id_product_attribute'], Configuration::get('PS_PRICE_DISPLAY_PRECISION'), null, false, false);
                $available = 1;

                $combinations_images = $item->getCombinationImages($id_lang);
                if (isset($combinations_images[$combination['id_product_attribute']])) {
                  $id_images = array_column($combinations_images[$combination['id_product_attribute']], 'id_image');
                  if (!empty($id_images) && !in_array($img['id_image'], $id_images)) {
                    $img = array('id_image' => $id_images[0]);
                  }
                }

                break;
              }
            }
          }
        }

        $item_features = $item->getFrontFeatures($id_lang);
        if (!empty($item_features)) {
          foreach ($item_features as $item_feature) {
            if (!isset($features[$item_feature['name']]) || (isset($features[$item_feature['name']]) && !in_array($item_feature['value'], $features[$item_feature['name']])))
              $features[$item_feature['name']][] = $item_feature['value'];
          }
        }
      }

      $translations = array(
          'availables' => array(
              'trans' => $this->l('Availables'),
              'str' => 'Availables'
          ),
          'not_availables' => array(
              'trans' => $this->l('Not availables'),
              'str' => 'Not availables'
          ),
          'with_discount' => array(
              'trans' => $this->l('With discount'),
              'str' => 'With discount'
          ),
          'without_discount' => array(
              'trans' => $this->l('Without discount'),
              'str' => 'Without discount'
          )
      );
      
      $item_array = array(
          'uuid' => array(
              'id' => $item->id,
              'type' => 'product'
          ),
          'metadata' => array(
              'id_product' => (int) $item->id,
              'id_product_attribute' => isset($id_product_attribute) ? (int) $id_product_attribute : 0,
              'name' => (string) $item->name,
              'description' => (string) $item->description,
              'description_short' => (string) $item->description_short,
              'brand' => (string) $item->manufacturer_name,
              'reference' => (string) $reference,
              'ean' => (string) $ean13,
              'upc' => (string) $upc,
              'price' => (string) Tools::displayPrice($price),
              'old_price' => (string) Tools::displayPrice($old_price),
              'show_price' => !$item->available_for_order && !$item->show_price ? 0 : 1,
              'link' => (string) Context::getContext()->link->getProductLink($item),
              'img' => (string) Context::getContext()->link->getImageLink(isset($item->link_rewrite) ? $item->link_rewrite : $item->name, $img['id_image'], 'home_default'),
              'available' => (bool) $available,
              'with_discount' => $old_price - $price > 0 ? (bool) true : (bool) false,
              'minimal_quantity' => (int) $minimal_quantity
          ),
          'indexed_metadata' => array(
              'as-version' => (int) $version,
              'price' => (float) round($price, 2),
              'categories' => (array) $categories,
              'name' => (string) $item->name,
              'available' => $available ? (string) $this->getTranslation($translations['availables']['str'], $this->name, $id_lang) : (string) $this->getTranslation($translations['not_availables']['str'], $this->name, $id_lang),
              'with_discount' => $old_price - $price > 0 ? (string) $this->getTranslation($translations['with_discount']['str'], $this->name, $id_lang) : (string) $this->getTranslation($translations['without_discount']['str'], $this->name, $id_lang),
              'quantity_discount' => (int) ($old_price - $price),
              'quantity_sold' => (int) $this->getSold($item->id)
          ),
          'searchable_metadata' => array(
              'name' => (string) $item->name,
              'description' => (string) strip_tags($item->description),
              'description_short' => (string) strip_tags($item->description_short),
              'brand' => (string) $item->manufacturer_name,
          ),
          'suggest' => array(
              'name' => (string) $item->name,
          ),
          'exact_matching_metadata' => array((int) $item->id, (string) $reference, (string) $ean13, (string) $upc)
      );

      if ($only_one) {
        $items = $item_array;
        if (!empty($item->manufacturer_name)) {
          $items['indexed_metadata']['brand'] = (string) $item->manufacturer_name;
        }
        if (!empty($item->supplier_name)) {
          $items['indexed_metadata']['supplier'] = (string) $item->supplier_name;
        }
        foreach ($attributes as $attr_name => $attr_values) {
          $items['indexed_metadata'][Tools::link_rewrite($attr_name)] = (array) $attr_values;
        }
        foreach ($features as $feat_name => $feat_values) {
          $items['indexed_metadata'][Tools::link_rewrite($feat_name)] = (array) $feat_values;
        }
      } else {
        $items[$item->id] = $item_array;
        if (!empty($item->manufacturer_name)) {
          $items[$item->id]['indexed_metadata']['brand'] = (string) $item->manufacturer_name;
        }
        if (!empty($item->supplier_name)) {
          $items[$item->id]['indexed_metadata']['supplier'] = (string) $item->supplier_name;
        }
        foreach ($attributes as $attr_name => $attr_values) {
          $items[$item->id]['indexed_metadata'][Tools::link_rewrite($attr_name)] = (array) $attr_values;
        }
        foreach ($features as $feat_name => $feat_values) {
          $items[$item->id]['indexed_metadata'][Tools::link_rewrite($feat_name)] = (array) $feat_values;
        }
      }
    }

    if ($only_one) {
      return $items;
    } else {
      return array('items' => $items, 'as-version' => $version);
    }
  }

  public function getAvailability($id, $available_for_order, $out_of_stock, $minimal_quantity, $combination_id = 0) {
    $available = 0;
    if ($available_for_order) {
      if (Configuration::get('PS_STOCK_MANAGEMENT')) {
        if (Configuration::get('PS_ORDER_OUT_OF_STOCK')) {
          $available = 1;
        } else {
          if (Product::getRealQuantity($id, $combination_id) >= $minimal_quantity || $out_of_stock == 1) {
            $available = 1;
          }
        }
      } else {
        $available = 1;
      }
    }
    return $available;
  }

  public function getTranslation($string, $source, $id_lang) {

    $string = preg_replace("/\\\*'/", "\'", $string);
    
    $iso = Language::getIsoById($id_lang);

    $filesByPriority = array(
        // Translations in theme
        _PS_THEME_DIR_ . 'modules/' . $this->name . '/translations/' . $iso . '.php',
        _PS_THEME_DIR_ . 'modules/' . $this->name . '/' . $iso . '.php',
        // PrestaShop 1.5 translations
        _PS_MODULE_DIR_ . $this->name . '/translations/' . $iso . '.php',
        // PrestaShop 1.4 translations
        _PS_MODULE_DIR_ . $this->name . '/' . $iso . '.php',
    );

    $translations = array();
    $file_exists = false;
    global $_MODULE;
    $_MODULE = array();
    
    foreach ($filesByPriority as $file) {
      if (file_exists($file)) {
        $file_exists = true;
        include $file;
        $translations = !empty($translations) ? array_merge($translations, $_MODULE) : $_MODULE;
      }
    }
    
    if (!$file_exists) {
      return stripslashes($string);
    }

    $key = md5($string);

    $currentKey = strtolower('<{' . $this->name . '}' . _THEME_NAME_ . '>' . $source) . '_' . $key;
    $defaultKey = strtolower('<{' . $this->name . '}prestashop>' . $source) . '_' . $key;

    if ('controller' == substr($source, -10, 10)) {
      $file = substr($source, 0, -10);
      $currentKeyFile = strtolower('<{' . $this->name . '}' . _THEME_NAME_ . '>' . $file) . '_' . $key;
      $defaultKeyFile = strtolower('<{' . $this->name . '}prestashop>' . $file) . '_' . $key;
    }

    if (isset($currentKeyFile) && !empty($translations[$currentKeyFile])) {
      $ret = stripslashes($translations[$currentKeyFile]);
    } elseif (isset($defaultKeyFile) && !empty($translations[$defaultKeyFile])) {
      $ret = stripslashes($translations[$defaultKeyFile]);
    } elseif (!empty($translations[$currentKey])) {
      $ret = stripslashes($translations[$currentKey]);
    } elseif (!empty($translations[$defaultKey])) {
      $ret = stripslashes($translations[$defaultKey]);
    } else {
      $ret = stripslashes($string);
    }

    return $ret;
  }
  
  public function getSold($id_product) {
    return Db::getInstance()->getValue('
                        SELECT COUNT(od.product_quantity - od.product_quantity_refunded - od.product_quantity_return - od.product_quantity_reinjected)
                        FROM ' . _DB_PREFIX_ . 'order_detail od
                        LEFT JOIN ' . _DB_PREFIX_ . 'orders o ON (o.id_order = od.id_order)
                        LEFT JOIN ' . _DB_PREFIX_ . 'order_state os ON (os.id_order_state = o.current_state)
                        WHERE od.product_id = ' . $id_product . '
                        AND o.valid = 1
                        AND os.logable = 1
                        AND os.paid = 1'
    );
  }
  
  public function checkImgExists($id_image) {
    $image = new Image($id_image);
    
    if (Configuration::get('PS_LEGACY_IMAGES') && file_exists(_PS_PROD_IMG_DIR_ . $image->id_product . '-' . $image->id . '.' . $image->image_format)) {
      return true;
    } elseif (file_exists(_PS_PROD_IMG_DIR_ . $image->getImgPath() . '.' . $image->image_format)) {
      return true;
    }
    
    return false;
  }

}
