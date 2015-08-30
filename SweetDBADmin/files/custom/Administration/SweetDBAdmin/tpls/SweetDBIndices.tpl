{include file="custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl"}
<table class='list view'>
<tr>
{if $ACTIONS}
	<th colspan="2">&nbsp;</th>
{/if}
{foreach key=key item=item from=$HEADER_ARRAY}
    <th scope='col'>{$item}</th>
{/foreach}
</tr>
{foreach item=item from=$DATA_ARRAY}
	<tr class='{cycle values="evenListRowS1,oddListRowS1"}'>
        {foreach key=key2 item=item2 from=$item name=data}
            {if $smarty.foreach.data.first}
                {if $ACTIONS}
	                {if isset($item.id)}
		                {assign var=itemid value=$item.id}
	                {/if}
	                {if isset($item.id_c)}
		                {assign var=itemid value=$item.id_c}
	                {/if}
	                <td>
                        <a title='{$mod.LBL_SWEETDBADMIN_EDIT}' id='edit{$itemid|strip_tags|trim}' href='#' onclick="document.sql.currentTable.value='{$TABLE}';document.sql.main.value='{$MAIN}';document.sql.id.value='{$itemid|strip_tags|trim}';document.sql.command.value='edit';document.sql.submit();">
                            <img src='custom/modules/Administration/SweetDBAdmin/images/edit_inline.png?v={$itemid|strip_tags|trim}' alt='{$mod.LBL_SWEETDBADMIN_EDIT}' border='0'></a>
                    </td>
                    <td>
                        <a title='{$mod.LBL_SWEETDBADMIN_DELETE}' id='delete{$itemid|strip_tags|trim}' onclick="document.sql.currentTable.value='{$TABLE}';document.sql.main.value='{$MAIN}';document.sql.id.value='{$itemid|strip_tags|trim}';document.sql.command.value='delete';document.sql.submit();" href='#'>
                            <img src='custom/modules/Administration/SweetDBAdmin/images/delete_inline.png?v={$itemid|strip_tags|trim}' alt='{$mod.LBL_SWEETDBADMIN_DELETE}' border='0'></a>
                    </td>
                {/if}
            {/if}
            <td>{$item2}</td>
        {/foreach}
    </tr>
{/foreach}
</table>
