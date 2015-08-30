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
class SweetDB_search extends SweetDB
{
    public $searchTables;
    public $searchColumns;
    public $searchOptions=3;
    public $searchPattern;

    function preformSearch() {
        $sql = $this->buildQuery();
        $SweetDB_query = new SweetDB_query();
        $SweetDB_query->setupTextArea($sql);
        $queryArray = $SweetDB_query->runQuery($sql, 50);
        $this->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        $this->sugar_smarty->assign("QUERY_TIME", $queryArray['query_time']);
        $SweetDB_query->drawTableData($sql,$queryArray);
        $this->sugar_smarty->assign('EDIT_COMMAND','edit');
        $this->sugar_smarty->assign('DELETE_COMMAND','delete');
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }

    function buildQuery() {
        $table = $this->getTable();
        $where = array();
        $sort = array();
        $joins = array();
        $joinNames=array();
        foreach ($_REQUEST as $fieldName => $value) {
            if (substr($fieldName, - 6) == '_input') {
                $field = substr($fieldName, 0, - 6);
                $action = $_REQUEST[$field . '_action'];
                if(!empty($_REQUEST[$field . '_sort'])) {
                    $sort[$field] = $_REQUEST[$field . '_sort'];
                }
                if (is_array($value) && count($value) == 1 && empty($value[0])) {
                    $value = "";
                }
                if (! empty($value) || $value == '0') {
                    $where[] = $this->addToWhere($field, $value, $action, $table);
                }
            } elseif(substr($fieldName, - 5) == '_sort') {
                $field = substr($fieldName, 0, - 5);
                if(!empty($_REQUEST[$field . '_sort'])) {
                    $sort[$field] = $_REQUEST[$field . '_sort'];
                }
            }
        }

        if (isset($_REQUEST['columns'])) {
            $columns = $_REQUEST['columns'];
        }
        if (empty($columns)) {
            $columnNames = $table.".*";
        }
        else {
            //we need to force ID to always be in the query
            if (substr($table, - 5) == '_cstm') {
                if (in_array('id_c', $columns) == FALSE) {
                    $columns[] = 'id_c';
                }
            }
            else {
                if (in_array('id', $columns) == FALSE) {
                    $columns[] = 'id';
                }
            }

            //move id or id_c to the beginning of the list
            //put columns back on top that need to be up top
            $columns=$this->columnShift($columns,array('name','last_name','first_name','id_c','id','email_id'));
            $columnNames = $table.'.'.implode(','.$table.'.', $columns);
        }
        $sortStatement="";
        if(!empty($sort)) {
            $sortStatement = " ORDER BY ";
            $sortFields=array();
            foreach($sort as $field=>$direction) {
                $sortFields[] = $table.'.'.$field . ' ' . strtoupper($direction);
            }
            $sortStatement .= implode(",",$sortFields);
        }

        if (isset($_REQUEST['joins']) && !empty($_REQUEST['joins'])) {
            $joinList=$_REQUEST['joins'];
            foreach($joinList as $joinLines) {
                list($joinNames[],$joins[])=explode("~",$joinLines);
            }
            $joinClause = implode(" ",$joins) ." ";
            $columnNames .= ','.implode(",",$joinNames);
        } else {
            $joinClause = "";
        }
        if (! empty($where)) {
            $whereClause = implode(" AND", $where);
            $sql = "SELECT {$columnNames} FROM {$table} {$joinClause}WHERE {$whereClause}{$sortStatement}";
        }
        else {
            $sql = "SELECT {$columnNames} FROM {$table} {$joinClause} {$sortStatement}";
        }
        return $sql;
    }

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

    function searchAllMenu() {
        $this->assembleList();
        $tables = $this->tableArray;
        $columns=array();
        foreach ($tables as $tableNames=>$columnNames) {
            foreach($columnNames as $colName=>$colData) {
                $columns[$colName]=$colName;
            }
        }
        ksort($columns);
        $tableNameOptions=$this->getFullTableOptions();
        $this->sugar_smarty->assign('OPTIONS', $tableNameOptions);
        $this->sugar_smarty->assign('COLUMNS', $columns);
        $this->sugar_smarty->display('custom/modules/Administration/SweetDBAdmin/tpls/SweetDBSearchAll.tpl');
    }

