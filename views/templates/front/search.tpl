{if null != $group_id}
	{assign var='user_type' value=$group_id}
	{if null != $customer_id}
		{assign var='user_type' value=$customer_id|cat:'||'|cat:$user_type}
	{/if}
	{assign var='group_param' value='user-type='|cat:$user_type}
{else}
	{assign var='group_param' value=''}
{/if}

{if $real_time_prices}
<script>
	window.apisearchItemsTransformation = async (items) => {
		console.log(items);
		const ids = items.map((item) => item.uuid.id).join(",");
		const url = "{$base_url}modules/apisearch/prices.php?ids=" + ids;
		const response = await fetch(url);
		const prices = await response.json();

		return items.map((item) => {
			const itemId = item.uuid.id;
			if (typeof prices[itemId] !== "undefined") {
				const price = prices[itemId];
				item.indexedMetadata.price = price["p"];
				item.metadata.price_with_currency = price["p_c"];
				item.metadata.old_price = price["op"];
				item.metadata.old_price_with_currency = price["pp_c"];
				item.indexedMetadata.with_discount = price["wd"];
				item.indexedMetadata.discount_percentage = price["dp"];
			}

			return item;
		})
	}
</script>
{/if}

<link href="https://eu1.apisearch.cloud" rel="dns-prefetch" crossOrigin="anonymous">
<script
		type="application/javascript"
		src='{$apisearch_admin_url}/{$apisearch_index_id}.layer.js?{$group_param}'
		charSet='UTF-8'
		async defer
		crossOrigin="anonymous"
></script>

