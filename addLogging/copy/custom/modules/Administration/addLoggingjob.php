<?php
//Remove logging from a single file
if(isset($_REQUEST['remove']) && $_REQUEST['remove']==2) {
    removeLoggingFromAllFiles();
    exit;
}

if(isset($_REQUEST['file'])) {
    $file = substr($_REQUEST['file'], 0, -1);
} else {
    exit;
}

//Remove logging from a single file
if(isset($_REQUEST['remove']) && $_REQUEST['remove']==1) {
    if(file_exists($file . ".dlu")) {
        unlink($file);
        copy($file . ".dlu", $file);
        unlink($file . ".dlu");
    }
    exit;
}

//do not add logging to a file that has already been processed
if(file_exists($file . '.dlu')) {
    exit;
}

$fileContents = updateFile($file);

if($fileContents!=false) {
    $trash = array_shift($fileContents);
    file_put_contents($file, $fileContents, LOCK_EX);
} else {
    unlink($file . '.dlu');
}

function updateFile($fileName)
{
    $numOfLinesAdded=0;
    //make a backup of the file
    if(!file_exists($fileName . ".dlu")) {
        copy($fileName, $fileName . ".dlu");
    }

    //Read the file into a token list
    $tokenList = readTokenList($fileName);

    //Read the file into an array
    $fileContents = file($fileName);
    //just add one element to the beginning of the array to make it match $tokenlist
    array_unshift($fileContents,"BEGIN");

    foreach ($tokenList as $index => $token) {
        if (is_array($token)) {
            if ($token['TOKEN_NAME'] == 'T_FUNCTION') {
                $functionName = $tokenList[$index + 2][1];
                $lineToInsertAt = intval($tokenList[$index][2]) + $numOfLinesAdded;
                $lastChar=substr(trim($fileContents[$lineToInsertAt]), -1);
                if ($lastChar != "{") {
                    $lineToInsertAt = $lineToInsertAt + 1;
                    for ($i = $index + 3; $i <= count($tokenList); $i++) {
                        if (is_array($tokenList[$i])) {
                            $lineToInsertAt = intval($tokenList[$i][2] + 1 + $numOfLinesAdded);
                        } else {
                            if ($tokenList[$i] == "{") {
                                break;
                            }
                        }
                    }
                }

                $loggingLine = "if(isset(\$GLOBALS['log'])) {\n\t\$GLOBALS['log']->fatal('File:'.__FILE__.' Function:'.__FUNCTION__.' at line '.__LINE__);\n";
                $loggingLine .= "\tif(func_num_args()>0) {\n\t\t\$GLOBALS['log']->fatal('Arguments:'.var_export(func_get_args(),true));\n\t}\n}\n";

                array_splice($fileContents, $lineToInsertAt+1, 0, $loggingLine);
                $numOfLinesAdded++;
            }
        }
    }
    if($numOfLinesAdded==0) {
        return false;
    } else {
        return $fileContents;
    }
}

function readTokenList($fileName)
{
    $tokenList = token_get_all(file_get_contents($fileName));

    //First process the file
    foreach ($tokenList as $index => $keyList) {
        if (is_array($keyList)) {
            $tokenNumber = $keyList[0];
            if ($tokenNumber == 375) {
                //Remove all whitespace
                unset($tokenList[$index]);
            } else {
                $tokenList[$index]['TOKEN_NAME'] = token_name($tokenNumber);
            }
        }
    }
    return $tokenList;
}

function removeLoggingFromAllFiles() {
    $path = realpath(getcwd());

    $objects = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST);
    $Regex = new RegexIterator($objects, '/^.+\.dlu$/i', RecursiveRegexIterator::GET_MATCH);
    foreach($Regex as $name){
        $file=substr($name[0],0,-4);
        unlink($file);
        copy($file . ".dlu", $file);
        unlink($file . ".dlu");
    }
}

