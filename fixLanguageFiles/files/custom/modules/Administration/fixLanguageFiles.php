<?php
/*********************************************************************************
 * Fix Language Files
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
 * @category   Language file repair script
 * @package    fixLanguageFiles
 * @author     Kenneth Brill <kbrill@sugarcrm.com>
 * @copyright  2015-2016 SugarCRM
 * @license    http://www.gnu.org/licenses/agpl.txt
 * @version    3.0
 */
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

$GLOBALS['app_list_strings'] = return_app_list_strings_language($current_language);
$GLOBALS['app_strings'] = return_application_language($current_language);

global $current_user;
global $mod_strings;

if (isset($_POST['step']) && $_POST['step'] == 'start') {
    $rlf = new fixLanguageFiles();
    //check to see if we make backups
    if (isset($_POST['makeBackups'])) {
        $rlf->makeBackups = true;
    }
    //check to see if we delete empty language files
    if (isset($_POST['deleteEmpty'])) {
        $rlf->deleteEmpty = true;
    }

    if(isset($_POST['lowLevelLog']) && $_POST['lowLevelLog']==1) {
        $rlf->lowLevelLog=true;
    } else {
        $rlf->lowLevelLog=false;
    }

    //Run the tests
    $rlf->processLanguageFiles();

    //Run a QR&R
    $GLOBALS['log']->debug("fixLanguageFiles: BEGIN QRR");
    require_once('modules/Administration/QuickRepairAndRebuild.php');
    $RAC = new RepairAndClear();
    $actions = array('clearAll');
    $RAC->repairAndClearAll($actions, array('All Modules'), false, $output);
    $GLOBALS['log']->debug("fixLanguageFiles: END QRR");

    $sugar_smarty = new Sugar_Smarty();
    $sugar_smarty->assign("MOD", $mod_strings);
    $sugar_smarty->assign("APP", $app_strings);

    $sugar_smarty->assign("RETURN_MODULE", "Administration");
    $sugar_smarty->assign("RETURN_ACTION", "index");
    $sugar_smarty->assign("DB_NAME", $db->dbName);

    $sugar_smarty->assign("MODULE", $currentModule);
    $sugar_smarty->assign("PRINT_URL", "index.php?" . $GLOBALS['request_string']);

    //result storage
    $sugar_smarty->assign("MANUALFIXFILES", implode("\n",$rlf->manualFixFiles));
    $sugar_smarty->assign("MODIFIEDFILES", implode("\n",$rlf->modifiedFiles));
    $sugar_smarty->assign("INDEXCHANGES", implode("\n",$rlf->indexChanges));
    $sugar_smarty->assign("REMOVEDFILES", implode("\n",$rlf->removedFiles));
    $sugar_smarty->assign("REMOVEDMODULES", implode("\n",$rlf->removedModules));

    $sugar_smarty->display("custom/modules/Administration/fixLanguageFilesResult.tpl");

    unlink("custom/fixLanguageFiles_Progress.php");
} else {
    $title = getClassicModuleTitle(
        "Administration",
        array(
            "<a href='index.php?module=Administration&action=index'>{$mod_strings['LBL_MODULE_NAME']}</a>",
            translate('LBL_FIXLANGUAGEFILES')
        ),
        false
    );

    global $currentModule;

    $GLOBALS['log']->info("Administration: fixLanguageFiles");

    $sugar_smarty = new Sugar_Smarty();
    $sugar_smarty->assign("MOD", $mod_strings);
    $sugar_smarty->assign("APP", $app_strings);

    $sugar_smarty->assign("TITLE", $title);

    $sugar_smarty->display("custom/modules/Administration/fixLanguageFiles.tpl");
}


class fixLanguageFiles
{
    const TYPE_EMPTY = 0;
    const TYPE_DYNAMIC = 1;
    const TYPE_STATIC = 2;
    const TYPE_UNREADABLE = 3;
    const TYPE_UNWRITABLE = 4;
    const TYPE_SYNTAXERROR = 5;

    const SEV_HIGH = 2;
    const SEV_MEDIUM = 1;
    const SEV_LOW = 0;

    public $customLanguageFileList = array();
    public $customOtherFileList = array();
    public $customListNames = array();
    public $totalFiles = 0;
    public $makeBackups = false;
    public $deleteEmpty = false;
    public $lowLevelLog = true;

    //result storage
    public $manualFixFiles = array();
    public $modifiedFiles = array();
    public $indexChanges = array();
    public $removedFiles = array();
    public $removedModules = array();

