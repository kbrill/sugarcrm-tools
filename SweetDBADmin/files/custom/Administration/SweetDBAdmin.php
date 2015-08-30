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
 * @category   Main
 * @package    SweetDBAdmin
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2011-2013 Kenneth Brill
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    1.9
 * @link       http://www.sugarforge.org/reviews/?group_id=1300
 */
if (! defined('sugarEntry') || ! sugarEntry) {
    die('Not A Valid Entry Point');
}

//todo: add alerts when queries are updates, deletes or inserts from history
//todo: Add an import facility
//todo: Need to fix column names for joined columns to include the name of the column
//todo: build history class
//todo: add command, numrecords, startrecord and table to class

require_once('custom/modules/Administration/SweetDBAdmin/SweetDBConfig.php');
require_once('include/php-sql-parser.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/SweetDBClass.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/class.indexes.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/class.query.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/class.search.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/class.readLog.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/class.crud.php');
require_once('custom/modules/Administration/SweetDBAdmin/classes/class.table.php');

global $current_user;
global $mod_strings;
global $app_list_strings;
global $app_strings;
global $theme;

$title = getClassicModuleTitle(
    "Administration",
    array(
        "<a href='../../../modules/Administration/index.php?module=Administration&action=SweetDBAdmin'>{$mod_strings['LBL_MODULE_NAME']}</a>",
         translate('LBL_SWEETDBADMIN')
    ),
    FALSE
);

//set up classes
$SweetDB_query = new SweetDB_query();
$SweetDB = new SweetDB();

$command=$SweetDB->getRequestVar('command',"query");
$numOfRecords=$SweetDB->getRequestVar('numrecords',50);
$startRecord=$SweetDB->getRequestVar('startrecord',0);
$scriptName=$SweetDB->getRequestVar('action',"");

$SweetDB->sugar_smarty->assign("mod", $mod_strings);
$SweetDB->sugar_smarty->assign("app", $app_strings);
$SweetDB->sugar_smarty->assign("NUM_RECORDS", $numOfRecords);
$SweetDB->sugar_smarty->assign("START_RECORD", $startRecord);
$SweetDB->sugar_smarty->assign("SCRIPTNAME", $scriptName);
$SweetDB->sugar_smarty->assign("TABLE", $SweetDB->getTable());
$SweetDB->sugar_smarty->assign("TITLE", $title);
$SweetDB->sugar_smarty->assign("MODULE", getCurrentModule($SweetDB->getTable()));
if(file_exists('cache/SweetDB_sqlHistory.php')) {
    include_once('cache/SweetDB_sqlHistory.php');
}
if(isset($sqlHistory) && !empty($sqlHistory)) {
    krsort($sqlHistory);
    $SweetDB->sugar_smarty->assign("HISTORYITEMS", $sqlHistory);
    uasort($sqlHistory, 'sortByCount');
    $SweetDB->sugar_smarty->assign("TOPHISTORYITEMS", $sqlHistory);
}

if(!file_exists('include/javascript/jquery/jquery-ui-min.js')) {
    $SweetDB->sugar_smarty->assign('JQUERY','<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.5.2/jquery.min.js"></script><script src="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.14/jquery-ui-1.8.14.custom.min.js"></script>');
}

$SweetDB->getAlphaSortedTables();

