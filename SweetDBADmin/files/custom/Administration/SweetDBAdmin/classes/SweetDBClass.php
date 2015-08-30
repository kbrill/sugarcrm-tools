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
class SweetDB
{
    public $tableArray=array();
    public $IDArray=array('id','id_c','email_id');
    public $table_name;
    public $sugar_smarty;
    public $finalParser;

    function __construct($onlyTables=FALSE) {
        if(!isset($GLOBALS['SweetDB_sugar_smarty'])) {
            $this->sugar_smarty = new Sugar_Smarty();
            $GLOBALS['SweetDB_sugar_smarty'] = $this->sugar_smarty;
        } else {
            $this->sugar_smarty = $GLOBALS['SweetDB_sugar_smarty'];
        }
    }

    /**
     * @param $table
     * @return mixed
     */
    function get_indicies($table) {
        $indexes = $GLOBALS['db']->get_indices($table);
        return $indexes;
    }
    /**
     * assembleList
     * Function fill in $tableArray with all fields from all tables.
     *
     * @param bool $onlyTables (if this is set to TRUE then the columns data is left out)
     */
    function assembleList($onlyTables=FALSE) {
        $this->tableArray=array();
        //todo: we need this to be deleted if studio is used or maybe if studio or modulebuilder/loader is visited
        if(file_exists('cache/SweetDB_tableArray.php')) {
            $tableArray=array();
            require('cache/SweetDB_tableArray.php');
            if(empty($tableArray)) {
                unlink('cache/SweetDB_tableArray.php');
            } else {
                $this->tableArray=$tableArray;
            }
        } else {
            $tables = $GLOBALS['db']->getTablesArray();
            ksort($tables);
            foreach ($tables as $tableName) {
                if($onlyTables) {
                    $this->tableArray[]=$tableName;
                } else {
                    $cols = $GLOBALS['db']->get_columns($tableName);
                    ksort($cols);
                    //move ID back up to the top
                    if(isset($cols['id'])) {
                        $tempID=$cols['id'];
                        unset($cols['id']);
                        $cols=array("id"=>$tempID) + $cols;
                    }
                    if(isset($cols['id_c'])) {
                        $tempID=$cols['id_c'];
                        unset($cols['id_c']);
                        $cols=array("id_c"=>$tempID) + $cols;
                    }

                    foreach ($cols as $colName=> $colData) {
                        //if the search table list is empty OR if its not empty AND the current table is on the list
                        if((!empty($this->searchTables) &&  in_array($tableName,$this->searchTables)) ||
                            empty($this->searchTables)) {
                            if((!empty($this->searchColumns) &&  in_array($colName,$this->searchColumns)) ||
                                empty($this->searchColumns)) {
                                    $this->tableArray[$tableName][$colName]=$colData;
                            }
                        }
                    }
                }
            }
            write_array_to_file('tableArray', $this->tableArray, 'cache/SweetDB_tableArray.php');
            if(!file_exists('cache/SweetDB_tableArray.php')) {
                sugar_die("Unable to write ('cache/SweetDB_tableArray.php') to the cache directory!");
            }
        }
    }

    function getTableColumnNames($table) {
        if(empty($this->tableArray)) {
            $this->assembleList(FALSE);
        }
        $tables = $this->tableArray;
        $retVar=array_keys($tables[$table]);
        return $retVar;
    }

    /**
     * valueCheck - In the end this function will check a value against a fieldtype and make sure it matches
     *              for example if the field is a TIMESTAMP then the value has to be 2012-01-01 00:00:00.00000
     * @param $value
     * @param $columnData
     *
     * @return bool
     */
    function valueCheck($value,$columnData) {
        $includeNumeric = FALSE;
        if(is_numeric(substr($value,0,4)) && substr($value,5,1)=='-' &&
            (stripos(strtoupper($columnData['type']),'DATE')!==FALSE ||
                stripos(strtoupper($columnData['type']),'TIME')!==FALSE)) {
            $includeNumeric=TRUE;
        } else {
            $includeNumeric=is_numeric(trim($value," %"));
        }
        return $includeNumeric;
    }