    function doSearchAll($searchPattern) {
        //run query
        $this->sugar_smarty->display('custom/modules/Administration/SweetDBAdmin/tpls/SweetDBheader.tpl');
        $this->searchTables=$this->getRequestVar('tableselect',"");
        $this->searchOptions=$this->getRequestVar('search_option',3);
        $this->searchColumns=$this->getRequestVar('columnNames',"");
        $this->assembleList();
        $results=$this->preformSearchAll($searchPattern);
        echo "<table border=1 class='list view' width=400>";
        echo "<tr><th>Table Name</th><th>Matches</th><th>Actions</th></tr>";
        foreach ($results as $line) {
            echo $line;
        }
        echo "</table>";
    }

    function preformSearchAll($searchPattern='') {
        $resultArray=array();
        if(!empty($searchPattern)) {
            $this->searchPattern=$searchPattern;
        } else {
            return NULL;
        }
        $this->assembleList();
        $sqlArray=array();
        $sqlLog=array();
        $sqlIndex=0;
        foreach($this->tableArray as $tableName=>$columnNames) {
            if(in_array($tableName, $this->searchTables)) {
                if(isset($_REQUEST['columnNames']) && !empty($_REQUEST['columnNames'])) {
                    $requestedColumnNames=array();
                    foreach($_REQUEST['columnNames'] as $colName) {
                        if(isset($columnNames[$colName])) {
                            $requestedColumnNames[$colName]=$columnNames[$colName];
                        }
                    }
                } else {
                    $requestedColumnNames=$columnNames;
                }

                $sql=$this->buildSQL($tableName,$requestedColumnNames);
                if(!empty($sql)) {
                    $sqlArray[$sqlIndex]=$sql['run'];
                    $sqlLog[]=$sql['show'];
                    $result=$GLOBALS['db']->query($sqlArray[$sqlIndex],FALSE);
                    if($result==FALSE) {
                        echo $GLOBALS['db']->lastError();
                    } else {
                        $hash=$GLOBALS['db']->fetchByAssoc($result);
                        $rowCount=$hash['rc'];
                    }
                } else {
                    $rowCount=0;
                }

                //todo: need to convert this to a TPL

                if($rowCount>0) {
                    $resultArray[]="<tr><td>{$tableName}</td><td>{$rowCount} matches</td><td>&nbsp;&nbsp;<a href='index.php?module=Administration&action=SweetDBAdmin&command=runLogQuery&query={$sqlIndex}'>SHOW</a></td></tr>";
                } else {
                    $resultArray[]="<tr><td>{$tableName}</td><td>{$rowCount} matches</td><td>&nbsp;</td></tr>";
                }
                $sqlIndex++;
            }
        }
        write_array_to_file('sqlLog', $sqlLog, 'cache/SweetDB_sqlLog.php');
        return $resultArray;
    }