switch ($command) {
    case 'about':
        $SweetDB->sugar_smarty->assign('VERSION',$SweetDBConfig['version']);
        $SweetDB->sugar_smarty->assign('RELEASE_DATE',$SweetDBConfig['release_date']);
        $SweetDB->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBAbout.tpl");
        break;
    case 'export_csv':
        require('custom/modules/Administration/SweetDBAdmin/SweetDBExportCSV.php');
        break;
    case 'importSQL':
    case 'importCSV':

        break;
    case 'deleteCaches':
        $SweetDB->deleteCaches();
        $SweetDB_query->display_query();
        break;
    case 'add_index':
    case 'edit_index':
        $SweetDB_Indexes = new SweetDB_Indexes();
        $SweetDB_Indexes->add_edit_indexes();
        break;
    case 'save_index':
        $SweetDB_Indexes = new SweetDB_Indexes();
        $SweetDB_Indexes->save_index();
        $SweetDB_Indexes->display_indexes();
        break;
    case 'delete_index':
        $SweetDB_Indexes = new SweetDB_Indexes();
        $SweetDB_Indexes->delete_index();
        $SweetDB_Indexes->display_indexes();
        break;
    case 'indexes':
        $SweetDB_Indexes = new SweetDB_Indexes();
        $SweetDB_Indexes->display_indexes();
        break;
    case 'truncateTable':
    if(isset($_REQUEST['tableselect']) && !empty($_REQUEST['tableselect'])) {
        $SweetDB_table = new SweetDB_Table();
        $SweetDB_table->truncateTable();
    } else {
        $tableNameOptions=$SweetDB->getFullTableOptions();
        $SweetDB->sugar_smarty->assign('OPTIONS', $tableNameOptions);
        $SweetDB->sugar_smarty->assign('COMMAND', 'truncateTable');
        $SweetDB->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBTruncateTable.tpl");
    }
    break;
    case 'describeTable':
        if(isset($_REQUEST['tableselect']) && !empty($_REQUEST['tableselect'])) {
            $SweetDB_table = new SweetDB_Table();
            $SweetDB_table->describeTable();
        } else {
            $tableNameOptions=$SweetDB->getFullTableOptions();
            $SweetDB->sugar_smarty->assign('OPTIONS', $tableNameOptions);
            $SweetDB->sugar_smarty->assign('COMMAND', 'describeTable');
            $SweetDB->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBDescribeTable.tpl");
        }
        break;    case 'copyTable':
        if(isset($_REQUEST['newTableName']) && !empty($_REQUEST['newTableName'])) {
            $SweetDB_table = new SweetDB_Table();
            $SweetDB_table->copyTable();
        } else {
            $tableNameOptions=$SweetDB->getFullTableOptions();
            $SweetDB->sugar_smarty->assign('OPTIONS', $tableNameOptions);
            $SweetDB->sugar_smarty->assign('COMMAND', 'copyTable');
            $SweetDB->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBCopyTable.tpl");
        }
        break;
    case 'dropTable':
        if(isset($_REQUEST['tableselect']) && !empty($_REQUEST['tableselect'])) {
            $SweetDB_table = new SweetDB_Table();
            $SweetDB_table->dropTable();
        } else {
            $tableNameOptions=$SweetDB->getFullTableOptions();
            $SweetDB->sugar_smarty->assign('OPTIONS', $tableNameOptions);
            $SweetDB->sugar_smarty->assign('COMMAND', 'dropTable');
            $SweetDB->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBDropTable.tpl");
        }
        break;
    case 'getColumnsNames':
        $json_data=array();
        $json = getJSONobj();
        $table=$_REQUEST['tableselectID'];
        $SweetDB=new SweetDB(FALSE);
        $columnNames=$SweetDB->getTableColumnNames($table);
        foreach($columnNames as $columnName) {
            $json_data[$columnName]=$columnName;
        }
        $json_response = $json->encode($json_data, true);
        echo $json_response;
        break;
    case 'getTypeaheadData':
        if( isset( $_REQUEST['query'] ) && !empty($_REQUEST['query']))
        {
            if( isset( $_REQUEST['table'] ) && !empty($_REQUEST['table']))
            {
                $q=$_REQUEST['query'];
                $field=$_REQUEST['field'];
                $table=$_REQUEST['table'];
                $sql = "SELECT DISTINCT {$field} FROM {$table} WHERE {$field} LIKE '%{$q}%'";
                $result = $GLOBALS['db']->limitQuery($sql, 0, 10, FALSE);
                if ( $result )
                {
                    echo '<ul>'."\n";
                    while( $hash = $GLOBALS['db']->fetchByAssoc( $result ) )
                    {
                        $p = $hash[$field];
                        $p = preg_replace('/(' . $q . ')/i', '<span style="font-weight:bold;">$1</span>', $p);
                        $guid=create_guid();
                        echo "\t".'<li id="autocomplete_'.$guid.'" rel="'.$guid.'">'. utf8_encode( $p ) .'</li>'."\n";
                    }
                    echo '</ul>';
                }
            }
        }
        break;
    case 'searchAllTables':
        $SweetDB_search = new SweetDB_search();
        $searchPattern=$SweetDB_search->getRequestVar('searchPattern',"");
        if(!empty($searchPattern)) {
            //if we have a search pattern then do the search
            $SweetDB_search->doSearchAll($searchPattern);
        } else {
            //show menu
            $SweetDB_search->searchAllMenu();
        }
        break;
    case 'readLog':
        $SweetDB_readLog = new SweetDB_readLog();
        $SweetDB_readLog->logWindow();
        break;
    case 'edit':
    case 'insert':
        $SweetDB_CRUD = new SweetDB_CRUD();
        $SweetDB_CRUD->editWindow();
        break;
    case 'delete':
        $SweetDB_CRUD = new SweetDB_CRUD();
        $SweetDB_CRUD->delete_record();
        break;
    case 'historyDeleteAll':
        if(file_exists('cache/SweetDB_sqlHistory.php')) {
            unlink('cache/SweetDB_sqlHistory.php');
        }
        $SweetDB_query->display_query();
        break;
    case 'historyDelete':
        //no break between this and 'history'
        $sqlHistory = array();
        if(file_exists('cache/SweetDB_sqlHistory.php')) {
            include('cache/SweetDB_sqlHistory.php');
        }
        unset($sqlHistory[$_REQUEST['id']]);
        write_array_to_file('sqlHistory', $sqlHistory, 'cache/SweetDB_sqlHistory.php');
    case 'history':
        historyWindow($SweetDB->sugar_smarty);
        $SweetDB->sugar_smarty->display("custom/modules/Administration/SweetDBAdmin/tpls/SweetDBHistory.tpl");
        break;
    case 'search':
        $SweetDB_search = new SweetDB_search();
        $SweetDB_search->searchWindow();
        break;
    case 'buildsearch':
        $SweetDB_search = new SweetDB_search();
        $SweetDB_search->preformSearch();
        break;
    case 'save':
        $SweetDB_CRUD = new SweetDB_CRUD();
        $SweetDB_CRUD->save_record();
        break;
    case 'historyQuery':
        $sqlHistory = array();
        if(file_exists('cache/SweetDB_sqlHistory.php')) {
            include('cache/SweetDB_sqlHistory.php');
        }
        $_REQUEST['sql'] = $sqlHistory[$_REQUEST['id']]['query'];
        $SweetDB_query->display_query();
        break;
    case 'runLogQuery':
        $SweetDB_readLog = new SweetDB_readLog();
        $SweetDB_readLog->runLogQuery();
        //no break
    case 'query':
    default:
        $SweetDB_query->display_query();
        break;
}


function historyWindow($smarty)
{
    $admin = new Administration();
    $tables = $admin->db->getTablesArray();
    $SweetDB = new SweetDB();
    $table = $SweetDB->getTable();
    $smarty->assign('COMMAND', 'buildsearch');
    $tableNameOptions = "";
    foreach ($tables as $tableNames) {
        if ($table == $tableNames) {
            $selected = "SELECTED /";
        }
        else {
            $selected = '/';
        }
        $tableNameOptions .= "<option value={$tableNames} {$selected}>{$tableNames}</option>";
    }
    $smarty->assign('OPTIONS', $tableNameOptions);
    $sqlHistory = array();
    if(file_exists('cache/SweetDB_sqlHistory.php')) {
        include('cache/SweetDB_sqlHistory.php');
    }
    if(isset($sqlHistory) && !empty($sqlHistory)) {
        krsort($sqlHistory);
    }
    $smarty->assign('SQLHISTORY', $sqlHistory);
}

function getCurrentModule($table)
{
    $SweetDB = new SweetDB();
    $beanSearch=$SweetDB->getBean($table);
    return $beanSearch['moduleName'];
}

function sortByCount($a, $b) {
    return $b['count'] - $a['count'];
}
