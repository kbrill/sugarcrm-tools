<script type="text/javascript" language="javascript" src="custom/modules/Administration/SweetDBAdmin/DataTables/media/js/jquery.dataTables.js"></script>
<style type="text/css" title="currentStyle">
	@import "custom/modules/Administration/SweetDBAdmin/DataTables/media/css/demo_page.css";
	@import "custom/modules/Administration/SweetDBAdmin/DataTables/media/css/demo_table.css";
</style>
{if $smarty.request.command != "indexes"}
	{literal}
	<script type="text/javascript" charset="utf-8">
		$(document).ready(function() {
			$('#datatable').dataTable({
				"iDisplayLength": 10,
				"sPaginationType": "full_numbers",
				"sDom": '<"top"if>tpl',
				"fnDrawCallback": function(){
					$('table#datatable td').bind('mouseenter', function () { $(this).parent().children().each(function(){$(this).addClass('datatablerowhighlight');}); });
					$('table#datatable td').bind('mouseleave', function () { $(this).parent().children().each(function(){$(this).removeClass('datatablerowhighlight');}); });
				}
			});
		} );
	</script>
	{/literal}
{/if}
{if $QUERY_TIME}
Query Execution Time: {$QUERY_TIME} seconds.
{/if}
<div id="demo">
{if $smarty.request.command == "indexes"}
	<table class="list view" id="datatable">
{else}
	<table cellpadding="0" cellspacing="0" border="0" class="display" id="datatable" width="100%">
{/if}
<thead>
<tr>
{if $ACTIONS}
	<th width=25>&nbsp;</th>
	<th width=25>&nbsp;</th>
{/if}
{foreach key=key item=item from=$HEADER_ARRAY}
    <th>{$item}</th>
{/foreach}
</tr>
</thead>
<tbody>
{foreach item=item from=$DATA_ARRAY}
	<tr>
        {foreach key=key2 item=item2 from=$item name=data}
            {if $smarty.foreach.data.first}
	            {if $ACTIONS}
	                {if isset($item.id_c)}
	                     {assign var=itemid value=$item.id_c}
	                {/if}
	                {if isset($item.id)}
	                     {assign var=itemid value=$item.id}
	                {/if}
	                <td>
                        <a title='{$mod.LBL_SWEETDBADMIN_EDIT}' id='edit{$itemid|strip_tags|trim}' href='#' onclick="document.sql.currentTable.value='{$TABLE}';document.sql.main.value='{$MAIN}';document.sql.id.value='{$itemid|strip_tags|trim}';document.sql.command.value='{$EDIT_COMMAND}';document.sql.submit();">
                            <img src="custom/modules/Administration/SweetDBAdmin/images/edit_inline.png" alt="Edit"></a>
                    </td>
                    <td>
                        <a title='{$mod.LBL_SWEETDBADMIN_DELETE}' id='delete{$itemid|strip_tags|trim}' onclick="document.sql.currentTable.value='{$TABLE}';document.sql.main.value='{$MAIN}';document.sql.id.value='{$itemid|strip_tags|trim}';document.sql.command.value='{$DELETE_COMMAND}';document.sql.submit();" href='#'>
                            <img src="custom/modules/Administration/SweetDBAdmin/images/delete_inline.png" alt="Delete"></a>
                    </td>
                {/if}
            {/if}
            <td>
                {$item2}
            </td>
        {/foreach}
    </tr>
{/foreach}
</tbody>
</table>
</div>
