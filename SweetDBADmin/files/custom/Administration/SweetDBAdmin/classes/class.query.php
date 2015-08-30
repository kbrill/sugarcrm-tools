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
class SweetDB_query extends SweetDB
{
    function setupTextArea($sql) {
        if($sql=='HIDE') {
            $this->sugar_smarty->assign("HIGHLIGHTED_SQL", "");
            $this->sugar_smarty->assign("SHOWTEXTAREA", 'none');
            $this->sugar_smarty->assign("HIDEEDITBUTTON", true);
            $parser = new PHPSQLParser($sql);
            $finalParser = $this->finalSQLParse($parser->parsed, "", 0);
        } elseif (! empty($sql)) {
            require_once('custom/modules/Administration/SweetDBAdmin/sql-formatter/lib/SqlFormatter.php');
            $this->sugar_smarty->assign("HIGHLIGHTED_SQL", SqlFormatter::format($sql));
            $this->sugar_smarty->assign("SHOWTEXTAREA", 'none');
            $parser = new PHPSQLParser($sql);
            $finalParser = $this->finalSQLParse($parser->parsed, "", 0);
        }
        else {
            $this->sugar_smarty->assign("SHOWTEXTAREA", 'block');
        }
        if(isset($finalParser['TABLE']) || isset($finalParser['FROM'])) {
            if(isset($finalParser['TABLE'])) {
                $this->sugar_smarty->assign("TABLE", $finalParser['TABLE']);
            } else {
                if(count($finalParser['FROM'])==1) {
                    $this->sugar_smarty->assign("TABLE", $finalParser['FROM'][0]['table']);
                }
            }
        }
    }

