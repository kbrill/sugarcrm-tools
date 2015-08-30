{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<script type="text/javascript"src="custom/modules/Administration/SweetDBAdmin/js/datepickr.js"></script>
<script type="text/javascript" src="custom/modules/Administration/SweetDBAdmin/js/simpleAutoComplete.js"></script>
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/datepicker.css" />
<link rel="stylesheet" type="text/css" href="custom/modules/Administration/SweetDBAdmin/css/simpleAutoComplete.css" />
<input type='hidden' name='command' value='{$COMMAND}'>
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<input type='hidden' name='sql' value='{$SQL}'>
<input type='hidden' name='table' value=''>
<table>
	<tr>
		<td align='left' valign='top'>
			<button id='save1Submit' name='save1Submit' onclick="submitPage();">{$mod.LBL_SWEETDBADMIN_SUBMIT}</button>
			<table id='detailpanel_1'>
				<tr bgcolor="#d3d3d3"><td colspan="3" align="center">
					{if $smarty.request.command == 'insert'}
						<h2><span style="color: black">{$mod.LBL_SWEETDBADMIN_INSERT} {$TABLE}</span></h2>
					{else}
						<h2><center>{$mod.LBL_SWEETDBADMIN_EDIT} {$TABLE}</center></h2>
					{/if}
				</td></tr>
				{foreach from=$EDITFIELDS key=myId item=i}
				<tr>
					<td scope='row'>
						{$i.name}
					</td>
					<td scope='row'>
						({$i.type})
					</td>
					<td scope='row'>
						{$i.fieldCode}
						<input type='hidden' name='{$i.name}_originalValue' value='{$i.value}'>
					</td>
				</tr>
				{/foreach}
			</table>
			<button id='save2Submit' name='save2Submit' onclick="submitPage();">{$mod.LBL_SWEETDBADMIN_SUBMIT}</button>
		</td>
	</tr>
</table>
</form>