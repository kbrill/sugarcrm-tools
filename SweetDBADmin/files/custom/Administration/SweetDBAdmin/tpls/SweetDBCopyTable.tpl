{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<input type='hidden' name='command' value='{$COMMAND}'>
<input type='hidden' name='id' value='{$ID}'>
<input type='hidden' name='main' value='{$MAIN}'>
<input type='hidden' name='table' value=''>
<table align=center cellpadding="5" cellspacing="5">
	<tr>
		<td align='right'>
			{$mod.LBL_TABLE_TO_COPY}:
		</td>
		<td>
			<select name=tableselect>
			{$OPTIONS}
			</select>
		</td>
	</tr>
	<tr>
		<td align='right'>
			{$mod.LBL_SWEETDBADMIN_CTN}:
		</td>
		<td>
			<input type="text" name="newTableName">
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<hr>
		</td>
	</tr>
	<tr>
		<td align='right' valign="top">
			{$mod.LBL_SWEETDBADMIN_WTC}:
		</td>
		<td>
			<input type="radio" name="copythis" id="copys" value="structure"> {$mod.LBL_SWEETDBADMIN_SO}<br />
			<input type="radio" name="copythis" id="copya" value="all" checked="checked"> {$mod.LBL_SWEETDBADMIN_SD}<br />
			<input type="radio" name="copythis" id="copyd1" value="data1"> {$mod.LBL_SWEETDBADMIN_DO1}<br />
			<input type="radio" name="copythis" id="copyd2" value="data2"> {$mod.LBL_SWEETDBADMIN_DO2}<br />
		</td>
	</tr>
	<tr>
		<td align='right'>
			{$mod.LBL_SWEETDBADMIN_NDR}:
		</td>
		<td>
			<input type="checkbox" name='noDeletedRecords'>
		</td>
	</tr>
	<tr>
		<td align='right'>
		{$mod.LBL_SWEETDBADMIN_RID}:
		</td>
		<td>
			<input type="checkbox" name='ReplaceIDs'>
		</td>
	</tr>
	<tr>
		<td align='right'>
		{$mod.LBL_SWEETDBADMIN_SNT}:
		</td>
		<td>
			<input type="checkbox" name='switchToNewTable'>
		</td>
	</tr>
	<tr>
		<td align='right'>
			{$mod.LBL_SWEETDBADMIN_LCR}:
		</td>
		<td>
			<input type="text" name='copyLimit' maxlength="6" size="6">&nbsp;{$mod.LBL_SWEETDBADMIN_RECORDS}
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
			<input type="submit" value='{$mod.LBL_SWEETDBADMIN_COPY_TABLE}'>
		</td>
	</tr>
</table>
</form>