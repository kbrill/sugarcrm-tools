<?php
/*********************************************************************************
 * SweetDBAdmin is a SQL management program developed by
 * Kenneth Brill (kbrill@sugarcrm.com)
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU Affero General Public License version 3 as published by the
 * Free Software Foundation with the addition of the following permission added
 * to Section 15 as permitted in Section 7(a): FOR ANY PART OF THE COVERED WORK
 * IN WHICH THE COPYRIGHT IS OWNED BY KENNETH BRILL, KENNETH BRILL DISCLAIMS THE WARRANTY
 * OF NON INFRINGEMENT OF THIRD PARTY RIGHTS.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU Affero General Public License for more
 * details.
 *
 * You should have received a copy of the GNU Affero General Public License along with
 * this program; if not, see http://www.gnu.org/licenses or write to the Free
 * Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
 * 02110-1301 USA.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 *
 * @category   Class
 * @package    SweetDBAdmin
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2011-2013 Kenneth Brill
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    1.9
 * @link       http://www.sugarforge.org/reviews/?group_id=1300
 */
class SweetDB_Indexes extends SweetDB
{
    function add_edit_indexes() {
        global $mod_strings;
        $table=$this->getTable();
        $this->sugar_smarty->assign("IS_EXISTING",false);
        if($this->getRequestVar('command','')=='edit_index') {
            $indexes=$this->get_indicies($table);
            $relevant_index=$indexes[$_REQUEST['id']];
            $this->sugar_smarty->assign("NAME",$relevant_index['name']);
            $this->sugar_smarty->assign("IS_EXISTING",true);
        } else {
            $relevant_index['type']="index";
            $relevant_index['fields'][]='id';
        }
        $type_list=get_select_options_with_id($mod_strings['INDEX_TYPES'],$relevant_index['type']);
        $this->sugar_smarty->assign("TYPE",$type_list);
        //todo: we need to check for primary and disable/remove the option if needed
        $this->sugar_smarty->assign("FIELDS_IN_INDEX",$relevant_index['fields']);
        $columnNames=$this->getTableColumnNames($table);
        array_unshift($columnNames,'');
        $columnNamesArray=array_combine($columnNames,$columnNames);
        $this->sugar_smarty->assign('FIELD_LIST',$columnNamesArray);
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBIndexEditor.tpl");
    }

    function save_index() {
        $definition=array();
        $definition['name']=$this->getRequestVar('index_name',"");
        $definition['type']=$this->getRequestVar('index_type',"");
        $fields=array();
        for($i=0;$i<15;$i++) {
            if($i==0) {
                $index='';
            } else {
                $index=$i;
            }
            $value=$this->getRequestVar("field".$index,"");
            if(!empty($value)) $definition['fields'][]=$value;
        }

        if(!empty($definition['fields'])) {
            $dropFirst=$this->getRequestVar('existingIndex',false);
            if($dropFirst) {
                //it needs to be removed and replaced
                $sql=$GLOBALS['db']->add_drop_constraint($this->getTable(), $definition, true);
                $result=$GLOBALS['db']->query($sql,true);
                $sql=$GLOBALS['db']->add_drop_constraint($this->getTable(), $definition, false);
            } else {
                $sql=$GLOBALS['db']->add_drop_constraint($this->getTable(), $definition, false);
            }
            $result=$GLOBALS['db']->query($sql,true);
        }
    }

    function delete_index() {
        $indexes=$this->get_indicies($this->getTable());
        $definition=array();
        $name=$this->getRequestVar('id',"");
        $relevant_index=$indexes[$name];
        $definition['name']=$name;
        $definition['type']=$relevant_index['type'];
        $definition['fields']=$relevant_index['fields'];
        $sql=$GLOBALS['db']->add_drop_constraint($this->getTable(), $definition, true);
        $result=$GLOBALS['db']->query($sql,false);
    }

    function display_indexes() {
        $indexes=$this->get_indicies($this->getTable());
        $i=0;
        $rows=array();
        foreach($indexes as $name=>$data) {
            $fields=implode(', ',$data['fields']);
            $rows[$i]=array('id'=>$data['name'],'type'=>$data['type'],'fields'=>$fields);
            $i++;
        }
        $queryArray['data']=$rows;
        $queryArray['actions']=true;
        $queryArray['header']=array('Name','Type','Fields');
        $queryArray['main']='';

        $queryArray['extra_controls']="Select table to query:";
        $tableNameOptions=$this->getFullTableOptions();
        $queryArray['extra_controls'].="<select name=tableselect onchange=\"chooseNewTable('indexes');\">";
        $queryArray['extra_controls'].=$tableNameOptions;
        $add_index_link="index.php?module=Administration&action=SweetDBAdmin&skip=0&sql=&command=add_index&currentTable={$this->getTable()}";
        $queryArray['extra_controls'].="</select>&nbsp;<input type=button name=add value='Add Index' onclick='document.location=\"{$add_index_link}\"'>";

        $this->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        $sql="HIDE";
        $SweetDB_query=new SweetDB_query();
        $SweetDB_query->setupTextArea($sql);
        $SweetDB_query->drawTableData($sql,$queryArray);
        $this->sugar_smarty->assign('EDIT_COMMAND','edit_index');
        $this->sugar_smarty->assign('DELETE_COMMAND','delete_index');
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }


}
