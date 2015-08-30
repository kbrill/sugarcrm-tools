{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<script type="text/javascript" language="javascript" src="custom/modules/Administration/SweetDBAdmin/DataTables/media/js/jquery.dataTables.js"></script>
<style type="text/css" title="currentStyle">
	@import "custom/modules/Administration/SweetDBAdmin/DataTables/media/css/demo_page.css";
	@import "custom/modules/Administration/SweetDBAdmin/DataTables/media/css/demo_table.css";
</style>
{literal}
<script type="text/javascript" charset="utf-8">
	$(document).ready(function() {
		$('#datatable').dataTable({
			"iDisplayLength": 5,
			"bLengthChange": true,
			"oLanguage": {
				"sLengthMenu": 'Display <select>'+
						'<option value="5">5</option>'+
						'<option value="10">10</option>'+
						'<option value="20">20</option>'+
						'<option value="30">30</option>'+
						'<option value="40">40</option>'+
						'<option value="-1">All</option>'+
						'</select> records',
			},
			"aoColumnDefs": [
				{ "bSortable": false, "bSearchable": false, "aTargets": [ 0 ] },
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
<input type='hidden' name='command' value='{$COMMAND}'>
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<select name=logFile onchange="document.sql.command.value='readLog';document.sql.submit();">
{$OPTIONS}
</select>
<table cellpadding="0" cellspacing="0" border="0" class="display" id="datatable" width="100%">
	<thead>
		<tr>
			<td>&nbsp;</td>
            <td>Date</td>
            <td>PID</td>
            <td>User Name</td>
            <td>Query Time</td>
            <td>Queries</td>
        </tr>
	</thead>
{foreach from=$SQLLOG key=k item=v name=dataLoop}
	<tr class='{cycle values="evenListRowS1,oddListRowS1"}'>
		<td valign="top">
			<a title='{$mod.LBL_SWEETDBADMIN_RUN}' id='run{$smarty.foreach.dataLoop.iteration}' href='index.php?module=Administration&action=SweetDBAdmin&command=runLogQuery&query={$k}'>
				<img title='{$mod.LBL_SWEETDBADMIN_RUN}' src='custom/modules/Administration/SweetDBAdmin/images/CustomQueries.gif?v={$smarty.foreach.dataLoop.iteration}' alt='{$mod.LBL_SWEETDBADMIN_RUN}' border='0'></a>
		</td>
        <td valign="top">
            {$v.date}
        </td>
        <td valign="top">
            {$v.logData}
        </td>
        <td valign="top">
            {$v.user}
        </td>
        <td valign="top">
            {$v.query_time}
        </td>
		<td valign="top">
            {$v.sql}
		</td>
	</tr>
{/foreach}
</table>
<div id='lineHolder'></div>