<?php

$packageArray = array('fixForecasts', 'fixLanguageFiles', 'ReportDiagnostics');

foreach ($packageArray as $packageName) {
    // Get real path for our folder
    $rootPath = realpath($packageName);
    echo "Working on {$rootPath}\n";
    // Initialize archive object
    $zip = new ZipArchive();
    $zip->open($packageName.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

    // Initialize empty "delete list"
    $filesToDelete = array();

    // Create recursive directory iterator
    /** @var SplFileInfo[] $files */
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($rootPath),
        RecursiveIteratorIterator::LEAVES_ONLY
    );

    foreach ($files as $name => $file) {
        // Skip directories (they would be added automatically)
        if (!$file->isDir()) {
            // Get real and relative path for current file
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($rootPath) + 1);
            echo "Adding '$filePath'\n";
            // Add current file to archive
            $zip->addFile($filePath, $relativePath);
        }
    }

    // Zip archive will be created only after closing object
    $zip->close();

}