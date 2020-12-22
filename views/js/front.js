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
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

const as_snippet = '//admin.apisearch.io/' + index_id + '.js?' +
        'static_token=' + static_token +
        '&url_cart=' + url_cart +
        '&url_search=' + url_search +
        '&show_more=' + show_more +
        '&show_less=' + show_less +
        '&results=' + results +
        '&empty_results=' + empty_results +
        '&clear_filters=' + clear_filters +
//        '&user_id=' + user_id +
        '&add_to_cart=' + add_to_cart;
(function (d, t) {
  var f = d.createElement(t), s = d.getElementsByTagName(t)[0];
  f.src = ('https:' == location.protocol ? 'https:' : 'http:') + as_snippet;
  f.setAttribute('charset', 'utf-8');
  f.setAttribute('defer', '');
  s.parentNode.insertBefore(f, s)
}(document, 'script'));