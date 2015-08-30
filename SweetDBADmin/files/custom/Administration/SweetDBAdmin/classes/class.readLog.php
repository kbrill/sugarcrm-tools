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
require_once('custom/modules/Administration/SweetDBAdmin/sql-formatter/lib/SqlFormatter.php');
class SweetDB_readLog extends SweetDB
{
    function logWindow()
    {
        //todo: base this all on the $sugar_config for log file name
         set_time_limit(300);
        $logFile=$this->getRequestVar('logFile','sugarcrm.log');
        $this->sugar_smarty->assign('COMMAND', 'readLog');
        $tableNameOptions = "";
        foreach (glob("sugar*.log") as $filename) {
            if ($logFile == $filename) {
                $selected = "SELECTED /";
            }
            else {
                $selected = '/';
            }
            $tableNameOptions .= "<option value='{$filename}' {$selected}>{$filename}</option>";
        }

        $this->sugar_smarty->assign('OPTIONS', $tableNameOptions);

        $sqlLog = $this->readLog($logFile);
        $this->sugar_smarty->assign('SQLLOG', $sqlLog);
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBLog.tpl");
    }

    function readLog($logFile="") {
        if($logFile=="") $logFile='sugarcrm.log';
        $logArray=file($logFile);
        $counter=0;

        foreach($logArray as $line) {
            $throwAway=false;
            //Figure out if this line starts with a date
            $firstFour = substr($line,0,4);
            //This might need to be updated if your dates in the log file are not in english
            $dayArray = array('Sun ','Mon ','Tue ','Wed ','Thu ','Fri ','Sat ');
            if(in_array($firstFour,$dayArray)) {
                $dateLine = true;
            } else {
                $dateLine = false;
            }

            $line=trim($line);

            //remove comments
            if(substr($line,0,2)!='--') {
                $parts=explode("--",$line,2);
                if(isset($parts[0])) {
                    $line=$parts[0];
                } else {
                    $line='';
                }
            } else {
                $line='';
            }

            if(!empty($line)) {
                //get the PID
                $logData = explode('[',$line);

                if($dateLine && stristr($line,"[info] query:")!==false) {
                    $cLines[$counter]['sql'] = substr($line,stripos($line,"[info] query:")+13);
                    $cLines[$counter]['logData'] = substr($logData[1],0,-1);
                    $cLines[$counter]['date'] = trim($logData[0]);
                    if($logData[2] != '-none-]') {
                        $cLines[$counter]['user'] = get_user_name(substr($logData[2],0,-1));
                    } else {
                        $cLines[$counter]['user'] = substr($logData[2],0,-1);
                    }
                    $throwAway=true;
                    $counter++;
                } elseif ($dateLine && stristr($line, '[INFO] Query Execution Time') !== false) {
                    $parentLine=$counter-1;
                    $query_time = explode(":",$logData[3]);
                    $cLines[$parentLine]['query_time'] = $query_time[1];
                    $throwAway=true;
                } elseif($dateLine) {
                    $throwAway=true;
                }
                //If $throwAway is false then this must be a continuation of a SQL statement from the line above
                if(!$throwAway) {
                    $parentLine=$counter-1;
                    $cLines[$parentLine]['sql'] .= " ".$line;
                }
            }
        }
        $uniqueSQL = array();
        $sqlList=array();
        $sqlLog=array();
        foreach($cLines as $id=>$data) {
            if(!in_array($data['sql'],$uniqueSQL)) {
                $uniqueSQL[]=$data['sql'];
                $sqlList[$id]=$data;
                $sqlList[$id]['sql'] = SqlFormatter::format($data['sql']);
                $sqlLog[$id]=$data;
            } else {
                unset($data[$id]);
            }
        }

        write_array_to_file('sqlLog', $sqlLog, 'cache/SweetDB_sqlLog.php');

        return $sqlList;
    }

    function runLogQuery() {
        $sqlLog=array();
        if(file_exists('cache/SweetDB_sqlLog.php')) {
            require_once('cache/SweetDB_sqlLog.php');
            $query=$this->getRequestVar('query','');
            if(!empty($query) || $query==0) {
                $_REQUEST['sql'] = $sqlLog[$query];
                $_REQUEST['numOfRecords'] = - 1;
            }
        } else {
            $_REQUEST['sql']="";
        }
    }
}
