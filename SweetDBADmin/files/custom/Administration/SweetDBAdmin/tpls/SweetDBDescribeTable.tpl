{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<input type='hidden' name='command' value='{$COMMAND}'>
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<input type='hidden' name='table' value=''>
<table align=center cellpadding="5" cellspacing="5">
	<tr>
		<td align='right' valign="top">
			{$mod.LBL_TABLE_TO_DESCRIBE}:
		</td>
		<td>
			<select name="tableselect" size="20">
			{$OPTIONS}
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type="submit" value="{$mod.LBL_SWEETDBADMIN_DESC_TABLE}">
		</td>
	</tr>
</table>
</form>