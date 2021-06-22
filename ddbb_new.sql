SELECT p.*, t.`id_lang`, t.`name`
FROM `ps_product` p
        LEFT JOIN `ps_product_lang` `b` ON p.`id_product` = b.`id_product` AND b.`id_lang` = 4
        LEFT JOIN `ps_product_shop` `c` ON p.`id_product` = c.`id_product` AND c.`id_shop` = 1
        LEFT JOIN `ps_tag` `t` ON p.id_product = pt.`id_product`
        INNER JOIN ps_product_shop product_shop
                   ON (product_shop.id_product = p.id_product AND product_shop.id_shop = 1)
WHERE (p.`id_product` = 1) AND (b.`id_shop` = 1) LIMIT 1


SELECT t.`id_lang`, t.`name`
FROM ps_tag t
         LEFT JOIN ps_product_tag pt ON (pt.id_tag = t.id_tag)
WHERE pt.`id_product`=1



SELECT `name`
FROM `ps_manufacturer`
WHERE `id_manufacturer` = 1
  AND `active` = 1 LIMIT 1



SELECT `name` FROM `ps_supplier` WHERE `id_supplier` = 0 LIMIT 1



SELECT `id_hook`, `name`
FROM `ps_hook`
UNION
SELECT `id_hook`, ha.`alias` as name
FROM `ps_hook_alias` ha
         INNER JOIN `ps_hook` h ON ha.name = h.name



SELECT h.id_hook, h.name as h_name, title, description, h.position, hm.position as hm_position, m.id_module, m.name, active
FROM `ps_hook_module` hm
    STRAIGHT_JOIN `ps_hook` h ON (h.id_hook = hm.id_hook AND hm.id_shop = 1)
    STRAIGHT_JOIN `ps_module` as m ON (m.id_module = hm.id_module)
ORDER BY hm.position




SELECT tr.*
FROM `ps_tax_rule` tr
         JOIN `ps_tax_rules_group` trg ON (tr.`id_tax_rules_group` = trg.`id_tax_rules_group`)
WHERE trg.`active` = 1
  AND tr.`id_country` = 4
  AND tr.`id_tax_rules_group` = 1
  AND tr.`id_state` IN (0, 0)
  AND ('0' BETWEEN tr.`zipcode_from` AND tr.`zipcode_to`
    OR (tr.`zipcode_to` = 0 AND tr.`zipcode_from` IN(0, '0')))
ORDER BY tr.`zipcode_from` DESC, tr.`zipcode_to` DESC, tr.`id_state` DESC, tr.`id_country` DESC



SELECT *
FROM `ps_tax` a
WHERE (a.`id_tax` = 1) LIMIT 1



SELECT *
FROM `ps_tax_lang`
WHERE `id_tax` = 1



SELECT p.id_product
FROM `ps_product` p
         INNER JOIN ps_product_shop product_shop
                    ON (product_shop.id_product = p.id_product AND product_shop.id_shop = 1)
WHERE p.id_product = 1
  AND DATEDIFF(
              product_shop.`date_add`,
              DATE_SUB(
                      "2021-05-07 00:00:00",
                      INTERVAL 20 DAY
                )
          ) > 0


SELECT product_attribute_shop.id_product_attribute
FROM ps_product_attribute pa
         INNER JOIN ps_product_attribute_shop product_attribute_shop
                    ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute AND product_attribute_shop.id_shop = 1)
WHERE pa.id_product = 1 LIMIT 1



SELECT product_attribute_shop.id_product_attribute
FROM ps_product_attribute pa
         INNER JOIN ps_product_attribute_shop product_attribute_shop
                    ON (product_attribute_shop.id_product_attribute = pa.id_product_attribute AND product_attribute_shop.id_shop = 1)
WHERE product_attribute_shop.default_on = 1  AND pa.id_product = 1 LIMIT 1



SELECT 1 FROM `ps_specific_price` WHERE id_product = 0 LIMIT 1



SELECT 1 FROM `ps_specific_price` WHERE id_product = 1 LIMIT 1



SELECT COUNT(DISTINCT `id_product`) FROM `ps_specific_price` WHERE `id_product` != 0 LIMIT 1



