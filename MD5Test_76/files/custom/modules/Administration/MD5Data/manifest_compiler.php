<?php
//SET THIS TO THE FULL PATH TO YOUR SUGARFUEL COMPARISONS DIRECTORY
$myInstancePath = "/www/storage/comparisons/";
if(class_exists('ZipArchive')) {
    $zip = new ZipArchive();
    $zipFileName = "MD5Update.zip";
    $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);
}
if(substr($myInstancePath,-1)!=DIRECTORY_SEPARATOR) {
    $myInstancePath .= DIRECTORY_SEPARATOR;
}
$sfPath = "{$myInstancePath}{$VERSION}".DIRECTORY_SEPARATOR."{$FLAVOR}";
require_once('manifest.php');
mkdir('files');
foreach($installdefs['copy'] as $line) {
    $to=$line['to'];
    $directoryName = dirname($to);
    $fullDirName = 'files'.DIRECTORY_SEPARATOR.$directoryName;
    if(!file_exists($fullDirName)) {
        echo "Making {$fullDirName}\n";
        mkdir($fullDirName, 0777, true);
    }
    echo "Copy " . $sfPath . $to . "\n";
    copy($sfPath.DIRECTORY_SEPARATOR.$to,'files'.DIRECTORY_SEPARATOR.$to);
    if(class_exists('ZipArchive')) {
        $zip->addFile('files'.DIRECTORY_SEPARATOR.$to, 'files'.DIRECTORY_SEPARATOR.$to);
    }
}
if(class_exists('ZipArchive')) {
    $zip->addFile('manifest.php', 'manifest.php');
    $zip->close();
}
?>