    function buildSQL($tableName,$columnNames) {
        $whereClause=array();
        $numericTypes=array('INTEGER', 'INT', 'SMALLINT', 'TINYINT', 'MEDIUMINT', 'BIGINT',
                            'DECIMAL', 'NUMERIC', 'FLOAT', 'DOUBLE', 'BIT', 'DATE', 'DATETIME', 'TIMESTAMP');
        foreach($columnNames as $columnName=>$columnData) {
            switch($this->searchOptions) {
                case 1:
                    $wordList=explode(" ",$this->searchPattern);
                    $temp=array();
                    foreach($wordList as $words) {
                        $includeNumeric=valueCheck($words,$columnData);
                        if($includeNumeric==TRUE || !in_array(strtoupper($columnData['type']),$numericTypes)) {
                            $temp[]=$columnName." LIKE '%".$words."%'";
                        }
                    }
                    if(!empty($temp)) {
                        $wordList=implode(" OR ",$temp);
                        $whereClause[]='('.$wordList.')';
                    }
                    break;
                case 2:
                    $wordList=explode(" ",$this->searchPattern);
                    $temp=array();
                    foreach($wordList as $words) {
                        $includeNumeric=valueCheck($words,$columnData);
                        if($includeNumeric==TRUE || !in_array(strtoupper($columnData['type']),$numericTypes)) {
                            $temp[]=$columnName." LIKE '%".$words."%'";
                        }
                    }
                    if(!empty($temp)) {
                        $wordList=implode(" AND ",$temp);
                        $whereClause[]='('.$wordList.')';
                    }
                    break;
                case 3:
                    if(stristr($this->searchPattern,"%")!==FALSE) {
                        $operator="LIKE";
                    } else {
                        $operator="=";
                    }
                    $includeNumeric=$this->valueCheck($this->searchPattern,$columnData);
                    if($includeNumeric==TRUE || !in_array(strtoupper($columnData['type']),$numericTypes)) {
                        $whereClause[]=$columnName." {$operator} '".$this->searchPattern."'";
                    }
                    break;
            }

        }
        $where=implode(" OR ",$whereClause);
        $sql=array();
        if(!empty($where)) {
            $sql['run']="SELECT count(*) rc FROM {$tableName} WHERE {$where}";
            $sql['show']="SELECT * FROM {$tableName} WHERE {$where}";
        }
        return $sql;
    }

