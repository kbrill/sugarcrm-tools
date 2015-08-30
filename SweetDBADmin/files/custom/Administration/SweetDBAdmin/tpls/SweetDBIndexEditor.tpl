{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<input type="hidden" name='command' value='save_index'>
<input type="hidden" name='existingIndex' value='{$IS_EXISTING}'>
<br />
<table class='list view'>
<tr>
	<td>
		Name:
	</td>
	<td>
		<input type='text' name='index_name' value='{$NAME}'>
	</td>
</tr>
<tr>
	<td>
		Type:
	</td>
	<td>
		<select name='index_type' id='index_type'>{$TYPE}</select>
	</td>
</tr>
<tr>
	<td valign="top">
		Fields:
	</td>
	<td>
		<div id="field_list">
			{foreach from=$FIELDS_IN_INDEX item=FIELD name=FIELD_LOOP}
				<br>
				<select name="field{if $smarty.foreach.FIELD_LOOP.index>0}{$smarty.foreach.FIELD_LOOP.index}{/if}" id="id_field{if $smarty.foreach.FIELD_LOOP.index>0}{$smarty.foreach.FIELD_LOOP.index}{/if}" class="field_class">
					{html_options options=$FIELD_LIST selected=$FIELD}
				</select>
			{/foreach}
		</div>
		<input type=button class="clone" value="Add">
		<input type=button class="remove" value="Remove">
	</td>
</tr>
</table>
<input type="submit" name="Submit" value='Save'>
{literal}
<script type="text/javascript">
$(function() {

$(".clone").live("click", function() {
	var cloneIndex = $(".field_class").length; // you can find the the current length of `.lang` within the handler
	if(cloneIndex>14) {
		alert('No more fields can be added.')
	} else {
		$(".field_class:last").clone() // clones the last element with class of `lang`
				.attr("id", "id_field" +  cloneIndex) // change this to id selector
				.attr("name", "field" +  cloneIndex)
				.appendTo("#field_list")
				.before('<br/>')
		document.getElementById("id_field" +  cloneIndex).selectedIndex=0;
	}
});

$(".remove").live('click', function() {
	var cloneIndex = $(".field_class").length;
	if(cloneIndex>1) {
		$(".field_class:last").remove();
		trimBrs(document.getElementById("field_list"));
	}
});

});
</script>
{/literal}