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
 * @category   Function
 * @package    SweetDBAdmin
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2011-2013 Kenneth Brill
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    1.9
 * @link       http://www.sugarforge.org/reviews/?group_id=1300
 */
require_once('custom/modules/Administration/SweetDBAdmin/sql-formatter/lib/SqlFormatter.php');
function readLog($logFile="") {
    if($logFile=="") $logFile='sugarcrm.log';
    $logArray=file($logFile);
    $counter=0;
    $throwAway=0;
    foreach($logArray as $line) {
        $firstChar=ord($line[0]);
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

        //We need to eliminate all lines that begin with a space (32) or a tab(9)
        if(!empty($line)) {
            //get the PID
            list($pid,$trash) = explode('[',$line);

            if($firstChar!=32 && $firstChar!=9 && stristr($line,"[info] query:")!==false) {
                $cLines[$counter]['sql'] = substr($line,stripos($line,"[info] query:")+13);
                $cLines[$counter]['pid'] = $pid;
                $throwAway=0;
                $counter++;
            } elseif($firstChar!=32 && $firstChar!=9 && stristr($line,"[info] query:")===false) {
                $throwAway=1;
            } else {
                if($throwAway==0) {
                    $parentLine=$counter-1;
                    $cLines[$parentLine]['sql'] .= " ".$line;
                }
            }
        }
    }
    $uniqueSQL = array();
    $sqlList=array();
    $sqlLog=array();
    foreach($cLines as $id=>$data) {
        if(!in_array($data['sql'],$uniqueSQL)) {
            $uniqueSQL[]=$data['sql'];
            $sqlList[] = SqlFormatter::format($data['sql']);
            $sqlLog[]=$data;
        } else {
            unset($data[$id]);
        }
    }

    write_array_to_file('sqlLog', $sqlLog, 'cache/SweetDB_sqlLog.php');

    return $sqlList;
}
?>