    function getBean($table) {
        global $dictionary;
        $retVar=array('vardefs'=>array(),'moduleName'=>'');
        if (strtolower(substr($table, - 5)) == '_cstm') {
            $table = substr($table, 0, - 5);
        }
        if(!isset($GLOBALS['vardefs_loaded'])) {
            if (empty($beanList))
                include("include/modules.php");
            //Reload ALL the module vardefs....
            foreach($beanList as $moduleName => $beanName)
            {
                VardefManager::loadVardef($moduleName, BeanFactory::getObjectName($moduleName));
            }
            $GLOBALS['vardefs_loaded']=TRUE;
        }

        foreach ($dictionary as $module => $data) {
            if (isset($data['table']) && $data['table'] == strtolower($table)) {
                $retVar['vardefs'] = $data;
                $retVar['moduleName'] = $module;
                break;
            }
        }
        if(!isset($retVar['vardefs']['relationships'])) {
            //we dont have any relationships yet, will need to do
            // a deep dive into the vardefs
            foreach ($dictionary as $module => $data) {
                if(isset($data['relationships'])) {
                    foreach ($data['relationships'] as $rel_name => $rel_data) {
                        if($rel_name==$table) {
                            $retVar['vardefs']['relationships'][$rel_name]=$rel_data;
                        }
                        if(isset($rel_data['join_table']) && $rel_data['join_table']==$table) {
                            $retVar['vardefs']['relationships'][$rel_name]=$rel_data;
                        }
                    }
                }
            }
        }
        return $retVar;
    }

    function getRequestVar($name,$default="") {
        $retVal=$default;
        if($name=='sql') {
            if (isset($_REQUEST['sql'])) {
                $sql=$_REQUEST['sql'];
                if (!empty($sql)) {
                    $retVal = preg_replace("/\s\s+/", " ", $sql);
                }
            } else {
                $retVal=$default;
            }
        } else {
            if (isset($_REQUEST[$name])) {
                $retVal = $_REQUEST[$name];
            } else {
                $retVal=$default;
            }
        }
        return $retVal;
    }

    function getTable() {
        $table = "accounts";
        if (isset($_REQUEST['currentTable']) && ! empty($_REQUEST['currentTable'])) {
            $table = $_REQUEST['currentTable'];
        }
        elseif (isset($_REQUEST['table']) && ! empty($_REQUEST['table'])) {
            $table = $_REQUEST['table'];
        }
        return $table;
    }

    function getFullTableOptions() {
        if(empty($this->tableArray)) {
            $this->assembleList(FALSE);
        }
        $table = $this->getTable();
        $tables = $this->tableArray;
        $tableNameArray=array_combine(array_keys($tables),array_keys($tables));
        $tableNameOptions=get_select_options_with_id($tableNameArray,$table);
        return $tableNameOptions;
    }

    /**
     *
     */
    function getAlphaSortedTables() {
        $maxPerMenu=16;
        $hardMaxPerMenu=22;
        if(empty($this->tableArray)) {
            $this->assembleList(FALSE);
        }
        $tables = $this->tableArray;
        $prevLetter="";
        $counter=0;
        $subCounter=0;
        $labelArray=array();
        $tableArray=array();
        foreach($tables as $tableName => $tableData) {
            $firstLetter=substr($tableName,0,1);
            if($firstLetter != $prevLetter) {
                if(!isset($labelArray[$counter])) {
                    $labelArray[$counter]=$firstLetter.'-';
                } else {
                    if($subCounter > $maxPerMenu) {
                        $labelArray[$counter].=$prevLetter;
                        $counter++;
                        $labelArray[$counter]=$firstLetter.'-';
                        $subCounter=0;
                    }
                }
                $prevLetter = $firstLetter;
            }
            $subCounter++;
            $tableArray[$counter][]=$tableName;
            if($subCounter>$hardMaxPerMenu) {
                $subCounter=0;
                $labelArray[$counter].=substr($tableName,0,2);
                $counter++;
                $labelArray[$counter]=substr($tableName,0,2).'-';
            }
        }
        $this->sugar_smarty->assign('DB_MENU_LABELS',$labelArray);
        $this->sugar_smarty->assign('DB_MENU_OPTIONS',$tableArray);
    }

    function deleteCaches() {
        if(file_exists('cache/SweetDB_sqlLog.php')) {
            unlink('cache/SweetDB_sqlLog.php');
        }
        if(file_exists('cache/SweetDB_tableArray.php')) {
            unlink('cache/SweetDB_tableArray.php');
        }
    }