SELECT DISTINCT `id_product` FROM `ps_specific_price` WHERE `id_product` != 0



SELECT COUNT(DISTINCT `id_product_attribute`) FROM `ps_specific_price` WHERE `id_product_attribute` != 0 LIMIT 1



SELECT 1 FROM `ps_specific_price` WHERE `from` BETWEEN '2021-05-07 00:00:00' AND '2021-05-07 23:59:59' LIMIT 1



SELECT 1 FROM `ps_specific_price` WHERE `to` BETWEEN '2021-05-07 00:00:00' AND '2021-05-07 23:59:59' LIMIT 1



SELECT `priority`, `id_specific_price_priority`
FROM `ps_specific_price_priority`
WHERE `id_product` = 1
ORDER BY `id_specific_price_priority` DESC LIMIT 1



SELECT *, ( IF (`id_group` = 1, 2, 0) +  IF (`id_country` = 4, 4, 0) +  IF (`id_currency` = 1, 8, 0) +  IF (`id_shop` = 1, 16, 0) +  IF (`id_customer` = 0, 32, 0)) AS `score`
FROM `ps_specific_price`
WHERE
        `id_shop` IN (0, 1) AND
        `id_currency` IN (0, 1) AND
        `id_country` IN (0, 4) AND
        `id_group` IN (0, 1) AND `id_product` = 1 AND `id_customer` = 0 AND `id_product_attribute` = 0 AND `id_cart` = 0  AND (`from` = '0000-00-00 00:00:00' OR '2021-05-07 00:00:00' >= `from`) AND (`to` = '0000-00-00 00:00:00' OR '2021-05-07 00:00:00' <= `to`)
  AND IF(`from_quantity` > 1, `from_quantity`, 0) <= 1 ORDER BY `id_product_attribute` DESC, `id_cart` DESC, `from_quantity` DESC, `id_specific_price_rule` ASC, `score` DESC, `to` DESC, `from` DESC LIMIT 1


SELECT product_shop.`price`, product_shop.`ecotax`,
       IFNULL(product_attribute_shop.id_product_attribute,0) id_product_attribute, product_attribute_shop.`price` AS attribute_price, product_attribute_shop.default_on
FROM `ps_product` p
         INNER JOIN `ps_product_shop` `product_shop` ON (product_shop.id_product=p.id_product AND product_shop.id_shop = 1)
         LEFT JOIN `ps_product_attribute_shop` `product_attribute_shop` ON (product_attribute_shop.id_product = p.id_product AND product_attribute_shop.id_shop = 1)
WHERE (p.`id_product` = 1)



SELECT `id_tax_rules_group`
FROM `ps_product_shop`
WHERE `id_product` = 1 AND id_shop=1 LIMIT 1


SELECT `reduction`
FROM `ps_product_group_reduction_cache`
WHERE `id_product` = 1 AND `id_group` = 1 LIMIT 1


SELECT `reduction`
FROM `ps_group`
WHERE `id_group` = 1 LIMIT 1


SELECT t.`id_lang`, t.`name`
FROM ps_tag t
         LEFT JOIN ps_product_tag pt ON (pt.id_tag = t.id_tag)
WHERE pt.`id_product`=1


SELECT SUM(quantity)
FROM `ps_stock_available`
WHERE (id_product = 1) AND (id_product_attribute = 0) AND (id_shop = 1) AND (id_shop_group = 0) LIMIT 1


SELECT out_of_stock
FROM `ps_stock_available`
WHERE (id_product = 1) AND (id_product_attribute = 0) AND (id_shop = 1) AND (id_shop_group = 0) LIMIT 1


SELECT depends_on_stock
FROM `ps_stock_available`
WHERE (id_product = 1) AND (id_product_attribute = 0) AND (id_shop = 1) AND (id_shop_group = 0) LIMIT 1


SELECT location
FROM `ps_stock_available`
WHERE (id_product = 1) AND (id_product_attribute = 0) AND (id_shop = 1) AND (id_shop_group = 0) LIMIT 1


SELECT cl.`link_rewrite`
FROM `ps_category_lang` cl
WHERE `id_lang` = 4
  AND cl.id_shop = 1
  AND cl.`id_category` = 4 LIMIT 1
