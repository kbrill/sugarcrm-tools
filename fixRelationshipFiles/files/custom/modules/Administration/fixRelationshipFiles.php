<?php
if (!defined('sugarEntry')) define('sugarEntry', true);

//require_once('include/utils/array_utils.php');
if (empty($current_language)) {
    $current_language = $sugar_config['default_language'];
}

$GLOBALS['app_list_strings'] = return_app_list_strings_language($current_language);
$GLOBALS['app_strings'] = return_application_language($current_language);

global $current_user;
$current_user = new User();
$current_user->getSystemUser();

$rlf = new fixRelationshipFiles();
$rlf->processRelationshipFiles();

//Run a QR&R
require_once('modules/Administration/QuickRepairAndRebuild.php');
$RAC = new RepairAndClear();
$actions = array('clearAll');
$RAC->repairAndClearAll($actions, array('All Modules'), false, $output);

//EOP


class fixRelationshipFiles
{
    const TYPE_OK = 0;
    const TYPE_UNREADABLE = 1;
    const TYPE_UNWRITABLE = 2;

    const SEV_HIGH = 2;
    const SEV_MEDIUM = 1;
    const SEV_LOW = 0;

    public $customRelationshipFileList = array();
    public $customOtherFileList = array();
    public $customListNames = array();
    private $objectList;
    private $beanFiles;
    private $beanList;

    public function __construct()
    {
        $beanList = $beanFiles = $objectList = array();
        require 'include/modules.php';
        $this->beanList = $beanList;
        $this->beanFiles = $beanFiles;
        $this->objectList = $objectList;

        $this->scanCustomDirectory();
        if (file_exists('fixRelationshipFiles.log')) {
            unlink('fixRelationshipFiles.log');
        }
    }

    /**
     *
     */
    public function processRelationshipFiles()
    {
        foreach ($this->customRelationshipFileList as $fileName) {
            $result = $this->testRelationshipFile($fileName);
            switch ($result) {
                case self::TYPE_UNREADABLE:
                    $this->logThis("Unreadable file: {$fileName}", self::SEV_HIGH);
                    break;
                case self::TYPE_UNWRITABLE:
                    $this->logThis("Unwritable file: {$fileName}", self::SEV_HIGH);
                    break;
                default:
                case self::TYPE_OK:
                    $this->repairFiles($fileName);
                    break;
            }
        }
    }

    /**
     * Tests a PHP file to see if it is readable and writable.
     *
     * @param $fileName
     * @return int
     */
    private function testRelationshipFile($fileName)
    {
        $varCounter = 0;

        //Check to see if we can process the files at all
        if (!is_readable($fileName)) {
            return self::TYPE_UNREADABLE;
        }
        if (!is_writable($fileName)) {
            return self::TYPE_UNWRITABLE;
        }
        return self::TYPE_OK;
    }

    /**
     * @param $fileName
     */
    private function repairFiles($fileName)
    {
        global $layout_defs;

        $this->logThis("Processing file: {$fileName}");

        $layout_defs=array();

        require($fileName);

        //Make a backup of whatever layout files are currently loaded
        $temp_layout_defs = $layout_defs;

        $count=count($layout_defs);
        $changed=false;
        foreach ($layout_defs as $module => $subpanelSetup) {
            foreach($subpanelSetup["subpanel_setup"] as $name=>$panel) {

                if (!empty($panel['module']) && ($panel['module'] == 'Activities' || $panel['module'] == 'History')
                    && isset($panel['collection_list'])
                ) {
                    // skip activities/history, upgrader will take care of them
                    $this->logThis("-> Skipping",self::SEV_LOW);
                    continue;
                }

//                echo "PANEL: {$panel['module']}<br>";
//                echo "BEANLIST: {$this->beanList[$panel['module']]}<br>";

                // check subpanel module. This param should refer to existing module
                if (!empty($panel['module']) &&
                    empty($this->beanList[$panel['module']])) {
                    unset($layout_defs[$module]['subpanel_setup'][$name]);
                    $count--;
                    $changed=true;
                    $this->logThis("--> {$panel['module']} is not a valid module, removing this layout",self::SEV_HIGH);
                }

                if (!empty($panel['get_subpanel_data']) &&
                    strpos($panel['get_subpanel_data'], 'function:') !== false) {
                    unset($layout_defs[$module]['subpanel_setup'][$name]);
                    $count--;
                    $changed=true;
                    $this->logThis("--> {$panel['get_subpanel_data']} is not a valid function, removing this layout",self::SEV_HIGH);
                }
                $objectName = $this->getObjectName($module);
                if (!empty($panel['get_subpanel_data']) && !$this->isValidLink(
                        $module,
                        $objectName,
                        $panel['get_subpanel_data']
                    )
                ) {
                    unset($layout_defs[$module]['subpanel_setup'][$name]);
                    $count--;
                    $changed=true;
                    $this->logThis("--> {$panel['get_subpanel_data']} is not a valid link, removing this layout",self::SEV_HIGH);
                }
            }
        }

        if ($changed) {
            $this->writeRelationshipFile($fileName, $layout_defs, $count);
        } else {
            $this->logThis("-> No Changes");
        }

        //Put the language files back
        $GLOBALS['layout_defs'] = $temp_layout_defs;
    }