    function inputField($fieldName, $fieldType, $bean, $value = '', $length = 255)
    {
        global $app_list_strings;
        global $mod_strings;
        if (isset($bean['fields'][$fieldName]['type'])) {
            $fieldType = $bean['fields'][$fieldName]['type'];
        }
        //if we are setting up a search page then we can treat all enums the same
        //if we are not setting up a search page then only group multienums together (standard, AC, ACinline ect...)
        if ((stristr($fieldType, 'enum') !== FALSE && $_REQUEST['command'] == 'search') ||
            (stristr($fieldType, 'multienum') !== FALSE && $_REQUEST['command'] != 'search')
        ) {
            $fieldType = 'multienum';
        }
        if($fieldType=='relate' && $fieldName=='assigned_user_id') $fieldType='assigned_user_name';
        if($length==0) {
            $length=255;
        }
        switch ($fieldType) {
            case 'text':
                return "&nbsp;<textarea cols='65' id='{$fieldName}_id' name='{$fieldName}_input'>{$value}</textarea>";
                break;
            case 'datetime':
                return "&nbsp;<input value='{$value}' name='{$fieldName}_input' id='{$fieldName}' class='date-pick' />\n
                            <script type='text/javascript'>\n
                                new datepickr('{$fieldName}', { dateFormat: 'Y-m-d 00:00:01' });\n
                            </script>\n";
                break;
            case 'modified_user_id':
            case 'assigned_user_name':
                $userArray=get_user_array(TRUE,'Active','',TRUE);
                $optionList = get_select_options_with_id($userArray, $value);
                $multiple="/";
                if($_REQUEST['command']=='insert' || $_REQUEST['command']=='edit') {
                    $multiple="/";
                    $arrayTag='';
                } else {
                    $multiple='MULTIPLE /';
                    $arrayTag='[]';
                }
                return "&nbsp;<select name='{$fieldName}_input{$arrayTag}' style='width: 420px' {$multiple}>
                           {$optionList}
                          </select>";
                break;
            case 'multienum':
                if(isset($bean['fields'][$fieldName]['options']) && isset($app_list_strings[$bean['fields'][$fieldName]['options']])) {
                    $list = $app_list_strings[$bean['fields'][$fieldName]['options']];
                }
                $list[''] = '';
                $optionList = get_select_options_with_id($list, $value);
                return "&nbsp;<select name='{$fieldName}_input[]' style='width: 420px' MULTIPLE>
                           {$optionList}
                          </select>";
                break;
            case 'enum':
            case 'enumAC':
                if(isset($bean['fields'][$fieldName]['options']) && isset($app_list_strings[$bean['fields'][$fieldName]['options']])) {
                    $list = $app_list_strings[$bean['fields'][$fieldName]['options']];
                }
                $list[''] = '';
                $optionList = get_select_options_with_id($list, $value);
                return "&nbsp;<select name='{$fieldName}_input' style='width: 420px'>
                           {$optionList}
                          </select>";
                break;
            case 'smallint':
            case 'bool':
                $c = "/";
                $nc = "/";
                if ($value == 1) {
                    $c = "SELECTED /";
                }
                if ($value == 0 && is_numeric($value)) {
                    $nc = "SELECTED /";
                }
                if ($_REQUEST['command'] == 'search') {
                    $addBlank = "<option value=''></option>";
                }
                else {
                    $addBlank = "";
                }
                if ($fieldName == "deleted") {
                    return "&nbsp;<select name='{$fieldName}_input' style='width: 420px'>
                            {$addBlank}
                            <option value='0' {$nc}>{$mod_strings['LBL_SWEETDBADMIN_NOTDELETED']}</option>
                            <option value='1' {$c}>{$mod_strings['LBL_SWEETDBADMIN_DELETED']}</option>
                          </select>";
                }
                else {
                    return "&nbsp;<select name='{$fieldName}_input' style='width: 420px'>
                            {$addBlank}
                            <option value='0' {$nc}>{$mod_strings['LBL_SWEETDBADMIN_NOTCHECKED']}</option>
                            <option value='1' {$c}>{$mod_strings['LBL_SWEETDBADMIN_CHECKED']}</option>
                          </select>";
                }
                break;
            default:
                //if this is an INSERT screen and an ID field then prefill it with an ID
                if ($fieldName == 'id' && $_REQUEST['command'] == 'insert') {
                    $value = create_guid();
                    $returnValue = "&nbsp;<input size=40 type='text' id='{$fieldName}_id' name='{$fieldName}_input' value='{$value}' maxlength='36'>&nbsp;({$mod_strings['LBL_SWEETDBADMIN_UNIQUE_ID']})";
                }
                else {
                    $returnValue = "&nbsp;<input size=50 type='text' id='{$fieldName}_id' name='{$fieldName}_input' value='{$value}' maxlength='{$length}'>";
                }
                $table=$this->getTable();
                $processingURL="index.php?module=Administration&action=SweetDBAdmin&skip=0&sql=&command=getTypeaheadData&field={$fieldName}&table={$table}&to_pdf=1";
                $returnValue .= "\n<script type='text/javascript'>$(document).ready(function(){ $('#{$fieldName}_id').simpleAutoComplete('{$processingURL}'); });</script>\n";
                return $returnValue;
                break;
        }
    }

