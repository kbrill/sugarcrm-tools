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
class SweetDB_CRUD extends SweetDB
{
    /**
     *
     */
    function editWindow()
    {
        $this->sugar_smarty->assign('OPTIONS', "");
        $table = $this->getTable();
        $id = trim($this->getRequestVar('id',''));
        $sql = trim($this->getRequestVar('sql',''));
        $command = trim($this->getRequestVar('command',''));

        if (substr($table, - 5) != '_cstm') {
            $main = 'id';
        }
        else {
            $main = 'id_c';
        }
        if (empty($id) && $command != 'insert') {
            $this->sugar_smarty->assign('OPTIONS', "ERROR: NO ID");
        }
        else {
            $beanSearch = $this->getBean($table);
            $bean=$beanSearch['vardefs'];
            $this->sugar_smarty->assign('COMMAND', 'save');
            $this->sugar_smarty->assign('ID', $id);
            $this->sugar_smarty->assign('SQL', $sql);
            $this->sugar_smarty->assign('MAIN', $main);
            $this->sugar_smarty->assign('TABLE', $table);
            $editFields = "";

            $this->assembleList();
            $tables = $this->tableArray;
            $cols = $tables[$table];

            $colNames = array();
            foreach ($cols as $colName=> $colData) {
                $name = strtolower($colName);
                $type = strtolower($colData['type']);
                $colNames[$name]['name'] = $name;
                $colNames[$name]['type'] = $type;
                if (isset($colData['len'])) {
                    $colNames[$name]['length'] = $colData['len'];
                }
                if ($command == 'insert') {
                    $recordHash[$name] = '';
                }
            }
            //put columns back on top that need to be up top
            $colNames=$this->columnShift($colNames,array('name','last_name','first_name','id_c','id','email_id'));

            //for an insert we dont need to query the DB
            if ($command != 'insert') {
                $recordSQL = "SELECT * FROM {$table} WHERE {$main}='{$id}'";
                $result = $GLOBALS['db']->query($recordSQL, TRUE);
                $recordHash = $GLOBALS['db']->fetchByAssoc($result);
            }

            foreach ($colNames as $key=> $rows) {
                if (isset($rows['length'])) {
                    $length = $rows['length'];
                }
                else {
                    $length = 0;
                }
                $editFields[$key]=array('name'=>$rows['name'],'type'=>$rows['type'],'value'=>$recordHash[$rows['name']],'fieldCode'=>$this->inputField($rows['name'], $rows['type'], $bean, $recordHash[$rows['name']], $length));
            }
            $this->sugar_smarty->assign("EDITFIELDS", $editFields);
        }

        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBEdit.tpl");
    }

    /**
     *
     */
    function delete_record() {
        $deleteSQL = $this->deleteRecordSQL();
        $SweetDB_query = new SweetDB_query();
        $SweetDB_query->setupTextArea($deleteSQL);
        $queryArray = $SweetDB_query->runQuery($deleteSQL, -1);
        $sql=$this->getRequestVar('sql','');
        $numOfRecords=$this->getRequestVar('numrecords',50);
        $this->sugar_smarty->assign("SQL", $sql);
        $queryArray = $SweetDB_query->runQuery($sql, $numOfRecords);
        $this->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        $this->sugar_smarty->assign("QUERY_TIME", $queryArray['query_time']);
        if (! empty($queryArray['data'])) {
            $this->sugar_smarty->assign("ISDATA", "1");
            $this->sugar_smarty->assign("HEADER_ARRAY", $queryArray['header']);
            $this->sugar_smarty->assign("DATA_ARRAY", $queryArray['data']);
            $this->sugar_smarty->assign("MAIN", $queryArray['main']);
        }
        else {
            $this->sugar_smarty->assign("ISDATA", "0");
        }
        $this->sugar_smarty->assign('EDIT_COMMAND','edit');
        $this->sugar_smarty->assign('DELETE_COMMAND','delete');
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }

    /**
     * @return string
     */
    function deleteRecordSQL() {
        $id = $this->getRequestVar('id','');
        $table = $this->getTable();
        $main = $this->getRequestVar('main','id');
        $deleteSQL = "DELETE FROM {$table} WHERE {$main}='{$id}'";
        return $deleteSQL;
    }

