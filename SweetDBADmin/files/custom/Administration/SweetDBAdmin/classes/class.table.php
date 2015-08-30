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
class SweetDB_Table extends SweetDB
{
    function dropTable() {
        $tableSelect=$this->getRequestVar('tableselect');
        if(!is_array($tableSelect))
        {
            $tempArray=$tableSelect;
            $tableSelect=array($tempArray);
        }
        if(empty($tableSelect)) return null;
        $_REQUEST['sql']="";
        if(file_exists('cache/SweetDB_tableArray.php')) {
            unlink('cache/SweetDB_tableArray.php');
        }
        foreach($tableSelect as $tableName)
        {
            $sql=$GLOBALS['db']->dropTableNameSQL($tableName);
            $_REQUEST['sql'] .= $sql."; ";
            $SweetDB_query=new SweetDB_query();
            $queryArray = $SweetDB_query->runQuery($sql,1,true);
        }
        $SweetDB_query->setupTextArea($_REQUEST['sql']);
        $SweetDB_query->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        $this->sugar_smarty->assign("QUERY_TIME", $queryArray['query_time']);
        $SweetDB_query->drawTableData($_REQUEST['sql'],$queryArray);
        $SweetDB_query->sugar_smarty->assign('EDIT_COMMAND','edit');
        $SweetDB_query->sugar_smarty->assign('DELETE_COMMAND','delete');
        $SweetDB_query->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }

    function truncateTable() {
        $tableSelect=$this->getRequestVar('tableselect');
        if(!is_array($tableSelect))
        {
            $tempArray=$tableSelect;
            $tableSelect=array($tempArray);
        }
        if(empty($tableSelect)) return null;
        $_REQUEST['sql']="";
        foreach($tableSelect as $tableName)
        {
            $sql=$GLOBALS['db']->truncateTableSQL($tableName);
            $_REQUEST['sql'] .= $sql."; ";
            $SweetDB_query=new SweetDB_query();
            $queryArray = $SweetDB_query->runQuery($sql,1,true);
        }
        $SweetDB_query->setupTextArea($_REQUEST['sql']);
        $SweetDB_query->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        $this->sugar_smarty->assign("QUERY_TIME", $queryArray['query_time']);
        $SweetDB_query->drawTableData($_REQUEST['sql'],$queryArray);
        $SweetDB_query->sugar_smarty->assign('EDIT_COMMAND','edit');
        $SweetDB_query->sugar_smarty->assign('DELETE_COMMAND','delete');
        $SweetDB_query->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }

    function describeTable() {
        global $sugar_config;
        $tableSelect=$this->getRequestVar('tableselect');
        if(empty($tableSelect)) return null;
        $ucaseDBUser = strtoupper($sugar_config['dbconfig']['db_user_name']);
        $ucaseTableName = strtoupper($tableSelect);

        $db_type = array('ibm_db2'      => "SELECT * FROM SYSCAT.COLUMNS WHERE TABSCHEMA = '{$ucaseDBUser}' AND TABNAME = '{$ucaseTableName}'",
                         'mysql'        => "DESCRIBE {$tableSelect}",
                         'SQL Server'   => "sp_columns_90 {$tableSelect}",
                         'oci8'         => "SELECT * FROM user_tab_columns WHERE TABLE_NAME = '".strtoupper($tableSelect)."'");

        $sql = $db_type[$sugar_config['dbconfig']['db_type']];

        $SweetDB_query = new SweetDB_query();
        $SweetDB_query->setupTextArea($sql);
        $queryArray = $SweetDB_query->runQuery($sql, 5000);
        //$this->sugar_smarty->assign("ACTIONS", $queryArray['actions']);
        $SweetDB_query->drawTableData($sql,$queryArray);
        $this->sugar_smarty->assign('EDIT_COMMAND','edit');
        $this->sugar_smarty->assign('DELETE_COMMAND','delete');
        $this->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBQuery.tpl");
    }

    function copyTable() {
        $tableselect=$this->getRequestVar('tableselect');
        $newTableName=$this->getRequestVar('newTableName');
        $copythis=$this->getRequestVar('copythis');
        $noDeletedRecords=$this->getRequestVar('noDeletedRecords');
        $ReplaceIDs=$this->getRequestVar('ReplaceIDs','0');
        $switchToNewTable=$this->getRequestVar('switchToNewTable','0');
        $copyLimit=$this->getRequestVar('copyLimit','0');

        //first copy the table if that is required
        if($copythis=='structure' || $copythis=='all') {
            $sql=$GLOBALS['db']->dropTableNameSQL($newTableName);
            $result=$GLOBALS['db']->query($sql,false);
            $sql="CREATE TABLE {$newTableName} LIKE {$tableselect}";
            $result=$GLOBALS['db']->query($sql,false);
        }

        //Now populate with data if that is required
        if($copythis=='data1' || $copythis=='data2' || $copythis=='all') {
            $additionalClauses=array();
            if($noDeletedRecords) {
                $additionalClauses[]="WHERE deleted=0";
            }
            if(is_numeric($copyLimit)) {
                $additionalClauses="LIMIT {$copyLimit}";
            }
            $sqlEnd=' '.implode(' ',$additionalClauses);
            $sql="INSERT INTO {$newTableName} SELECT * FROM {$tableselect}{$sqlEnd}";
            $result=$GLOBALS['db']->query($sql,false);
        }

        //now rewrite the IDs if required
        if($ReplaceIDs) {
            $this->assembleList();
            $tables = $this->tableArray;
            $cols = $tables[$tableselect];
            $id='';
            if(isset($cols['id'])) {
                $id='id';
            }
            if(isset($cols['id_c'])) {
                $id='id_c';
            }
            if(isset($cols['email_id'])) {
                $id='email_id';
            }
            $sql="SELECT {$id} FROM {$newTableName}";
            $result=$GLOBALS['db']->query($sql);
            while($hash=$GLOBALS['db']->fetchByAssoc($result)) {
                $newID=create_guid();
                $sql="UPDATE {$newTableName} SET {$id}='{$newID}' WHERE {$id}='{$hash[$id]}'";
                $update=$GLOBALS['db']->query($sql);
            }
        }
        if(file_exists('cache/SweetDB_tableArray.php')) {
            unlink('cache/SweetDB_tableArray.php');
        }
        $SweetDB_query=new SweetDB_query();
        if($switchToNewTable) {
            $_REQUEST['sql']="SELECT * FROM {$newTableName}";
        }
        $SweetDB_query->display_query();
    }
}
