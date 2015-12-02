<?php
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

global $mod_strings;
global $app_list_strings;
global $app_strings;

global $current_user;

if (!is_admin($current_user)) sugar_die("Unauthorized access to administration.");
if (isset($GLOBALS['sugar_config']['hide_admin_diagnostics']) && $GLOBALS['sugar_config']['hide_admin_diagnostics']) {
    sugar_die("Unauthorized access to File MD5 Validator.");
}

global $db;
if (empty($db)) {
    $db = DBManagerFactory::getInstance();
}

if (isset($_POST['step']) && $_POST['step'] == 'start') {
    $validator = new MD5FileValidator();
    $validator->build();
    $resultArray = $validator->result;

    //create the scripts
    $validator->createScripts();

    $sugar_smarty = new Sugar_Smarty();
    $sugar_smarty->assign("MOD", $mod_strings);
    $sugar_smarty->assign("APP", $app_strings);

    $sugar_smarty->assign("RETURN_MODULE", "Administration");
    $sugar_smarty->assign("RETURN_ACTION", "index");
    $sugar_smarty->assign("DB_NAME", $db->dbName);

    $sugar_smarty->assign("MODULE", $currentModule);
    $sugar_smarty->assign("PRINT_URL", "index.php?" . $GLOBALS['request_string']);


    $sugar_smarty->assign("MISSING", $validator->outputArrayAsList($resultArray['missing']));
    $sugar_smarty->assign("NOT_STOCK", $validator->outputArrayAsList($resultArray['not stock']));
    $sugar_smarty->assign("CHANGED", $validator->outputArrayAsList($resultArray['changed']));

    $sugar_smarty->display("custom/modules/Administration/fileMD5Result.tpl");

    unlink("custom/fileMD5Test_Progress.php");
} else {
    echo getClassicModuleTitle(
        "Administration",
        array(
            "<a href='index.php?module=Administration&action=index'>{$mod_strings['LBL_MODULE_NAME']}</a>",
            translate('LBL_FILEMD5TEST_TITLE')
        ),
        false
    );

    global $currentModule;

    $GLOBALS['log']->info("Administration File MD5 Test");

    $sugar_smarty = new Sugar_Smarty();
    $sugar_smarty->assign("MOD", $mod_strings);
    $sugar_smarty->assign("APP", $app_strings);

    $sugar_smarty->assign("RETURN_MODULE", "Administration");
    $sugar_smarty->assign("RETURN_ACTION", "index");
    $sugar_smarty->assign("DB_NAME", $db->dbName);

    $sugar_smarty->assign("MODULE", $currentModule);
    $sugar_smarty->assign("PRINT_URL", "index.php?" . $GLOBALS['request_string']);


    $sugar_smarty->assign("ADVANCED_SEARCH_PNG", SugarThemeRegistry::current()->getImage('advanced_search', 'border="0"', null, null, '.gif', $app_strings['LNK_ADVANCED_SEARCH']));
    $sugar_smarty->assign("BASIC_SEARCH_PNG", SugarThemeRegistry::current()->getImage('basic_search', 'border="0"', null, null, '.gif', $app_strings['LNK_BASIC_SEARCH']));

    $sugar_smarty->display("custom/modules/Administration/fileMD5Test.tpl");
}

class MD5FileValidator
{
    //Use this to scan a non-working instance.  Just put the COMPLETE path to the other (non-working) instnace
    // here and make sure it ends in a /
    public $instancePath = '';
    public $scannedFiles = array();
    public $excludePath = array('cache', 'jssource', 'examples', 'custom', '.idea', 'upload');
    public $excludeFile = array('config.php', 'config_override.php', 'config_si.php', '.DS_Store', '.htaccess', '*.txt', '*.log', 'README');
    public $dataFile = "";
    public $result = array();
    public $data = array();
    public $totalFiles = 0;
    private $customModuleNames = array();

    public function __construct()
    {
        $this->totalFiles = $this->countFiles();
        $this->customModuleNames = $this->getCustomModuleList();
        $this->dataFile = $this->getVersion();
    }

