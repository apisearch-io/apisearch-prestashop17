{if null != $group_id}
	{assign var='user_type' value=$group_id}
	{if null != $customer_id}
		{assign var='user_type' value=$customer_id|cat:'||'|cat:$user_type}
	{/if}
	{assign var='group_param' value='user-type='|cat:$user_type}
{else}
	{assign var='group_param' value=''}
{/if}

<link href="https://eu1.apisearch.cloud" rel="dns-prefetch" crossOrigin="anonymous">
<script
		type="application/javascript"
		src='{$apisearch_admin_url}/{$apisearch_index_id}.layer.js?{$group_param}'
		charSet='UTF-8'
		async defer
		crossOrigin="anonymous"
></script>

