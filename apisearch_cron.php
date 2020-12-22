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
include_once dirname(__FILE__) . '/../../config/config.inc.php';
include_once dirname(__FILE__) . '/../../init.php';

//ONLY FOR TESTING

require_once _PS_MODULE_DIR_ . 'apisearch/apisearch.php';

$apisearch = new Apisearch();

$indexs = array();
foreach (Context::getContext()->language->getLanguages(true, Context::getContext()->shop->id) as $lang) {
  $index_id = Configuration::get('AS_INDEX', $lang['id_lang']);
  if (in_array($index_id, $indexs))
    continue;

  $indexs[] = $index_id;

  $apisearch_client = $apisearch->conn($lang['id_lang']);

  if ($apisearch_client === false)
    continue;

  if (Shop::isFeatureActive()) {
    if (empty(Configuration::get('AS_SHOP')))
      continue;

    $assoc = json_decode(Configuration::get('AS_SHOP'), 1);
    if (!isset($assoc['shop']) || $assoc['shop'] == false)
      continue;

    $shop_og = Context::getContext()->shop->id;
    $products = array();
    foreach ($assoc['shop'] as $shop_id) {
      Shop::setContext(Shop::getContext(), $shop_id);
      if (!isset($products_old)) {
        $count = 100;
        $offset = 0;
        $products_old_total = array();
        while (true) {
          $products_old = Product::getProducts($lang['id_lang'], $offset, $count, 'id_product', 'asc', false, true);
          if (!empty($products_old)) {
            $products_old_total = array_merge($products_old_total, $products_old);
            $count = count($products_old);
            $offset = $offset + $count;
          } else {
            break;
          }
        }
        $products = $products_old_total;
      } else {
        $count = 100;
        $offset = 0;
        $products_new_total = array();
        while (true) {
          $products_new = Product::getProducts($lang['id_lang'], $offset, $count, 'id_product', 'asc', false, true);
          if (!empty($products_new)) {
            $products_new_total = array_merge($products_new_total, $products_new);
            $count = count($products_new);
            $offset = $offset + $count;
          } else {
            break;
          }
        }
        $products_new = $products_new_total;

        $mix_products = array_merge($products_old, $products_new);
        $products = array_intersect_key($mix_products, array_unique(array_column($mix_products, 'id_product')));
        $products_old = $products;
      }
    }
    Shop::setContext(Shop::getContext(), $shop_og);
  } else {
    $count = 100;
    $offset = 0;
    $products_total = array();
    while (true) {
      $products = Product::getProducts($lang['id_lang'], $offset, $count, 'id_product', 'asc', false, true);
      if (!empty($products)) {
        $products_total = array_merge($products_total, $products);
        $count = count($products);
        $offset = $offset + $count;
      } else {
        break;
      }
    }
    $products = $products_total;
  }

  $result = $apisearch->buildItems($products, $lang['id_lang']);

  $items = $result['items'];
  $version = $result['as-version'];

  if (!empty($items)) {
    $apisearch_client->putItems($items);
    $apisearch_client->flush();

    $apisearch_client->deleteItemsByQuery(array(
        'q' => '',
        'filters' => array(
            'version' => array(
                'field' => 'indexed_metadata.as-version',
                'values' => array($version),
                'application_type' => 16
            )
        )
    ));
  }
}