    function searchWindow()
    {
        $table = $this->getTable();
        $this->sugar_smarty->assign('COMMAND', 'buildsearch');
        $beanSearch = $this->getBean($table);
        $bean=$beanSearch['vardefs'];
        $module=$beanSearch['moduleName'];

        $tableNameOptions=$this->getFullTableOptions();
        $this->sugar_smarty->assign('OPTIONS', $tableNameOptions);

        $tables = $this->tableArray;
        $cols = $tables[$table];
        ksort($cols);

        $revBeanList=array_flip($GLOBALS['beanList']);
        if(!empty($revBeanList[$module])) {
            $mod_strings = return_module_language($GLOBALS['current_language'], $revBeanList[$module]);
        }

        //put id back on top
        if (isset($cols['id'])) {
            $temp = $cols['id'];
            unset($cols['id']);
            $cols=array_merge(array('id'=>$temp),$cols);
        }
        elseif (isset($cols['id_c'])) {
            $temp = $cols['id_c'];
            unset($cols['id_c']);
            $cols=array_merge(array('id_c'=>$temp),$cols);
        }

        $colNames = array();
        $tableColumnOptions = "";
        foreach ($cols as $colName=> $colData) {
            $name = strtolower($colName);
            $type = strtolower($colData['type']);
            $colNames[$name]['name'] = $name;
            $colNames[$name]['type'] = $type;
            $vName="";
            if(isset($bean['fields'][$name]['vname']) &&
                isset($mod_strings[$bean['fields'][$name]['vname']])) {
                $vName=trim($mod_strings[$bean['fields'][$name]['vname']],":");
            }
            $tableColumnOptions .= "<option title='{$vName}' value='{$name}'>{$name}</option>";
        }

        $this->sugar_smarty->assign('COLUMNS', $tableColumnOptions);

        $relationshipOptions="";
        if(!isset($bean['relationships'])) $bean['relationships']=array();
        foreach($bean['relationships'] as $rel_name=>$rel_data) {
            if(isset($rel_data['join_table'])) {
                //complex relationship
                if($table==$rel_data['join_table']) {
                    //this is a join table so we need two joins
                    //join #1
                    $relatedTable=$rel_data['rhs_table'];
                    $relatedKey=$rel_data['rhs_key'];
                    $joinTable=$rel_data['join_table'];
                    $key1=$rel_data['join_key_rhs'];
                    $uniqueID=substr(create_guid(),-3);

                    $tables = $this->tableArray;
                    $cols = $tables[$relatedTable];
                    $key_name1=$this->getKeyName($cols,$relatedTable."_".$uniqueID);
                    $join1="LEFT JOIN {$relatedTable} {$relatedTable}_{$uniqueID} ON {$relatedTable}_{$uniqueID}.{$relatedKey}={$joinTable}.{$key1} AND {$relatedTable}_{$uniqueID}.deleted=0 AND {$joinTable}.deleted=0";
                    //join #2
                    $relatedTable=$rel_data['lhs_table'];
                    $relatedKey=$rel_data['lhs_key'];
                    $joinTable=$rel_data['join_table'];
                    $key1=$rel_data['join_key_lhs'];
                    $uniqueID=substr(create_guid(),-3);
                    $cols = $tables[$relatedTable];
                    $key_name2=$this->getKeyName($cols,$relatedTable."_".$uniqueID);
                    $join2="LEFT JOIN {$relatedTable} {$relatedTable}_{$uniqueID} ON {$relatedTable}_{$uniqueID}.{$relatedKey}={$joinTable}.{$key1} AND {$relatedTable}_{$uniqueID}.deleted=0 AND {$joinTable}.deleted=0";

                    $join="{$key_name1},{$key_name2}~{$join1} {$join2}";
                } else {
                    if($rel_data['lhs_table']==$table) {
                        $relatedTable=$rel_data['rhs_table'];
                        $relatedKey=$rel_data['rhs_key'];
                        $moduleKey=$rel_data['lhs_key'];
                    }
                    if($rel_data['rhs_table']==$table) {
                        $relatedTable=$rel_data['lhs_table'];
                        $relatedKey=$rel_data['lhs_key'];
                        $moduleKey=$rel_data['rhs_key'];
                    }
                    if(!empty($relatedTable)) {
                        $joinTable=$rel_data['join_table'];
                        $key1=$rel_data['join_key_lhs'];
                        $key2=$rel_data['join_key_rhs'];
                        $uniqueID=substr(create_guid(),-3);
                        $tables = $this->tableArray;
                        $cols = $tables[$relatedTable];
                        $key_name=$this->getKeyName($cols,$relatedTable."_".$uniqueID);
                        if(isset($rel_data['relationship_role_column'])){
                            $rrc=$rel_data['relationship_role_column'];
                            $rrcv=$rel_data['relationship_role_column_value'];
                            if(isset($rel_data['join_table']) && !empty($rel_data['join_table'])) {
                                $trrc = "jt_{$uniqueID}";
                            } else {
                                $trrc = "{$relatedTable}_{$uniqueID}";
                            }
                            $join="{$key_name}~LEFT JOIN {$joinTable} jt_{$uniqueID} ON {$table}.{$moduleKey}=jt_{$uniqueID}.{$key1} AND jt_{$uniqueID}.deleted=0 LEFT JOIN {$relatedTable} {$relatedTable}_{$uniqueID} on jt_{$uniqueID}.{$key2}={$relatedTable}_{$uniqueID}.id AND {$relatedTable}_{$uniqueID}.deleted=0 AND {$trrc}.{$rrc}='{$rrcv}'";
                        } else {
                            $join="{$key_name}~LEFT JOIN {$joinTable} jt_{$uniqueID} ON {$table}.{$moduleKey}=jt_{$uniqueID}.{$key1} AND jt_{$uniqueID}.deleted=0 LEFT JOIN {$relatedTable} {$relatedTable}_{$uniqueID} on jt_{$uniqueID}.{$key2}={$relatedTable}_{$uniqueID}.id AND {$relatedTable}_{$uniqueID}.deleted=0";
                        }
                    }
                }
            } else {
                //simple relationship
                if($rel_data['lhs_table']==$table) {
                    $relatedTable=$rel_data['rhs_table'];
                    $relatedKey=$rel_data['rhs_key'];
                    $moduleKey=$rel_data['lhs_key'];
                }
                if($rel_data['rhs_table']==$table) {
                    $relatedTable=$rel_data['lhs_table'];
                    $relatedKey=$rel_data['lhs_key'];
                    $moduleKey=$rel_data['rhs_key'];
                }
                if(!empty($relatedTable)) {
                    $uniqueID=substr(create_guid(),-3);
                    $tables = $this->tableArray;
                    $cols = $tables[$relatedTable];
                    $key_name=$this->getKeyName($cols,$rel_name."_".$uniqueID);
                    $join="{$key_name}~LEFT JOIN {$relatedTable} {$rel_name}_{$uniqueID} ON {$table}.{$moduleKey}={$rel_name}_{$uniqueID}.{$relatedKey} AND {$rel_name}_{$uniqueID}.deleted=0";
                }
            }
            if(!empty($join)) $relationshipOptions .= "<option value=\"{$join}\">{$rel_name}</option>";
        }

        $join="";
        foreach($tables as $tName => $tData) {
            if($tName == $table.'_cstm') {
                $custom_table=$table.'_cstm';
                $key_name = $custom_table.'.'.implode(", {$custom_table}.",array_keys($tables[$custom_table]));
                $join="{$key_name}~LEFT JOIN {$custom_table} ON {$table}.id={$custom_table}.id_c";
                $rel_name = $custom_table;
                break;
            }
        }
        if(!empty($join)) $relationshipOptions = "<option value=\"{$join}\">{$rel_name}</option>".$relationshipOptions;

        $this->sugar_smarty->assign('JOINS', $relationshipOptions);

        $searchFields = "";
        foreach ($colNames as $key=> $rows) {
            $searchFields .= "<tr><td valign=top>{$rows['name']}</td><td valign=top>({$rows['type']})</td><td valign=top>";
            $searchFields .= $this->actionDropDown($rows['name'], $rows['type'], $bean) . "</td><td>";
            $searchFields .= $this->inputField($rows['name'], $rows['type'], $bean, '', '255') . "</td><td>";
            $searchFields .= $this->sortField($rows['name']) . "</td></tr>";
        }
        $this->sugar_smarty->assign("SEARCHFIELDS", $searchFields);
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBSearch.tpl");
    }

