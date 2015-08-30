{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<script src="custom/modules/Administration/SweetDBAdmin/js/jquery.chained.remote.js" type="text/javascript" charset="utf-8"></script>
{literal}
{/literal}
    <input type='hidden' name='command' value='query'>
    <input type='hidden' name='id' value=''>
    <input type='hidden' name='main' value=''>
    <input type='hidden' name='to_pdf' value=''>
    <input type='hidden' name='user_dates' value='{$smarty.request.user_dates}'>
    <input type='hidden' name='user_ids' value='{$smarty.request.user_ids}'>
    <input type='hidden' name='translated_column_names' value='{$smarty.request.translated_column_names}'>
<div id="textarea" style="display:{$SHOWTEXTAREA}">
	<table>
		<tr>
			<td valign="top">
				<textarea id=sqlarea name=sql rows='20' cols='160'>{$SQL}</textarea>
			</td>
			<td valign="top">
				<input onclick="processInsert('select');" type="button" name="Select" id="Select" value="Select" style="width: 80px;"><br />
				<input onclick="processInsert('update');" type="button" name="Update" id="Update" value="Update" style="width: 80px;"><br />
				<input onclick="processInsert('insert');" type="button" name="Insert" id="Insert" value="Insert" style="width: 80px;"><br />
				<input onclick="processInsert('delete');" type="button" name="Delete" id="Delete" value="Delete" style="width: 80px;"><br />
				<input onclick="processInsert('clear');" type="button" name="Clear" id="Clear" value="Clear" style="width: 80px;"><br />
				<input onclick="processInsert('copy');" type="button" name="Copy" id="Copy" value="<<<" style="width: 80px;"><br />
				<br />
				<br />
				<u>{$mod.LBL_OPTION_SWITCHES}</u>
				<br />
				{if $smarty.request.user_dates == 1}
					<input onclick="processInsert('dates');" type="button" name="Dates" id="date_switch" value="{$mod.LBL_USER_DATES}" style="width: 80px;"><br />
				{else}
					<input onclick="processInsert('dates');" type="button" name="Dates" id="date_switch" value="{$mod.LBL_DB_DATES}" style="width: 80px;"><br />
				{/if}
				{if $smarty.request.user_ids == 1}
					<input onclick="processInsert('users');" type="button" name="Users" id="user_switch" value="{$mod.LBL_USER_NAMES}" style="width: 80px;"><br />
				{else}
					<input onclick="processInsert('users');" type="button" name="Users" id="user_switch" value="{$mod.LBL_USER_IDS}" style="width: 80px;"><br />
				{/if}
				<br />
				<u>{$mod.LBL_COLUMN_NAMES}</u>
				<br />
				{if $smarty.request.translated_column_names == 1}
					<input onclick="processInsert('columns');" type="button" name="Users" id="column_switch" value="Translated" style="width: 80px;"><br />
				{else}
					<input onclick="processInsert('columns');" type="button" name="Users" id="column_switch" value="Untranslated" style="width: 80px;"><br />
				{/if}
			</td>
			<td>
				<select name=table id=tableselectID>
					{$OPTIONS}
				</select>
				<br>
				{$mod.LBL_SWEETDBADMIN_SELECT_COLUMNS_ADD}:<br>
				<select id=columnsID name=columns[] multiple style='height:290px; width: 350px'>
					{$COLUMNS}
				</select>
			</td>
			<td valign="top">
                &nbsp;
			</td>
		</tr>
		<tr>
			<td colspan="3">
			    <button id='querySubmit' onclick="SUGAR.ajaxUI.showLoadingPanel();">{$mod.LBL_SWEETDBADMIN_SUBMIT}</button>&nbsp;&nbsp;&nbsp;&nbsp;Show <input type="text" name=numrecords id=numrecords value="{$NUM_RECORDS}"> records
			</td>
		</tr>
	</table>
</div>
{if $SHOWTEXTAREA eq "none"}
	<div id="highlightedsql" style="display:block">
		{$HIGHLIGHTED_SQL}
		{if !$HIDEEDITBUTTON}
			<br />
			<button  onclick = "toggleToTextArea(); return false">{$mod.LBL_EDIT_QUERY}</button>
            {if $ISDATA}
                <button  onclick = "exportQueryToCSV(); return false">{$mod.LBL_EXPORT_TO_CSV}</button>
            {/if}
            <button  onclick = "javascript:location.reload(true)">{$mod.LBL_RERUN_QUERY}</button>
		{/if}
	</div>
{/if}
{if !$HIDEEDITBUTTON}
	<hr>
{/if}
{$EXTRA_CONTROLS}
{if $ISDATA=='1'}
    {include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBTable.tpl"}
{/if}
{$ERROR}
<div id='lineHolder'></div>
<script type="text/javascript">
$("#columnsID").remoteChained("#tableselectID", "index.php?module=Administration&action=SweetDBAdmin&skip=0&sql=&command=getColumnsNames&to_pdf=1");
</script>