    private $dynamicTokens = array('T_OBJECT_OPERATOR', 'T_DOUBLE_COLON', 'T_CONCAT');
    private $arrayCache = array();
    private $queryCache = array();
    private $globalsFound;
    private $objectList;
    private $beanFiles;
    private $beanList;
    private $syntaxError;
    private $reportKeys = array();

    public function __construct()
    {
        $beanList = $beanFiles = $objectList = array();
        require 'include/modules.php';
        $this->beanList = $beanList;
        $this->beanFiles = $beanFiles;
        $this->objectList = $objectList;

        $this->scanCustomDirectory();
        if (file_exists('fixLanguageFiles.log')) {
            unlink('fixLanguageFiles.log');
        }
        if (file_exists("custom/fixLanguageFiles_Progress.php")) {
            unlink("custom/fixLanguageFiles_Progress.php");
        }
        $this->totalFiles = count($this->customLanguageFileList);
        $this->preLoadReportData();
    }

    /**
     * Fills the directory lists so we only have to scan it once.
     *
     * @param string $directory
     */
    private function scanCustomDirectory($directory = 'custom')
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN scanCustomDirectory");
        $result = array();
        $path = realpath($directory);

        // Create recursive dir iterator which skips dot folders
        $dir = new RecursiveDirectoryIterator($path,
            FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, folders come before their files
        $objects = new RecursiveIteratorIterator($dir,
            RecursiveIteratorIterator::SELF_FIRST);

        //in each of these we skip the custom/application/ and custom/modules/MODULE_NAME/Ext directories as they
        // will be updated after a QRR
        foreach ($objects as $name => $object) {
            if (!$object->isDir() &&
                stripos($name, DIRECTORY_SEPARATOR . 'Language' . DIRECTORY_SEPARATOR) !== false &&
                stripos($name, 'custom' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR) === false &&
                (stripos($name, DIRECTORY_SEPARATOR . 'Ext' . DIRECTORY_SEPARATOR) === false ||
                    stripos($name, DIRECTORY_SEPARATOR . 'Extension' . DIRECTORY_SEPARATOR) !== false
                ) &&
                substr($name, -4) == '.php'
            ) {
                $this->customLanguageFileList[] = $name;
            } else if ((substr($name, -4) == '.php' ||
                    substr($name, -3) == '.js' ||
                    substr($name, -4) == '.tpl') &&
                (stripos($name, DIRECTORY_SEPARATOR . 'Ext' . DIRECTORY_SEPARATOR) === false ||
                    stripos($name, DIRECTORY_SEPARATOR . 'Extension' . DIRECTORY_SEPARATOR) !== false
                ) &&
                stripos($name, 'custom' . DIRECTORY_SEPARATOR . 'application' . DIRECTORY_SEPARATOR) === false
            ) {
                $this->customOtherFileList[] = $name;
            }
        }
        $GLOBALS['log']->debug("fixLanguageFiles: END scanCustomDirectory");
    }

    /**
     * Preload the data from reports to speed up the process later
     */
    private function preLoadReportData()
    {
        $sql = "SELECT id FROM reports";
        $result = $GLOBALS['db']->query($sql);
        while ($hash = $GLOBALS['db']->fetchByAssoc($result, false)) {
            $trash = $this->parseReportFilters($$hash['id'], null, null);
        }
    }