    /**
     * @param $fileNameToUpdate
     * @param $app_list_strings
     * @param $app_strings
     */
    private function writeRelationshipFile($fileNameToUpdate, $layout_defs, $count)
    {
        if (!is_writable($fileNameToUpdate)) {
            $this->logThis("{$fileNameToUpdate} is not writable!!!!!!!", self::SEV_HIGH);
        }
        if ($count == 0) {
            $this->logThis("-> This file is no longer valid, deleting it",self::SEV_HIGH);
            if(file_exists($fileNameToUpdate.".bak")) {
                unlink($fileNameToUpdate.".bak");
            }
            copy($fileNameToUpdate,$fileNameToUpdate.".bak");
            unlink($fileNameToUpdate);
        } else {
            $this->logThis("-> Updating");
            $flags = LOCK_EX;
            $phpTag = "<?php";

            foreach ($layout_defs as $key => $value) {
                if(!empty($layout_defs[$key]['subpanel_setup'])) {
                    $the_string = "{$phpTag}\n\$layout_defs['{$key}'] = " .
                        var_export_helper($layout_defs[$key]) .
                        ";\n";
                    sugar_file_put_contents($fileNameToUpdate, $the_string, $flags);
                    $flags = FILE_APPEND | LOCK_EX;
                    $phpTag = "";
                }
            }

            //Make sure the final file is loadable
            // If there is an error this REQUIRE will error out
            require($fileNameToUpdate);
        }
    }

    /**
     * Fills the directory lists so we only have to scan it once.
     *
     * @param string $directory
     */
    private function scanCustomDirectory($directory = 'custom/Extension')
    {
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
                stripos($name, DIRECTORY_SEPARATOR . 'Layoutdefs' . DIRECTORY_SEPARATOR) !== false &&
                substr($name, -4) == '.php'
            ) {
                $this->customRelationshipFileList[] = $name;
            }
        }
    }

    /**
     * flatfile logger
     */
    public function logThis($entry, $severity = self::SEV_LOW)
    {
        global $mod_strings;
        if (file_exists('include/utils/sugar_file_utils.php')) {
            require_once('include/utils/sugar_file_utils.php');
        }
        $log = 'fixRelationshipFiles.log';

        // create if not exists
        $fp = @fopen($log, 'a+');
        if (!is_resource($fp)) {
            $GLOBALS['log']->fatal("fixRelationshipFiles could not open/lock {$log} file");
            die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']);
        }

        $line = date('r') . " [{$severity}] - " . $entry . "\n";

        if (@fwrite($fp, $line) === false) {
            $GLOBALS['log']->fatal("fixRelationshipFiles could not write to {$log}: " . $entry);
            die($mod_strings['ERR_UW_LOG_FILE_UNWRITABLE']);
        }

        if (is_resource($fp)) {
            fclose($fp);
        }

        switch ($severity) {
            case self::SEV_MEDIUM:
                echo "<span style=\"color:orange\">{$entry}</span><br>";
                break;
            case self::SEV_HIGH:
                $entry = strtoupper($entry);
                echo "<span style=\"color:red\">{$entry}</span><br>";
                break;
            case self::SEV_LOW:
            default:
                echo "<span style=\"color:green\">{$entry}</span><br>";
                break;
        }

    }
    /**
     * Check if the link name is valid
     * @param string $module
     * @param string $object
     * @param string $link Link name
     * @return boolean
     */
    protected function isValidLink($module, $object, $link)
    {
        if (empty($GLOBALS['dictionary'][$object]['fields'])) {
            VardefManager::loadVardef($module, $object);
        }
        if (empty($GLOBALS['dictionary'][$object]['fields'])) {
            // weird, we could not load vardefs for this link
            //$this->logThis("Failed to load vardefs for $module:$object");
            return false;
        }
        if (empty($GLOBALS['dictionary'][$object]['fields'][$link]) ||
            empty($GLOBALS['dictionary'][$object]['fields'][$link]['type']) ||
            $GLOBALS['dictionary'][$object]['fields'][$link]['type'] != 'link'
        ) {
            return false;
        }
        return true;
    }

    /**
     * Get name of the object
     * @param string $module
     * @return string|null
     */
    protected function getObjectName($module)
    {
        if (!empty($this->objectList[$module])) {
            return $this->objectList[$module];
        }
        if (!empty($this->beanList[$module])) {
            return $this->beanList[$module];
        }
        $this->logThis("no module nam for: {$module}",self::SEV_MEDIUM);
        return null;
    }
}