    function buildLink($fieldName, $fieldValue, $sql, $counter)
    {
        require_once('include/php-sql-parser.php');
        $parser = new PHPSQLParser($sql);
        $finalParser = $this->finalSQLParse($parser->parsed, $fieldName, $counter);
        $returnValue = $fieldValue;
        if (! empty($finalParser['FIELDNAME'])) {
            $fieldName = strtolower($finalParser['FIELDNAME']);
            $tableName = $finalParser['TABLE'];
        } elseif(! empty($finalParser['FROM']) && count($finalParser['FROM'])==1) {
            $tableName=$finalParser['FROM'][0]['table'];
        }

        if (empty($tableName)) {
            $tableName = $this->getTable();
        }

        if (strtolower(substr($tableName, - 5)) == '_cstm') {
            $tableName = substr($tableName, 0, - 5);
        }

        $beanSearch=$this->getBean($tableName);
        $vardefs=$beanSearch['vardefs'];
        $moduleName=$beanSearch['moduleName'];
        if (! empty($vardefs)) {
            if (strtolower($fieldName) == 'id_c') {
                $fieldName = 'id';
            }
            if (isset($vardefs['fields'][$fieldName])) {
                if (isset($vardefs['fields'][$fieldName]['type'])) {
                    switch ($vardefs['fields'][$fieldName]['type']) {
                        case 'longtext':
                        case 'text':
                            $theArray=@unserialize(base64_decode($fieldValue));
                            if($theArray==false) {
                                $returnValue = $fieldValue;
                            } else {
                                //todo: would like to add some JS to this to add an OPEN/CLOSE function
                                // so that the full array is not shown all the time
                                $returnValue = "<font color=red><u>TRANSLATED</u></font><pre>".var_export($theArray,true)."</pre>";
                            }
                            break;
                        case 'datetime':
                            if($this->getRequestVar("user_dates","")=="1") {
                                $returnValue="<font color=green>" . $GLOBALS['timedate']->to_display_date_time($fieldValue) . "</font>";
                            } else {
                                $returnValue=$fieldValue;
                            }
                            break;
                        case 'assigned_user_name':
                        case 'relate':
                            if (isset($vardefs['fields'][$fieldName]['module'])) {
                                $moduleName = $vardefs['fields'][$fieldName]['module'];
                            }
                            if ($vardefs['fields'][$fieldName]['type'] == 'assigned_user_name' ||
                                $vardefs['fields'][$fieldName]['name'] == 'assigned_user_id') {
                                $moduleName = "Users";
                                if($this->getRequestVar("user_ids")=="1") {
                                    $id=$fieldValue;
                                    if(isset($_SESSION['users'][$fieldValue])) {
                                        $fieldValue=$_SESSION['users'][$fieldValue];
                                    } else {
                                        if(!isset($_SESSION['users'])) {
                                            $_SESSION['users'] = array();
                                        }
                                        $focus=new User();
                                        $focus->retrieve($fieldValue);
                                        $_SESSION['users'][$fieldValue]=$focus->name;
                                        $fieldValue=$focus->name;
                                    }
                                } else {
                                    $id=$fieldValue;
                                }
                            }
                            $returnValue = "<a href='index.php?module={$moduleName}&action=DetailView&record={$id}'>{$fieldValue}</a>";
                            break;
                        case 'id':
                            switch ($fieldName) {
                                case 'parent_id':
                                    //ok there are 2 types of parent_ids, one needs a parent_type and one does not.
                                    if (! isset($vardefs['fields']['parent_type'])) {
                                        //OK this is a field like 'member of' in accounts.
                                        $parent_name = $vardefs['fields']['parent_name'];
                                        $moduleName = $parent_name['module'];
                                        $returnValue = "<a href='index.php?module={$moduleName}&action=DetailView&record={$fieldValue}'>{$fieldValue}</a>";
                                    }
                                    break;
                                case 'id':
                                    $newBeanList = array_flip($GLOBALS['beanList']);
                                    if(isset($newBeanList[$moduleName])) {
                                        $returnValue = "<a href='index.php?module={$newBeanList[$moduleName]}&action=DetailView&record={$fieldValue}'>{$fieldValue}</a>";
                                    } else {
                                        $returnValue = $fieldValue;
                                    }
                                    break;
                            }
                            break;
                        case 'varchar':
                            //still might be a relationship
                            $relationships = array();
                            if (isset($vardefs['relationships'])) {
                                $relationships = $vardefs['relationships'];
                            }
                            foreach ($relationships as $name=> $data) {
                                if ($fieldName == $data['rhs_key']) {
                                    //bingo
                                    $moduleName = $data['lhs_module'];
                                    $returnValue = "<a href='index.php?module={$moduleName}&action=DetailView&record={$fieldValue}'>{$fieldValue}</a>";
                                }
                                if ($fieldName == $data['lhs_key']) {
                                    //bingo
                                    $moduleName = $data['rhs_module'];
                                    $returnValue = "<a href='index.php?module={$moduleName}&action=DetailView&record={$fieldValue}'>{$fieldValue}</a>";
                                }
                            }
                            break;
                    }
                }
            }
        }
        return $returnValue;
    }

