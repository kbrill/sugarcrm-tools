{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<input type='hidden' name='command' value='{$COMMAND}'>
<script type="text/javascript" language="javascript" src="custom/modules/Administration/SweetDBAdmin/DataTables/media/js/jquery.dataTables.js"></script>
<style type="text/css" title="currentStyle">
	@import "custom/modules/Administration/SweetDBAdmin/DataTables/media/css/demo_page.css";
	@import "custom/modules/Administration/SweetDBAdmin/DataTables/media/css/demo_table.css";
</style>
{literal}
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('#datatable').dataTable({
			"iDisplayLength": 10,
			"aoColumnDefs": [
				{ "bSortable": false, "bSearchable": false, "aTargets": [ 0 ] },
				{ "bSortable": true, "bSearchable": false, "aTargets": [ 0 ] },
				{ "bSortable": true, "aTargets": [ 0 ] }
			],
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
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="datatable" width="100%">
	<thead><tr><td>&nbsp;</td><td><b>{$mod.LBL_RUN_COUNT}</b></td><td>{$mod.LBL_HISTORY_ITEMS}</td></tr></thead>
	{foreach from=$SQLHISTORY key=k item=v}
	{if $v|stristr:$TABLE or $TABLE eq ''}
		<tr class='{cycle values="evenListRowS1,oddListRowS1"}'>
			<td>
				<a title='{$mod.LBL_SWEETDBADMIN_DELETE}' id='delete{$k}' href='index.php?module=Administration&action=SweetDBAdmin&command=historyDelete&id={$k}'>
					<img title='{$mod.LBL_SWEETDBADMIN_DELETE}' src='custom/modules/Administration/SweetDBAdmin/images/delete_inline.png?v={$k}' alt='{$mod.LBL_SWEETDBADMIN_DELETE}' border='0'></a>
			</td>
			<td>
				{$v.count}
			</td>
			<td>
				<a href='index.php?module=Administration&action=SweetDBAdmin&command=historyQuery&id={$k}'>
				{$v.query}
				</a>
			</td>
		</tr>
	{/if}
{/foreach}
</table>