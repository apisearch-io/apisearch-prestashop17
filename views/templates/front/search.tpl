{if null != $group_id}
	{assign var='group_param' value='?user-type='|cat:$group_id}
{else}
	{assign var='group_param' value=''}
{/if}

<link href="https://eu1.apisearch.cloud" rel="dns-prefetch" crossOrigin="anonymous">
<script
		type="application/javascript"
		src='{$apisearch_admin_url}/{$apisearch_index_id}.layer.js{$group_param}'
		charSet='UTF-8'
		async defer
		crossOrigin="anonymous"
></script>