    function getKeyName($cols,$table) {
        $keys=array_keys($cols);
        if(in_array('email_address',$keys)==TRUE) {
            return $table.".email_address {$table}_email_address";
        } elseif(in_array('user_name',$keys)==TRUE) {
            return $table.".user_name {$table}_user_name";
        } elseif(in_array('first_name',$keys)==TRUE) {
            return $GLOBALS['db']->concat($table,array('first_name','last_name')) . "{$table}_name";
        } elseif(in_array('name',$keys)==TRUE) {
            return $table.".name {$table}_name";
        } else {
            return $table.".id {$table}_id";
        }
    }

    function finalSQLParse($parsedList, $fieldName, $counter)
    {
        if(isset($parsedList['UPDATE'])) {
            $parsedList['TABLE'] = $parsedList['UPDATE'][0]['table'];
            return $parsedList;
        }
        if(!isset($parsedList['SELECT'])) {
            return $parsedList;
        }
        if (! isset($parsedList['SELECT'][$counter])) {
            $counter = 0;
        }
        $list = $parsedList['SELECT'][$counter];
        if (stripos($list['base_expr'], '.') !== FALSE) {
            //the table name/alias is part of the field name
            $tableName = substr($list['base_expr'], 0, stripos($list['base_expr'], '.'));
            $processedFieldName = trim(substr($list['base_expr'],
                                              - ((strlen($list['base_expr']) - 1) - strrpos($list['base_expr'], '.'))));
            foreach ($parsedList['FROM'] as $fromKey=> $fromList) {
                if ($fromList['alias'] == $tableName || $fromList['table'] == $tableName) {
                    $tableName = strtolower($fromList['table']);
                    break;
                }
            }
        }
        else {
            $processedFieldName = trim($list['base_expr']);
            //there is no indication of which table this field comes from
            if (count($parsedList['FROM']) == 1) {
                $tableName = strtolower($parsedList['FROM'][0]['table']);
            }
        }
        if(empty($tableName)) $tableName=$this->getTable();
        if ($fieldName == $processedFieldName || $fieldName == substr($list['alias'], 1, strlen($list['alias']) - 2)) {
            $parsedList['TABLE'] = $tableName;
            $parsedList['FIELDNAME'] = $processedFieldName;
        }
        elseif (empty($fieldName)) {
            if(isset($parsedList['TABLE'])) {
                $parsedList['TABLE'] = $tableName;
            }
        }
        return $parsedList;
    }

    function getColumnName($fieldName, $sql)
    {
        require_once('include/php-sql-parser.php');
        $parser = new PHPSQLParser($sql);
        $finalParser = $this->finalSQLParse($parser->parsed, $fieldName, 0);

        if (! empty($finalParser['FIELDNAME'])) {
            $fieldName = strtolower($finalParser['FIELDNAME']);
            $tableName = $finalParser['TABLE'];
        } elseif(! empty($finalParser['FROM']) && count($finalParser['FROM'])==1) {
            $tableName=$finalParser['FROM'][0]['table'];
        }

        if (empty($tableName)) {
            $tableName = $this->getTable();
        }

        if (strtolower(substr($tableName, - 5)) == '_cstm') {
            $tableName = substr($tableName, 0, - 5);
        }

        $beanSearch=$this->getBean($tableName);
        $vardefs=$beanSearch['vardefs'];
        $revBeanList=array_flip($GLOBALS['beanList']);
        $moduleName=$revBeanList[$beanSearch['moduleName']];
        if (! empty($vardefs)) {
            if (strtolower($fieldName) == 'id_c') {
                $fieldName = 'id';
            }
            if (isset($vardefs['fields'][$fieldName])) {
                $retVal = translate($vardefs['fields'][$fieldName]['vname'],$moduleName);
            } else {
                $retVal = $fieldName;
            }
        }
        return $retVal;
    }
}