    function actionDropDown($fieldName, $fieldType, $bean)
    {
        global $mod_strings;
        if (isset($bean['fields'][$fieldName]['type'])) {
            $fieldType = $bean['fields'][$fieldName]['type'];
        }
        if(isset($bean['fields'][$fieldName]['table'])) $fieldType='relate';
        switch ($fieldType) {
            case 'bool':
            case 'smallint':
                return "<select name='{$fieldName}_action' style='width: 150px'>
                        <option value='='>=</option>
                    </select>";
                break;
            case 'modified_user_id':
            case 'assigned_user_name':
            case 'assigned_user_id':
            case 'relate':
                if(isset($bean['fields'][$fieldName]['table'])) $relatedTable=$bean['fields'][$fieldName]['table'];
                if(isset($bean['fields'][$fieldName]['key_name'])) $relatedKey=$bean['fields'][$fieldName]['key_name'];
                $retVal = "<select name='{$fieldName}_action' style='width: 150px'>
                                <option value='IN (...)'>IN (...)</option>
                                <option value='NOT IN (...)'>NOT IN (...)</option>
                                <option value='IS NULL'>IS NULL</option>
                                <option value='IS NOT NULL'>IS NOT NULL</option>
                        </select>";
                return $retVal;
                break;
            case 'enum':
                return "<select name='{$fieldName}_action' style='width: 150px'>
                                <option value='IN (...)'>IN (...)</option>
                                <option value='NOT IN (...)'>NOT IN (...)</option>
                                <option value='IS NULL'>IS NULL</option>
                                <option value='IS NOT NULL'>IS NOT NULL</option>
                        </select>";
                break;
            case 'datetimecombo':
            case 'timestamp':
            case 'datetime':
                return "<select name='{$fieldName}_action' style='width: 150px'>
                                <option value='='>=</option>
                                <option value='!='>!=</option>
                                <option value='<'><</option>
                                <option value='<='><=</option>
                                <option value='>'>></option>
                                <option value='>='>>=</option>
                                <option value='= \'\''>= ''</option>
                                <option value='!= \'\''>!= ''</option>
                                <option value='IS NULL'>IS NULL</option>
                                <option value='IS NOT NULL'>IS NOT NULL</option>
                                <option value='date_hour'>Within the last hour</option>
                                <option value='date_today'>Today</option>
                                <option value='date_yestday'>Yesterday</option>
                        </select>";
                break;
            default:
                return "<select name='{$fieldName}_action' style='width: 150px'>
                                <option value='LIKE %...%'>LIKE %...%</option>
                                <option value='LIKE %...'>LIKE %...</option>
                                <option value='LIKE ...%'>LIKE ...%</option>
                                <option value='NOT LIKE'>NOT LIKE</option>
                                <option value='='>=</option>
                                <option value='!='>!=</option>
                                <option value='= \'\''>= ''</option>
                                <option value='!= \'\''>!= ''</option>
                                <option value='IN (...)'>IN (...)</option>
                                <option value='NOT IN (...)'>NOT IN (...)</option>
                                <option value='IS NULL'>IS NULL</option>
                                <option value='IS NOT NULL'>IS NOT NULL</option>
                        </select>";
                break;
        }
    }