    /**
     * Returns the changed $reportContent if there are changes made or FALSE in there
     *  were no changes make
     *
     * @param string $reportID
     * @param string $oldKey
     * @param string $newKey
     * @return bool
     */
    private function parseReportFilters($reportID, $oldKey, $newKey)
    {
        $changed = false;
        $jsonObj = getJSONobj();
        $savedReport = BeanFactory::getBean('Reports', $reportID);
        $reportContent = $jsonObj->decode(html_entity_decode($savedReport->content));

        if (!is_array($this->reportKeys[$reportID])) {
            $this->reportKeys[$reportID] = array();
        }
        foreach ($reportContent['filters_def'] as $index => $filterGroup) {
            foreach ($filterGroup as $subIndex => $filterList) {
                if ($subIndex !== 'operator') {
                    foreach ($filterList as $subSubIndex => $filterIndex) {
                        if ($subSubIndex !== 'operator') {
                            foreach ($filterIndex['input_name0'] as $filterNameIndex => $filterValueIndex) {
                                $this->reportKeys[$reportID][$filterValueIndex] = $filterValueIndex;
                                if ($filterValueIndex == $oldKey) {
                                    $reportContent['filters_def'][$index][$subIndex][$subSubIndex]['input_name0'][$filterNameIndex] = $newKey;
                                    unset($this->reportKeys[$reportID][$filterValueIndex]);
                                    $this->reportKeys[$reportID][$newKey] = $newKey;
                                    $changed = true;
                                    $this->logThis("Filter for report {$reportID} changed from '{$filterValueIndex}' to '{$newKey}'", self::SEV_LOW);
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($changed) {
            return $reportContent;
        } else {
            return false;
        }
    }

    /**
     * flatfile logger
     */
    public function logThis($entry, $severity = self::SEV_LOW)
    {
        global $mod_strings;

        if($severity==self::SEV_LOW && $this->lowLevelLog=false) {
            return;
        }

        if (file_exists('include/utils/sugar_file_utils.php')) {
            require_once('include/utils/sugar_file_utils.php');
        }
        $log = 'fixLanguageFiles.log';

        // create if not exists
        $fp = @fopen($log, 'a+');
        if (!is_resource($fp)) {
            $GLOBALS['log']->fatal('fixLanguageFiles could not open/lock upgradeWizard.log file');
            die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']);
        }

        $line = date('r') . " [{$severity}] - " . $entry . "\n";

        if (@fwrite($fp, $line) === false) {
            $GLOBALS['log']->fatal('fixLanguageFiles could not write to upgradeWizard.log: ' . $entry);
            die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']);
        }

        if (is_resource($fp)) {
            fclose($fp);
        }

//        switch ($severity) {
//            case self::SEV_MEDIUM:
//                echo "<span style=\"color:orange\">{$entry}</span><br>";
//                break;
//            case self::SEV_HIGH:
//                $entry = strtoupper($entry);
//                echo "<span style=\"color:red\">{$entry}</span><br>";
//                break;
//            case self::SEV_LOW:
//            default:
//                echo "<span style=\"color:green\">{$entry}</span><br>";
//                break;
//        }

    }

    /**
     *
     */
    public function processLanguageFiles()
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN processLanguageFiles");
        $currentFileCount = 0;
        foreach ($this->customLanguageFileList as $fileName) {
            $currentFileCount++;
            $result = $this->testLanguageFile($fileName);
            switch ($result) {
                case self::TYPE_SYNTAXERROR:
                    $this->logThis("Syntax Error in file: ".$this->truncateFileName($fileName).": {$this->syntaxError}", self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_UNREADABLE:
                    $this->logThis("Unreadable file: ".$this->truncateFileName($fileName)."", self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->logThis("Unwritable file: ".$this->truncateFileName($fileName)."", self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_EMPTY:
                    $this->logThis("Empty language file: ".$this->truncateFileName($fileName));
                    if ($this->deleteEmpty) {
                        unlink($fileName);
                        $this->removedFiles[] = $this->truncateFileName($fileName);
                        $this->logThis("-> Deleted file");
                    }
                    break;
                case self::TYPE_DYNAMIC:
                    $this->logThis("You will need to manually update: ".$this->truncateFileName($fileName), self::SEV_HIGH);
                    $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                    break;
                case self::TYPE_STATIC:
                    $this->repairStaticFile($fileName);
                    break;
            }
            $this->updateProgress($currentFileCount, $this->totalFiles);
        }
        $GLOBALS['log']->debug("fixLanguageFiles: END processLanguageFiles");
    }

    /**
     * Tests a PHP file to see if it is a list of static variables or if it has dynamic content in it.
     *
     * Dynamic = $app_list_strings['LBL_EMAIL_ADDRESS_BOOK_TITLE_ICON'] =
     *      SugarThemeRegistry::current()->getImage('icon_email_addressbook',
     *                                              "",
     *                                              null,
     *                                              null,
     *                                              ".gif",
     *                                              'Address Book').' Address Book';
     *
     * Static = $app_list_strings['LBL_EMAIL_ADDRESS_BOOK_TITLE'] = 'Address Book';
     *
     * @param $fileName
     * @return int
     */
    private function testLanguageFile($fileName)
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN testLanguageFile: {$fileName}");
        $varCounter = 0;

        //Check to see if we can process the files at all
        if (!is_readable($fileName)) {
            return self::TYPE_UNREADABLE;
        }
        if (!is_writable($fileName)) {
            return self::TYPE_UNWRITABLE;
        }

        // Get the shell output from the syntax check command
        //$output = shell_exec('php -l "' . $fileName . '"');
        $output = $this->testPHPSyntax(file_get_contents($fileName));

        // If the error text above was matched, throw an exception containing the syntax error
        if ($output !== false) {
            $syntaxError = "";
            foreach ($output as $msg => $line) {
                $syntaxError .= "\nError: '{$msg}' in line {$line}";
            }
            $this->syntaxError = $syntaxError;
            return self::TYPE_SYNTAXERROR;
        }

        $tokens = token_get_all(file_get_contents($fileName));
        foreach ($tokens as $index => $token) {
            if (is_array($token)) {
                $tokenText = token_name($token[0]);
            } else {
                //this isn't translated for some reason
                if ($tokens[$index] == '.') {
                    $tokenText = 'T_CONCAT';
                } else {
                    $tokenText = "";
                }
            }
            //Check to see if this line contains a variable.  If so
            // then this file isn't empty
            if ($tokenText == 'T_VARIABLE') {
                $varCounter++;
            }
            //Check to see if this line contains one of the
            // dynamic tokens
            if (in_array($token[0], $this->dynamicTokens)) {
                return self::TYPE_DYNAMIC;
            }
        } //end foreach
        //If there were no variables in the file then it is considered empty
        if ($varCounter == 0) {
            return self::TYPE_EMPTY;
        }
        return self::TYPE_STATIC;
    }

    /**
     * @param $code
     * @return string
     *
     */
    private function testPHPSyntax($code)
    {
        $braces = 0;
        $inString = 0;

        // First of all, we need to know if braces are correctly balanced.
        // This is not trivial due to variable interpolation which
        // occurs in heredoc, backticked and double quoted strings
        foreach (token_get_all($code) as $token) {
            if (is_array($token)) {
                switch ($token[0]) {
                    case T_CURLY_OPEN:
                    case T_DOLLAR_OPEN_CURLY_BRACES:
                    case T_START_HEREDOC:
                        ++$inString;
                        break;
                    case T_END_HEREDOC:
                        --$inString;
                        break;
                }
            } else if ($inString & 1) {
                switch ($token) {
                    case '`':
                    case '"':
                        --$inString;
                        break;
                }
            } else {
                switch ($token) {
                    case '`':
                    case '"':
                        ++$inString;
                        break;

                    case '{':
                        ++$braces;
                        break;
                    case '}':
                        if ($inString) --$inString;
                        else {
                            --$braces;
                            if ($braces < 0) break 2;
                        }

                        break;
                }
            }
        }

        // Display parse error messages and use output buffering to catch them
        $inString = @ini_set('log_errors', false);
        $token = @ini_set('display_errors', true);
        ob_start();

        // If $braces is not zero, then we are sure that $code is broken.
        // We run it anyway in order to catch the error message and line number.

        // Else, if $braces are correctly balanced, then we can safely put
        // $code in a dead code sandbox to prevent its execution.
        // Note that without this sandbox, a function or class declaration inside
        // $code could throw a "Cannot redeclare" fatal error.

        $braces || $code = "if(0){{$code}\n}";

        $code = str_replace("<?php","",$code);
        $code = str_replace("?>","",$code);

        if (false === eval($code)) {
            if ($braces) {
                $braces = PHP_INT_MAX;
            } else {
                // Get the maximum number of lines in $code to fix a border case
                false !== strpos($code, "\r") && $code = strtr(str_replace("\r\n", "\n", $code), "\r", "\n");
                $braces = substr_count($code, "\n");
            }

            $code = ob_get_clean();
            $code = strip_tags($code);

            // Get the error message and line number
            if (preg_match("'syntax error, (.+) in .+ on line (\d+)$'s", $code, $code)) {
                $code[2] = (int)$code[2];
                $code = $code[2] <= $braces
                    ? array($code[1], $code[2])
                    : array('unexpected $end' . substr($code[1], 14), $braces);
            } else {
                $code = array('syntax error', 0);
            }
        } else {
            ob_end_clean();
            $code = false;
        }

        @ini_set('display_errors', $token);
        @ini_set('log_errors', $inString);

        return $code;
    }

    /**
     * This function tell me if the values in the custom language file replaces the entire array
     *    or just adds/edits the values in the array.
     *
     * @param string $key
     * @param array $fileList
     * @return bool
     */
    private function diffAppListStrings($key,$fileList) {
        global $current_language;
        $appListStrings = return_app_list_strings_language($current_language);

        $diff = array_diff_assoc($appListStrings[$key],$fileList);
        $diff2 = array_diff_assoc($fileList,$appListStrings[$key]);

        if(count($diff)==0 && count($diff2)==0) {
            $result=true;
        } else {
            $result=false;
        }

        return $result;
    }

    /**
     * @param string $fileName
     */
    private function repairStaticFile($fileName)
    {
        global $app_list_strings;
        global $app_strings;
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN repairStaticFile {$fileName}");
        $this->logThis("Processing ".$this->truncateFileName($fileName));

        $app_list_strings = array();
        $app_strings = array();
        $count = 0;
        $keyCount = 0;

        //Make a backup of whatever language files are currently loaded
        $temp_app_list_strings = $GLOBALS['app_list_strings'];
        $GLOBALS['app_list_strings'] = array();
        $temp_app_strings = $GLOBALS['app_strings'];
        $GLOBALS['app_strings'] = array();
        $app_strings = array();
        $app_list_strings = array();

        //Process the file to remove $GLOBALS[...]
        $this->globalsFound = false;
        $processedFile = $this->fixGlobals($fileName);
        if (!empty($processedFile)) {
            require($processedFile);
            unlink($processedFile);
        } else {
            require($fileName);
        }
        $changed = $this->globalsFound;

        //See if $app_strings are supposed to be app_list_strings
        foreach ($app_strings as $key => $value) {
            if (is_array($value)) {
                //Should be an app_list_string
                $this->logThis("\$app_string '{$key}' in '".$this->truncateFileName($fileName)."' is an array.  Arrays are not allowed in \$app_strings!", self::SEV_HIGH);
            }
        }

        foreach ($app_list_strings as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $mKey => $mValue) {
                    if (is_null($mValue)) {
                        $this->logThis("\$app_list_strings['{$key}']['{$mKey}'] in '".$this->truncateFileName($fileName)."' is being removed as it is set to NULL!", self::SEV_MEDIUM);
                        $changed = true;
                    }
                    if ($key == 'moduleList' && empty($mValue)) {
                        $this->logThis("\$app_list_strings['{$key}']['{$mKey}'] in '".$this->truncateFileName($fileName)."' is being removed as it is an empty value!", self::SEV_MEDIUM);
                        $changed = true;
                    }
                }
            }
//            if ($key == 'moduleList' || $key == 'moduleListSingular') {
//                if (count($value)>1) {
//                    $this->logThis("\$app_list_strings '{$key}' in '".$this->truncateFileName($fileName)."' is being converted from an array to individual strings!", self::SEV_MEDIUM);
//                    $changed = true;
//                }
//            }
        }

        $fullArray=array();

        //Now go through and remove the characters [& / - ( )] and spaces (in some cases) from array keys
        $badChars = array(' & ', '&', ' - ', '-', '/', ' / ', '(', ')');
        $goodChars = array('_', '_', '_', '_', '_', '_', '', '');
        foreach ($app_list_strings as $listName => $listValues) {
            $fullArray[$listName]=$this->diffAppListStrings($listName,$listValues);
            foreach ($app_list_strings[$listName] as $oldKey => $sValues) {
                $newKey = str_replace($badChars, $goodChars, $oldKey, $count);
                if ($newKey != $oldKey) {
                    //replace the bad sub-key
                    $keyCount = $keyCount + $count;
                    $changed = true;
                    $app_list_strings[$listName][$newKey] = $sValues;
                    unset($app_list_strings[$listName][$oldKey]);
                    $listField = $this->findListField($listName);
                    $this->updateDatabase($listField, $oldKey, $newKey);
                    $this->updateFieldsMetaDataTable($listField, $newKey, $oldKey);
                    $this->updatefiles($newKey, $oldKey);
                    $this->updateReportFilters($oldKey, $newKey);
                    $this->modifiedFiles[$fileName] = $this->truncateFileName($fileName);
                    $this->indexChanges[$oldKey] = $newKey;
                }
            }
        }

        //Make sure everything in moduleList has a corresponding beanFile
        if (array_key_exists('moduleList', $app_list_strings)) {
            foreach ($app_list_strings['moduleList'] as $moduleKey => $moduleName) {
                $okModules = array('iFrames', 'Feeds', 'Home', 'Dashboard', 'Sync', 'Calendar', 'Activities', 'Reports',
                    'Queues');
                if (!isset($this->beanList[$moduleKey]) || empty($this->beanList[$moduleKey])) {
                    if (!in_array($moduleKey, $okModules)) {
                        //dont touch anything in modulebuilder
                        if (stristr($fileName, "ModuleBuilder") === false) {
                            $this->logThis("\$app_list_strings['moduleList']['{$moduleKey}'] in '".$this->truncateFileName($fileName)."' is invalid.  There is no corresponding beanFile", self::SEV_HIGH);
                            unset($app_list_strings['moduleList'][$moduleKey]);
                            $this->removedModules[$moduleKey] = $moduleKey;
                            if (stripos($fileName, 'Extension') !== false &&
                                count($app_list_strings) == 1
                            ) {
                                $this->logThis("'".$this->truncateFileName($fileName)."' Renamed", self::SEV_HIGH);
                                rename($fileName, $fileName . '.old');
                                $this->removedFiles = $this->truncateFileName($fileName);
                            }
                        }
                    }
                }
            }
        }

        if ($changed) {
            $this->modifiedFiles[$fileName] = $fileName;
            $this->writeLanguageFile($fileName, $app_list_strings, $app_strings, $keyCount, $fullArray);
        } else {
            $this->logThis("-> No Changes");
        }

        //Put the language files back
        $GLOBALS['app_list_strings'] = $temp_app_list_strings;
        $GLOBALS['app_strings'] = $temp_app_strings;
        $GLOBALS['log']->debug("fixLanguageFiles: END repairStaticFile");
    }

    /**
     * Takes a language file and converts any
     *  $GLOBALS['app_list_strings']['whatever']=array (
     * into
     *  $app_list_strings['whatever']=array (
     *
     * @param string $fileName
     * @return string
     */
    private function fixGlobals($fileName)
    {
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN fixGlobals {$fileName}");
        $tmpFileName = "";
        $fileContents = sugar_file_get_contents($fileName);
        $globalsRemoved = preg_replace("/(GLOBALS\[')(\w+)('\])(\[')(\w+)('\])/", "$2$4$5$6", $fileContents, -1, $count);

        if ($count > 0) {
            $this->globalsFound = true;
            $this->modifiedFiles[$fileName] = $this->truncateFileName($fileName);
            $this->logThis("-> {$count} \$GLOBALS removed from ".$this->truncateFileName($fileName),self::SEV_MEDIUM);
            $tmpFileName = sys_get_temp_dir() . "/TMP_INCLUDE.php";
            sugar_file_put_contents($tmpFileName, $globalsRemoved, LOCK_EX);
        }
        $GLOBALS['log']->debug("fixLanguageFiles: END fixGlobals");
        return $tmpFileName;
    }

    /**
     * @param string $listName
     * @param string $module
     * @return array
     */
    private function findListField($listName, $module = "")
    {
        global $beanList;
        $moduleList = array();
        $retArray = array();

        //if the array as already been processed then just return the value
        if (isset($this->arrayCache[$listName])) {
            return $this->arrayCache[$listName];
        }

        if (!empty($module) && array_key_exists($module, $beanList)) {
            $moduleList[$module] = $beanList[$module];
        } else {
            $moduleList = $beanList;
        }
        foreach ($moduleList as $bean => $object) {
            $focus = BeanFactory::getBean($bean);
            if (isset($focus->field_defs) && !empty($focus->field_defs)) {
                foreach ($focus->field_defs as $fieldName => $definitions) {
                    if (array_key_exists('options', $definitions) && $definitions['options'] == $listName) {
                        $retArray[$bean] = $fieldName;
                    }
                }
            }
        }
        if (empty($retArray)) {
            $this->logThis("Could not locate '{$listName}' in bean '{$bean}', it appears not to be used as a dropdown list", self::SEV_MEDIUM);

        }
        $this->arrayCache[$listName] = $retArray;
        return $retArray;
    }

    /**
     * @param string $fieldData
     * @param string $oldValue
     * @param string $newValue
     */
    private function updateDatabase($fieldData, $oldValue, $newValue)
    {
        if (!empty($fieldData)) {
            foreach ($fieldData as $module => $fieldName) {
                $bean = BeanFactory::getBean($module);
                $fieldDef = $bean->field_defs[$fieldName];
                if (array_key_exists('source', $fieldDef) && $fieldDef['source'] == 'custom_fields') {
                    $table = $bean->table_name . '_cstm';
                } else {
                    $table = $bean->table_name;
                }
                $query = str_replace(array("\r", "\n"), "", "UPDATE {$table}
                            SET {$fieldName} = REPLACE({$fieldName}, '{$oldValue}', '{$newValue}')
                            WHERE {$fieldName} LIKE '%^{$oldValue}^%' OR
                                  {$fieldName} = '{$oldValue}'");
                $query = preg_replace('/\s+/', ' ', $query);
                //dont bother running the same query twice
                if (!in_array($query, $this->queryCache)) {
                    $this->logThis("-> Running Query: {$query}");
                    $GLOBALS['db']->query($query, true, "Error updating {$table}.");
                    $this->queryCache[] = $query;
                }
            }
        }
    }

    /**
     * @param string $listName
     * @param string $newKey
     * @param string $oldKey
     */
    private function updateFieldsMetaDataTable($listName, $newKey, $oldKey)
    {
        foreach ($listName as $moduleName => $fieldName) {
            $query = str_replace(array("\r", "\n"), "", "UPDATE fields_meta_data
                        SET default_value = REPLACE(default_value, '{$oldKey}', '{$newKey}')
                        WHERE custom_module='{$moduleName}'
                          AND (default_value LIKE '%^{$oldKey}^%' OR default_value = '{$oldKey}')
                          AND ext1='{$fieldName}'");
            $query = preg_replace('/\s+/', ' ', $query);
            //dont bother running the same query twice
            if (!in_array($query, $this->queryCache)) {
                $this->logThis("-> Running Query: {$query}");
                $GLOBALS['db']->query($query, true, "Error updating fields_meta_data.");
                $this->queryCache[] = $query;
            }
        }
    }

    /**
     * Shows a list of files that might need manual updating
     *
     * @param string $searchString
     * @param string $oldKey
     * @return bool
     */
    private function updateFiles($newKey, $oldKey)
    {
        $matches = array();
        if (empty($newKey) || in_array($oldKey, $this->customListNames)) {
            return false;
        }

        $searchString1 = "'" . $oldKey . "'";
        $searchString2 = '"' . $oldKey . '"';

        foreach ($this->customOtherFileList as $fileName) {
            $text = sugar_file_get_contents($fileName);
            if (strpos($text, $searchString1) !== FALSE ||
                strpos($text, $searchString2) !== FALSE
            ) {
                $oldText = array(
                    "=> '{$oldKey}'",
                    "=> \"{$oldKey}\"",
                    "=>'{$oldKey}'",
                    "=>\"{$oldKey}\"",
                    "= '{$oldKey}'",
                    "= \"{$oldKey}\"",
                    "='{$oldKey}'",
                    "=\"{$oldKey}\""
                );
                $newText = array(
                    "=> '{$newKey}'",
                    "=> \"{$newKey}\"",
                    "=>'{$newKey}'",
                    "=>\"{$newKey}\"",
                    "= '{$newKey}'",
                    "= \"{$newKey}\"",
                    "='{$newKey}'",
                    "=\"{$newKey}\""

                );
                $text = str_replace($oldText, $newText, $text);
                if (strpos($text, $searchString1) !== FALSE) {
                    $matches[$fileName] = true;
                    $this->customListNames[] = $oldKey;
                } else {
                    $this->modifiedFiles[$fileName] = $this->truncateFileName($fileName);
                    $this->backupFile($fileName);
                    sugar_file_put_contents($fileName, $text, LOCK_EX);
                }
            }
        }

        if (!empty($matches)) {
            $this->logThis("------------------------------------------------------------", self::SEV_MEDIUM);
            $this->logThis("These files MAY need to be updated to reflect the new key (New '{$newKey}' vs. old '{$oldKey}')", self::SEV_MEDIUM);
            $this->logThis("-------------------------------------------------------------", self::SEV_MEDIUM);
            foreach ($matches as $fileName => $flag) {
                $this->manualFixFiles[$fileName] = $this->truncateFileName($fileName);
                $this->logThis($this->truncateFileName($fileName), self::SEV_MEDIUM);
            }
            $this->logThis("-------------------------------------------------------------", self::SEV_MEDIUM);
        }
    }

    private function truncateFileName($fileName) {
        return str_replace($_SERVER['CONTEXT_DOCUMENT_ROOT'].'/',"",$fileName);
    }

    /**
     * @param string $srcFile
     * @return bool
     */
    private function backupFile($srcFile)
    {
        //Just return if no backup files are needed
        if ($this->makeBackups == false) {
            return true;
        }

        $dstFile = str_replace('custom', 'custom_flf', $srcFile);
        if(!file_exists(dirname($dstFile))) {
            if (!mkdir(dirname($dstFile), 0777, true)) {
                $this->logThis("Could not create " . dirname($dstFile) . ", so backup file could not be created");
                return false;
            }
        }
        if(file_exists($dstFile)) {
            unlink($dstFile);
        }
        if (!copy($srcFile, $dstFile)) {
            $this->logThis("Could not copy to {$dstFile}, so backup file could not be created");
            return false;
        }
        return true;
    }

    /**
     * @param string $fileNameToUpdate
     * @param array $app_list_strings
     * @param array $app_strings
     * @param int $keyCount - How many keys were changed
     * @param bool $fullArray - denotes if this is the full array or just additions
     */
    private function writeLanguageFile($fileNameToUpdate, $app_list_strings, $app_strings, $keyCount, $fullArray)
    {
        $this->logThis("-> Updating");
        $this->backupFile($fileNameToUpdate);
        $GLOBALS['log']->debug("fixLanguageFiles: BEGIN writeLanguageFile {$fileNameToUpdate}");
        if (!is_writable($fileNameToUpdate)) {
            $this->logThis("{$fileNameToUpdate} is not writable!!!!!!!", self::SEV_HIGH);
        }
        if ($keyCount > 0) {
            $this->logThis("-> {$keyCount} keys changed");
        }
        $flags = LOCK_EX;
        $moduleList = false;
        $moduleListSingular = false;
        $phpTag = "<?php";

        if (count($app_list_strings) > 0) {
            foreach ($app_list_strings as $key => $value) {
                if ($key == 'moduleList' && $moduleList == false) {
                    $the_string = "{$phpTag}\n";
                    foreach ($value as $mKey => $mValue) {
                        if (!empty($mValue)) {
                            $the_string .= "\$app_list_strings['moduleList']['{$mKey}'] = '{$mValue}';\n";
                        }
                    }
                    sugar_file_put_contents($fileNameToUpdate, $the_string, $flags);
                    $flags = FILE_APPEND | LOCK_EX;
                    $phpTag = "";
                    $moduleList = true;
                } elseif ($key == 'moduleListSingular' && $moduleListSingular == false) {
                    $the_string = "{$phpTag}\n";
                    foreach ($value as $mKey => $mValue) {
                        if (!empty($mValue)) {
                            $the_string .= "\$app_list_strings['moduleListSingular']['{$mKey}'] = '{$mValue}';\n";
                        }
                    }
                    sugar_file_put_contents($fileNameToUpdate, $the_string, $flags);
                    $flags = FILE_APPEND | LOCK_EX;
                    $phpTag = "";
                    $moduleListSingular = true;
                } else {
                    if($fullArray) {
                        $the_string = "{$phpTag}\n\$app_list_strings['{$key}'] = " .
                            var_export_helper($app_list_strings[$key]) .
                            ";\n";
                    } else {
                        $the_string = "{$phpTag}\n";
                        foreach ($value as $mKey => $mValue) {
                            if (!empty($mValue)) {
                                $the_string .= "\$app_list_strings['moduleList']['{$mKey}'] = '{$mValue}';\n";
                            }
                        }
                    }
                    sugar_file_put_contents($fileNameToUpdate, $the_string, $flags);
                    $flags = FILE_APPEND | LOCK_EX;
                    $phpTag = "";
                }
            }
        } else {
            $flags = LOCK_EX;
        }

        if (count($app_strings) > 0) {
            $the_string = "{$phpTag}\n";
            foreach ($app_strings as $key => $value) {
                if ($value == NULL || $key == NULL) {
                    continue;
                }
                $the_string .= "\$app_strings['{$key}']='{$value}';\n";
            }
            sugar_file_put_contents($fileNameToUpdate, $the_string, $flags);
        }
        //Make sure the final file is loadable
        // If there is an error this REQUIRE will error out
        require($fileNameToUpdate);
        $GLOBALS['log']->debug("fixLanguageFiles: END writeLanguageFile");
    }

    /**
     * @param INT $current
     * @param INT $total
     */
    private function updateProgress($current, $total)
    {
        $percentage = ceil($current / $total * 100);
        $fh = fopen('custom/fixLanguageFiles_Progress.php', 'w');
        fwrite($fh, "<?php\n");
        fwrite($fh, "echo '{$percentage}';");
        fclose($fh);
    }

    /**
     * @param $oldKey
     * @param $newKey
     */
    private function updateReportFilters($oldKey, $newKey)
    {
        $jsonObj = getJSONobj();
        foreach ($this->reportKeys as $reportID => $filterKeys) {
            if (in_array($oldKey, $filterKeys)) {
                $contents = $this->parseReportFilters($reportID, $oldKey, $newKey);
                $encodedContent = $jsonObj->encode(htmlentities($contents));
                $savedReport = BeanFactory::getBean('Reports', $reportID);
                $savedReport->content = $encodedContent;
                $savedReport->save();
                $this->logThis("Report {$reportID} Updated with new key '{$newKey}'", self::SEV_MEDIUM);
            }
        }
    }
}