    /**
     *
     */
    public function build()
    {
        $this->scanFiles();
    }

    private function getVersion()
    {
        $sugar_version = "";
        $sugar_flavor = "";
        include $this->instancePath . "sugar_version.php";
        $sugar_flavor = strtolower($sugar_flavor);
        return "custom/modules/Administration/MD5Data/{$sugar_version}-{$sugar_flavor}-TokenData.php";
    }


    private function scanFiles()
    {
        $current = 0;
        $this->data = include($this->dataFile);
        if (empty($this->data)) {
            echo "DATA ERROR!";
            exit();
        }

        if (empty($this->instancePath)) {
            $path = realpath(getcwd());
        } else {
            $path = $this->instancePath;
        }

        // Create recursive dir iterator which skips dot folders
        $dir = new RecursiveDirectoryIterator($path,
            FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, folders come before their files
        $objects = new RecursiveIteratorIterator($dir,
            RecursiveIteratorIterator::SELF_FIRST);

        foreach ($objects as $name => $object) {
            $fileName = $object->getFilename();
            $current++;
            $this->updateProgress($current, $this->totalFiles);
            if (!$object->isDir()) {
                $arrayIndexName = substr($name, strlen($path));
                if (substr($arrayIndexName, 0, 1) == DIRECTORY_SEPARATOR) {
                    $arrayIndexName = substr($arrayIndexName, 1);
                }
                $found = false;
                foreach ($this->excludePath as $excludeItem) {
                    $excludeItem = DIRECTORY_SEPARATOR . $excludeItem . DIRECTORY_SEPARATOR;
                    if (stristr($name, $excludeItem) !== false) {
                        $found = true;
                    }
                }
                foreach ($this->excludeFile as $excludeItem) {
                    if (substr($excludeItem, 0, 1) == "*") {
                        $nameLength = strlen($fileName);
                        $needleLength = strlen($excludeItem) - 1;
                        $start = $nameLength - $needleLength;
                        if (substr($fileName, $start) == substr($excludeItem, 1)) {
                            $found = true;
                        }
                    } elseif ($fileName == $excludeItem) {
                        $found = true;
                    }
                }
                if ($found == false) {
                    if (substr($name, -4) == '.php') {
                        $fileMD5 = $this->processPHPCode($name);
                    } elseif (substr($name, -3) == '.js') {
                        $fileMD5 = md5_file($name);
                    } else {
                        $fileMD5 = md5_file($name);
                    }
                    if (array_key_exists($arrayIndexName, $this->data)) {
                        if ($this->data[$arrayIndexName] != $fileMD5) {
                            $this->result['changed'][] = $arrayIndexName;
                        }
                        unset($this->data[$arrayIndexName]);
                    } else {
                        $customModule = false;
                        foreach ($this->customModuleNames as $key => $value) {
                            if (strpos($arrayIndexName, $key) !== false) {
                                $customModule = true;
                            }
                        }
                        if (!$customModule) {
                            $this->result['not stock'][] = $arrayIndexName;
                        }
                    }
                }
            }
        }
        foreach ($this->data as $fileName => $data) {
            $this->result['missing'][] = $fileName;
        }
    }

    /**
     * @param $fullFilePath
     * @return string
     */
    private function processPHPCode($fullFilePath)
    {
        $processedCode = "";
        $tokens = token_get_all(file_get_contents($fullFilePath));
        foreach ($tokens as $index => $data) {
            $tokenType = $data[0];
            $tokenCode = $data[1];

            //Omit comments and blank lines
            if ($tokenType != 370 && $tokenType != 375) {
                $processedCode .= $tokenCode;
            }
        }
        //print_r($tokens);
        return md5($processedCode);
    }

    private function updateProgress($current, $total)
    {
        $percentage = ceil($current / $total * 100);
        $fh = fopen('custom/fileMD5Test_Progress.php', 'w');
        fwrite($fh, "<?php\n");
        fwrite($fh, "echo '{$percentage}';");
        fclose($fh);
    }

    private function countFiles()
    {
        $count = 0;
        if (empty($this->instancePath)) {
            $path = realpath(getcwd());
        } else {
            $path = $this->instancePath;
        }

        // Create recursive dir iterator which skips dot folders
        $dir = new RecursiveDirectoryIterator($path,
            FilesystemIterator::SKIP_DOTS);

        // Flatten the recursive iterator, folders come before their files
        $objects = new RecursiveIteratorIterator($dir,
            RecursiveIteratorIterator::SELF_FIRST);

        foreach ($objects as $name => $object) {
            $fileName = $object->getFilename();
            if (!$object->isDir() && $fileName[0] != '.') {
                $count++;
            }
        }
        return $count;
    }

    private function getCustomModuleList()
    {
        global $beanList;
        global $beanFiles;
        global $moduleList;
        $customModuleNames = array();
        $tmpBeanList = $beanList;
        $tmpbeanFiles = $beanFiles;
        $tmpmoduleList = $moduleList;
        $moduleFile = $this->instancePath . 'custom/application/Ext/Include/modules.ext.php';
        if (file_exists($moduleFile)) {
            require($moduleFile);
            foreach ($beanList as $key => $value) {
                $customModuleNames[$key] = $value;
            }
        }
        $beanList = $tmpBeanList;
        $beanFiles = $tmpbeanFiles;
        $moduleList = $tmpmoduleList;
        return $customModuleNames;
    }

    public function createScripts()
    {
        $sugar_version = "";
        $sugar_flavor = "";
        include "sugar_version.php";
        $filePath = "custom";
        $zip = new ZipArchive();
        if (!empty($this->result['not stock'])) {
            $zipFileName = $filePath . "/MD5Remove.zip";
            $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $script = "";

            foreach ($this->result['not stock'] as $index => $fileName) {
                $script .= "rm {$fileName}\n";
            }

            $scriptFileName = $filePath . "/MD5Remove.sh";
            file_put_contents($scriptFileName, $script, LOCK_EX);
            $zip->addFile($scriptFileName, basename($scriptFileName));
            $zip->close();
            unlink($scriptFileName);
        }
        if (!empty($this->result['changed']) || !empty($this->result['missing'])) {
            $zipFileName = $filePath . "/MD5manifest.zip";
            $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            $newManifest = file("custom/modules/Administration/MD5Data/manifest_template.php");
            if (!empty($this->result['changed'])) {
                foreach ($this->result['changed'] as $index => $fileName) {
                    $newManifest[] = "        array('from'=> '<basepath>/files/{$fileName}',\n";
                    $newManifest[] = "              'to'=> '{$fileName}',\n";
                    $newManifest[] = "        ),\n";
                }
            }
            if (!empty($this->result['missing'])) {
                foreach ($this->result['missing'] as $index => $fileName) {
                    $newManifest[] = "        array('from'=> '<basepath>/files/{$fileName}',\n";
                    $newManifest[] = "              'to'=> '{$fileName}',\n";
                    $newManifest[] = "        ),\n";
                }
            }
            $newManifest[] = "    ),\n);\n?>";
            $scriptFileName1 = $filePath . "/manifest.php";
            file_put_contents($scriptFileName1, implode("", $newManifest), LOCK_EX);
            $zip->addFile($scriptFileName1, basename($scriptFileName1));
            $compiler = file_get_contents(realpath("custom/modules/Administration/MD5Data/manifest_compiler.php"));
            $compiler = str_replace("{\$VERSION}", $sugar_version, $compiler);
            $compiler = str_replace("{\$FLAVOR}", $sugar_flavor, $compiler);
            $scriptFileName2 = $filePath . "/manifest_complier.php";
            file_put_contents($scriptFileName2, $compiler, LOCK_EX);
            $zip->addFile($scriptFileName2, basename($scriptFileName2));
            $zip->close();
            unlink($scriptFileName1);
            unlink($scriptFileName2);
        }
    }

    public function outputArrayAsList($arrayToShow)
    {
        $listResult = "";
        foreach ($arrayToShow as $index => $value) {
            $listResult .= "[{$index}] - {$value}\n";
        }
        return $listResult;
    }
}