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

<script type="application/javascript">
  $(document).on('click', 'label[for="AS_REAL_TIME_INDEXATION_on"]', function() {
    $('.real-time').removeClass('hidden');
  });

  $(document).on('click', '#AS_REAL_TIME_INDEXATION_off', function() {
    $('.real-time').addClass('hidden');
  });
</script>

{if Configuration::get('AS_APP')}
  {if Shop::isFeatureActive()}
    {assign var=shopAsso value=Configuration::get('AS_SHOP')|json_decode:1}
    <script>
      {literal}
          $(function () {
            var groups = {/literal}{$shopAsso.group|array_values|json_encode}{literal};
            var shops = {/literal}{$shopAsso.shop|array_values|json_encode}{literal};
            $('#shop-tree input[type="checkbox"]').each(function () {
              if (($(this).attr('name').indexOf("checkBoxShopGroupAsso_module") != -1 && groups != null && groups.includes($(this).val())) || ($(this).attr('name').indexOf("checkBoxShopAsso_module") != -1 && shops != null && shops.includes($(this).val()))) {
                $(this).prop('checked', true);
              }
            });
          });
      {/literal}
    </script>
  {/if}
  <script>
    {literal}
    $(document).on('click', '#as-sync-container #as-sync', function () {
      $.ajax({
        type: 'POST',
        url: '{/literal}{$module_dir|escape:'htmlall':'UTF-8'}{literal}' + 'ajax.php' + '?rand=' + new Date().getTime(),
        async: true,
        cache: false,
        timeout: 600000, // 10 minutes timeout
        dataType: "json",
        headers: {"cache-control": "no-cache"},
        data: {
          method: 'syncProducts',
          ajax: true
        },
        beforeSend: function () {
          $('#as-sync-container #as-ajax').show();
        },
        success: function (jsonData) {
          $('#as-sync-container #as-ajax').hide();
        },
        error: function (jsonData) {
          $('#as-sync-container #as-ajax').hide();
        }
      });
    });
    {/literal}
  </script>
  <style>
    #as-sync-container {
      margin-bottom: 10px;
    }
    #as-sync-container #as-sync {
      background-color: #00aff0;
      color: #fff;
      text-transform: uppercase;
      border: 1px solid #2eacce;
      border-radius: 3px;
      cursor: pointer;
      display: inline-block;
      font-size: 12px;
      font-weight: 400;
      line-height: 17px;
      margin-bottom: 0;
      padding: 6px 8px;
      text-align: center;
      user-select: none;
      vertical-align: middle;
      white-space: nowrap;
    }
    #as-sync-container #as-sync:hover {
      background-color: #008abd;
      box-shadow: none;
    }
    #as-sync-container #as-ajax {
      display: none;
    }

    .hidden {
      display: none;
    }
  </style>
{/if}
