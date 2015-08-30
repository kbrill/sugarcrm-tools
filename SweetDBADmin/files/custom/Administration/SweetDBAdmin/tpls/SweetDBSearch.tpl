{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<script type="text/javascript"src="custom/modules/Administration/SweetDBAdmin/js/datepickr.js"></script>
<script type="text/javascript" src="custom/modules/Administration/SweetDBAdmin/js/simpleAutoComplete.js"></script>
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/datepicker.css" />
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/simpleAutoComplete.css" />
<input type='hidden' name='command' value='{$COMMAND}'>
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<input type='hidden' name='table' value=''>
<table>
	<tr>
		<td align='center' colspan='2' style="border-bottom: 1px solid black; padding: 5px;">
			{$mod.LBL_TABLE_TO_SEARCH}:
			<select name=tableselect onchange="chooseNewTable('search');">
				{$OPTIONS}
			</select>
		</td>
	</tr>
	<tr>
		<td align='center' valign='top' style="padding-right:30px;">
			{$mod.LBL_SWEETDBADMIN_SELECT_COLUMNS}:<br>
			<select id=columns name=columns[] multiple style='height:300px; width: 350px'>
				{$COLUMNS}
			</select>
			<br>
				<input type=button name='most' value='Select Most Used' onclick="selectMostUsed();return false;">
				&nbsp;
				<input type=button name='clear' value='Clear' onclick="resetSelect('columns');return false;">
			<br><br>
			{$mod.LBL_SWEETDBADMIN_ADDJOIN}:<br>
			<select id=joins name=joins[] multiple style='height:300px; width: 350px'>
				{$JOINS}
			</select>

		</td>
		<td align='left' valign='top' style="border-left: 1px solid black; padding: 25px;">
			<button id='search1Submit' name='search1Submit' onclick="submitPage();">{$mod.LBL_SWEETDBADMIN_SUBMIT}</button>
			<table id='detailpanel_1'>
				{$SEARCHFIELDS}
			</table>
			<button id='search2Submit' name='search2Submit' onclick="submitPage();">{$mod.LBL_SWEETDBADMIN_SUBMIT}</button>
		</td>
	</tr>
</table>
</form>