    /**
     *
     */
    function save_record() {
        $sql = $this->buildSave();
        $SweetDB_query = new SweetDB_query();
        $numOfRecords=$this->getRequestVar('numrecords',50);
        $queryArray = $SweetDB_query->runQuery($sql, -1);
        $table = $this->getTable();
        if(isset($_REQUEST['id_input'])) {
            $id=$_REQUEST['id_input'];
            $id_field='id';
        }
        if(isset($_REQUEST['id_c_input'])) {
            $id=$_REQUEST['id_c_input'];
            $id_field='id_c';
        }
        $newQuery=$this->getRequestVar('sql','');
        if(empty($newQuery)) {
            $newQuery = "SELECT * FROM {$table} WHERE {$id_field}='{$id}'";
        }
        $queryArray2 = $SweetDB_query->runQuery($newQuery, $numOfRecords);
        $queryArray2['affectedRows']=$queryArray['affectedRows'];
        $SweetDB_query->setupTextArea($sql);
        $this->sugar_smarty->assign("ACTIONS", $queryArray2['actions']);
        $this->sugar_smarty->assign("QUERY_TIME", $queryArray2['query_time']);
        $SweetDB_query->drawTableData($sql,$queryArray2);
        $this->sugar_smarty->assign('EDIT_COMMAND','edit');
        $this->sugar_smarty->assign('DELETE_COMMAND','delete');
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }

    /**
     * @return string
     */
    function buildSave() {
        $table = $this->getTable();
        $columns = array();
        $whereClause = "";
        foreach ($_REQUEST as $fieldName=> $value) {
            if (substr($fieldName, - 6) == '_input') {
                $field = substr($fieldName, 0, - 6);
                if ($field == 'id') {
                    $whereClause = "id='{$_REQUEST['id']}'";
                }
                if ($field == 'id_c') {
                    $whereClause = "id_c='{$_REQUEST['id']}'";
                }
                if (! empty($value) || $value == '0') {
                    if($value != $_REQUEST[$field.'_originalValue']) {
                        if(is_array($value)) {
                            //Multi Select Field
                            $menumValue = trim(encodeMultienumValue($value),"^");
                            if($_REQUEST[$field.'_originalValue'] != $menumValue) {
                                $columns[] = $this->addToColumns($field, $value);
                            }
                        } else {
                            $columns[] = $this->addToColumns($field, $value);
                        }
                    }
                }
            }
        }

        if (empty($columns)) {
            //queryBox('');
            exit;
        }
        else {
            $columnNames = implode(',', $columns);
        }

        //I think I can check $_REQUEST['id'], if its there then I can make this work without the query
        //but I want to make it able to ADD NEW or UPDATE so maybe not
        $sql = "SELECT * FROM {$table} WHERE {$whereClause}";
        $result=$GLOBALS['db']->query($sql);
        $hash=$GLOBALS['db']->fetchByAssoc($result);
        if(!$hash) {
            $nameArray=array();
            $valueArray=array();
            foreach($columns as $inserts) {
                list($name,$value)=explode("=",$inserts,2);
                $nameArray[]=$name;
                $valueArray[]=$value;
            }
            $names=implode(",",$nameArray);
            $values=implode(",",$valueArray);
            $sql = "INSERT INTO {$table} ({$names}) VALUES({$values})";
        } else {
            $sql = "UPDATE {$table} SET {$columnNames} WHERE {$whereClause}";
        }

        return $sql;
    }

    /**
     * @param $field
     * @param $value
     * @return string
     */
    function addToColumns($field, $value) {
        if (! is_array($value) || (is_array($value) && count($value) == 1)) {
            if (is_array($value)) {
                $finalValue = implode("", $value);
            }
            else {
                $finalValue = $value;
            }
            $result = "{$field}='{$finalValue}'";
        }
        else {
            $multiEnumValue = encodeMultienumValue($value);
            $result = "{$field}='{$multiEnumValue}'";
        }
        return $result;
    }

    /**
     * @param $colArray
     * @param array $columns
     * @return mixed
     */
    function columnShift($colArray,$columns=array())
    {
        foreach($columns as $columnName) {
            if (isset($colArray[$columnName])) {
                $temp = $colArray[$columnName];
                unset($colArray[$columnName]);
                array_unshift($colArray, $temp);
            }
        }
        return $colArray;
    }
}
