{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<input type='hidden' name='command' value='searchAllTables'>
<table width=640 align="center" cellspacing="10">
	<tr>
		<td colspan="4" align="center">
			<b><u>Search All Tables</u></b>
		</td>
	</tr>
	<tr>
		<td align=right>
			Search For:
		</td>
		<td>
			<input name="searchPattern" size="30" value="" type="text">&nbsp;('%' & '_' are the wildcards)
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">
			Find:
		</td>
		<td><input name="search_option" id="search_option_1" value="1" type="radio">
			<label for="search_option_1">at least one of the words*</label><br>
			<input name="search_option" id="search_option_2" value="2" type="radio">
			<label for="search_option_2">all words*</label><br>
			<input name="search_option" id="search_option_3" value="3" checked="checked" type="radio">
			<label for="search_option_3">the exact phrase</label><br>
			<sub>* Words are separeted by spaces</sub>
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">
			Inside table(s):
		</td>
		<td rowspan="2">
			<select id="tableselect" name="tableselect[]" size="6" multiple="multiple">
			{$OPTIONS}
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" valign="bottom">
			<a href="#"
			   onclick="selectAll('tableselect', true); return false;">Select All</a><br><a
				href="#"
				onclick="selectAll('tableselect', false); return false;">Unselect All</a>
		</td>
	</tr>
	<tr>
		<td align="right" valign="top">
			Inside columns:
		</td>
		<td rowspan="2">
			<select id="columnNames" name="columnNames[]" size="6" multiple="multiple">
			{html_options options=$COLUMNS}
			</select>
		</td>
	</tr>
	<tr>
		<td align="right" valign="bottom">
			<a href="#"
			   onclick="selectAllColumns('columnNames', 'date'); return false;">Select All Date Fields</a><br><a
				href="#"
				onclick="selectAllColumns('columnNames', 'id'); return false;">Select All ID Fields</a>
		</td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<button id='querySubmit' onclick="SUGAR.ajaxUI.showLoadingPanel();">{$mod.LBL_SWEETDBADMIN_SUBMIT}</button>
		</td>
	</tr>
</table>