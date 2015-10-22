<?php
$i = 0;
$fileNames=scanCustomDirectory();
foreach ($fileNames as $fileName) {
    //echo "Testing {$fileName}\n";
    $file = file_get_contents($fileName);
    //Simply replace SEARCH TERM with whatever you are looking for
    //read the README file to see other tips
    $newContents = str_replace("'SEARCH TERM';", "'REPLACE TERM{$i}';", $file, $count);
    if ($count > 0) {
        echo "-->Updating {$fileName}\n";
        file_put_contents($fileName, $newContents);
    }
    $i++;
}

/**
 * Fills the directory lists so we only have to scan it once.
 *
 * @param string $directory
 */
function scanCustomDirectory($directory = 'custom')
{
    $customLanguageFileList = array();
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
            substr($name, -4) == '.php'
        ) {
            $customLanguageFileList[] = $name;
        }
    }
    return $customLanguageFileList;
}