    function drawTableData($sql,$queryArray) {
        if(empty($this->tableArray)) {
            $this->assembleList(FALSE);
        }
        $this->sugar_smarty->assign("SQL", $sql);
        if(isset($queryArray['extra_controls'])) {
            $this->sugar_smarty->assign("EXTRA_CONTROLS", $queryArray['extra_controls']);
        }

        if (! empty($queryArray['data'])) {
            $this->sugar_smarty->assign("ISDATA", "1");
            $this->sugar_smarty->assign("HEADER_ARRAY", $queryArray['header']);
            $this->sugar_smarty->assign("DATA_ARRAY", $queryArray['data']);
            $this->sugar_smarty->assign("MAIN", $queryArray['main']);
        }
        else {
            $this->sugar_smarty->assign("ISDATA", "0");
            if(isset($queryArray['error'])) {
                $this->sugar_smarty->assign("ERROR",$queryArray['error']);
            }
        }
        $table = $this->getTable();
        $tables = $this->tableArray;
        $tableNameArray = array_combine(array_keys($tables),array_keys($tables));
        $tableNameOptions=get_select_options_with_id($tableNameArray,$table);
        $this->sugar_smarty->assign('OPTIONS', $tableNameOptions);
        $beanSearch = $this->getBean($table);
        $bean=$beanSearch['vardefs'];
        $module=$beanSearch['moduleName'];
        $cols = $tables[$table];
        ksort($cols);
        $revBeanList=array_flip($GLOBALS['beanList']);
        if(!empty($revBeanList[$module])) {
            $mod_strings = return_module_language($GLOBALS['current_language'], $revBeanList[$module]);
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
    }

    function runQuery($sql, $numOfRecords = 50, $silent = FALSE)
    {
        set_time_limit(900);
        $sql = html_entity_decode(preg_replace("/\s\s+/", " ", $sql), ENT_QUOTES);
        $returnArray = array('data'=> array(),
                             'actions'=> TRUE,
                             'header'=> array(),
                             'main'=> '');

        if (! empty($sql)) {
            if (stristr($sql,'limit')!==FALSE
                || strtoupper(substr($sql,0,6))!="SELECT"
                || $numOfRecords < 1) {
                $result = $GLOBALS['db']->query($sql, FALSE);
            }
            else {
                $result = $GLOBALS['db']->limitQuery($sql, 0, $numOfRecords, FALSE);
            }
            $affectedRows=abs($GLOBALS['db']->getAffectedRowCount($result));

            $returnArray['affectedRows']=$affectedRows;
            $returnArray['query_time']=$GLOBALS['db']->query_time;
            //If there is an error then put that on the screen and skip the rest of this
            // function
            if($result==FALSE) {
                $returnArray['error']=$GLOBALS['db']->lastError();
                return $returnArray;
            } else {
                //Save this query to the History file
                $sqlHistory = array();
                if(file_exists('cache/SweetDB_sqlHistory.php')) {
                    include('cache/SweetDB_sqlHistory.php');
                }
                if(isset($sqlHistory) && !empty($sqlHistory)) {
                    $index = array_keys($sqlHistory, max($sqlHistory));
                } else {
                    $index=0;
                }
                $foundIndex=-1;
                foreach($sqlHistory as $index => $data) {
                    if($data['query']==trim($sql)) {
                        $foundIndex=$index;
                    }
                }
                if ($foundIndex==-1) {
                    $sqlHistory[$index+1]=array();
                    $sqlHistory[$index+1]['query'] = trim($sql);
                    $sqlHistory[$index+1]['count'] = 1;
                } else {
                    $sqlHistory[$foundIndex]['count'] = $sqlHistory[$index]['count']+1;
                }
                write_array_to_file('sqlHistory', $sqlHistory, 'cache/SweetDB_sqlHistory.php');
            }
            //if we can safely edit and delete records from this result set them set this
            // to TRUE
            $returnArray['actions'] = $this->showActionMenu($sql);

            //Silent means that we are going to run this query but we do not care
            // about its output so we just don't process it, if its not a SELECT then we
            // don't need the output either
            if (! $silent && (strtoupper(substr($sql,0,6)) != "INSERT" && strtoupper(substr($sql,0,6)) != "UPDATE")) {
                $header = FALSE;
                $headerArray = array();
                $dataArray = array();
                $count=0;
                while ($line = $GLOBALS['db']->fetchByAssoc($result)) {
                    if ($header == FALSE) {
                        foreach ($line as $index=> $col_value) {
                            if($this->getRequestVar('translated_column_names','')=='1') {
                                $index=str_replace(":","",$this->getColumnName($index,$sql));
                            }
                            $headerArray[] = $index;
                            if (empty($returnArray['main'])) {
                                $returnArray['main'] = $index;
                            }
                        }
                        $returnArray['header'] = $headerArray;
                        $header = TRUE;
                    }
                    $lineArray = array();
                    $counter = 0;
                    if(isset($_SESSION['users'])) {
                        unset($_SESSION['users']);
                    }
                    foreach ($line as $index => $col_value) {
                        $displayValue = $this->buildLink($index, $col_value, $sql, $counter);
                        $counter ++;
                        $lineArray[$index] = $displayValue;
                    }
                    $dataArray[] = $lineArray;
                }
                $returnArray['data'] = $dataArray;
            }
        }
        return $returnArray;
    }

    function showActionMenu($sql)
    {
        //if there is a JOIN, a UNION or No ID field then return false
        $showActionMenu = TRUE;
        $parser = new PHPSQLParser($sql);
        $foundID=FALSE;
        if(isset($parser->parsed['SELECT'])) {
            foreach($parser->parsed['SELECT'] as $field) {
                if(substr($field['base_expr'],-2)=='id' ||
                    $field['base_expr']=='*' ||
                    $field['alias']=="`id`") {
                    $foundID=TRUE;
                }
            }
            if(!$foundID) {
                $showActionMenu=FALSE;
            }
        }
        return $showActionMenu;
    }

    function display_query() {
        $numOfRecords=$this->getRequestVar('numrecords',50);

        $sql=$this->getRequestVar('sql','');
        $this->setupTextArea($sql);
        $queryArray = $this->runQuery($sql, $numOfRecords);
        $this->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        if(isset($queryArray['query_time'])) {
            $this->sugar_smarty->assign("QUERY_TIME", $queryArray['query_time']);
        }
        $this->drawTableData($sql,$queryArray);
        $this->sugar_smarty->assign('EDIT_COMMAND','edit');
        $this->sugar_smarty->assign('DELETE_COMMAND','delete');
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }
}