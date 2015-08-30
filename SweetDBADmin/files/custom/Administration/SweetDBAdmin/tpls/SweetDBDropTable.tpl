{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<input type='hidden' name='command' value='{$COMMAND}'>
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<input type='hidden' name='table' value=''>
<table align=center cellpadding="5" cellspacing="5">
	<tr>
		<td align='right' valign="top">
			{$mod.LBL_TABLE_TO_DROP}:
		</td>
		<td>
			<select name="tableselect[]" multiple="1" size="20">
			{$OPTIONS}
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type="submit" value="{$mod.LBL_SWEETDBADMIN_DROP_TABLE}" onClick="return confirmDelete()">
		</td>
	</tr>
</table>
</form>
<script LANGUAGE="JavaScript">
function confirmDelete()
{ldelim}
	var agree=confirm("{$mod.LBL_CONFIRM_DELETE}");
	if (agree)
		return true ;
	else
		return false ;
{rdelim}
</script>