    function sortField($fieldName)
    {
        return "&nbsp;<select name='{$fieldName}_sort' style='width: 60px'>
                <option value=''></option>
                <option value='asc'>ASC</option>
                <option value='desc'>DESC</option>
              </select>";
    }

    function addToWhere($field, $value, $action, $table)
    {
        //TODO: could pare this down as some of the $actions are also the symbols used
        if(!empty($_REQUEST['joins'])) {
            $whereField = "{$table}.{$field}";
        } else {
            $whereField = $field;
        }
        $result = "";
        switch ($action) {
            case 'LIKE %...%':
                $result = " {$whereField} LIKE '%{$value}%'";
                break;
            case 'LIKE %...':
                $result = " {$whereField} LIKE '%{$value}'";
                break;
            case 'LIKE ...%':
                $result = " {$whereField} LIKE '{$value}%'";
                break;
            case 'NOT LIKE':
                $result = " {$whereField} NOT LIKE '{$value}'";
                break;
            case '=':
                $fieldType = "";
                if ($value == '0') {
                    //check to see if it is a checkbox, then we need to add both 0 and NULL
                    $beanSearch = $this->getBean($table);
                    $bean=$beanSearch['vardefs'];
                    if (isset($bean['fields'][$field]['type'])) {
                        $fieldType = $bean['fields'][$field]['type'];
                    }
                }
                if ($fieldType == 'bool') {
                    $result = " ({$whereField} = '{$value}' OR {$whereField} IS NULL)";
                }
                else {
                    $result = " {$whereField} = '{$value}'";
                }
                break;
            case '!=':
                $result = " {$whereField} != '{$value}'";
                break;
            case '>':
                $result = " {$whereField} > '{$value}'";
                break;
            case '<':
                $result = " {$whereField} < '{$value}'";
                break;
            case '>=':
                $result = " {$whereField} >= '{$value}'";
                break;
            case '<=':
                $result = " {$whereField} <= '{$value}'";
                break;
            case "= ''":
                $result = " {$whereField} = ''";
                break;
            case "!= ''":
                $result = " {$whereField} != ''";
                break;
            case 'IN (...)':
                $arrayList = implode("','", $value);
                $result = " {$whereField} IN ('{$arrayList}')";
                break;
            case 'NOT IN (...)':
                $arrayList = implode("','", $value);
                $result = " {$whereField} NOT IN ('{$arrayList}')";
                break;
            case 'IS NULL':
                $result = " {$whereField} IS NULL";
                break;
            case 'IS NOT NULL':
                $result = " {$whereField} IS NOT NULL";
                break;
            case 'date_hour':
                $lastHour  = date("Y-m-d H:i:s",gmmktime(date("H")-1, date("i"), date("s"), date("m"), date("d"), date("Y")));
                $result = " {$whereField} >= '{$lastHour}'";
                break;
            case 'date_today':
                $today  = date("Y-m-d H:i:s",gmmktime(0, 0, 1, date("m"), date("d"), date("Y")));
                $result = " {$whereField} >= '{$today}'";
                break;
            case 'date_yesterday':
                $today  = date("Y-m-d",gmmktime(0, 0, 1, date("m"), date("d")-1, date("Y")));
                $result = " {$whereField} = '{$today}'";
                break;
        }
        return $result;
    }
}
