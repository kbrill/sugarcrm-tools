<?php

$manifest = array (
  'acceptable_sugar_versions' => 
  array (
    'exact_matches' => 
    array (
      0 => '7.6.1.0',
      1 => '7.6.0.0',
      2 => '7.5.2.3',
      3 => '7.6.2.0',
      4 => '6.5.23',
    ),
  ),
  'acceptable_sugar_flavors' => 
  array (
    0 => 'CE',
    1 => 'PRO',
    2 => 'CORP',
    3 => 'ENT',
    4 => 'ULT',
  ),
  'readme' => '',
  'key' => 1453847221,
  'author' => 'Ken Brill',
  'description' => 'Adds logging lines to code in SugarCRM',
  'icon' => '',
  'is_uninstallable' => true,
  'name' => 'addLogging',
  'published_date' => '2016-01-26 22:27:01',
  'type' => 'module',
  'version' => 1453847221,
  'remove_tables' => '',
);

$installdefs = array (
  'id' => 1453847221,
  'copy' => 
  array (
    0 => 
    array (
      'from' => '<basepath>/copy/custom/Extension/application/Ext/EntryPointRegistry/addLogging.php',
      'to' => 'custom/Extension/application/Ext/EntryPointRegistry/addLogging.php',
    ),
    1 => 
    array (
      'from' => '<basepath>/copy/custom/Extension/modules/Administration/Ext/Administration/addLogging.php',
      'to' => 'custom/Extension/modules/Administration/Ext/Administration/addLogging.php',
    ),
    2 => 
    array (
      'from' => '<basepath>/copy/custom/Extension/modules/Administration/Ext/Language/en_us.addLogging.php',
      'to' => 'custom/Extension/modules/Administration/Ext/Language/en_us.addLogging.php',
    ),
    3 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/addLogging.php',
      'to' => 'custom/modules/Administration/addLogging.php',
    ),
    4 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/addLoggingjob.php',
      'to' => 'custom/modules/Administration/addLoggingjob.php',
    ),
    5 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/connectors/jaoconnector.php',
      'to' => 'custom/modules/Administration/connectors/jaoconnector.php',
    ),
    6 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/connectors/svn.php',
      'to' => 'custom/modules/Administration/connectors/svn.php',
    ),
    7 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/jaofiletree.css',
      'to' => 'custom/modules/Administration/jaofiletree.css',
    ),
    8 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/jaofiletree.js',
      'to' => 'custom/modules/Administration/jaofiletree.js',
    ),
    9 => 
    array (
      'from' => '<basepath>/copy/custom/modules/Administration/jquery-1.8.3.js',
      'to' => 'custom/modules/Administration/jquery-1.8.3.js',
    ),
    10 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/application.png',
      'to' => 'custom/themes/default/images/application.png',
    ),
    11 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/code.png',
      'to' => 'custom/themes/default/images/code.png',
    ),
    12 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/css.png',
      'to' => 'custom/themes/default/images/css.png',
    ),
    13 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/db.png',
      'to' => 'custom/themes/default/images/db.png',
    ),
    14 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/directory.png',
      'to' => 'custom/themes/default/images/directory.png',
    ),
    15 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/doc.png',
      'to' => 'custom/themes/default/images/doc.png',
    ),
    16 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/drive.png',
      'to' => 'custom/themes/default/images/drive.png',
    ),
    17 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/file.png',
      'to' => 'custom/themes/default/images/file.png',
    ),
    18 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/film.png',
      'to' => 'custom/themes/default/images/film.png',
    ),
    19 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/flash.png',
      'to' => 'custom/themes/default/images/flash.png',
    ),
    20 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/folder_open.png',
      'to' => 'custom/themes/default/images/folder_open.png',
    ),
    21 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/html.png',
      'to' => 'custom/themes/default/images/html.png',
    ),
    22 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/java.png',
      'to' => 'custom/themes/default/images/java.png',
    ),
    23 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/linux.png',
      'to' => 'custom/themes/default/images/linux.png',
    ),
    24 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/music.png',
      'to' => 'custom/themes/default/images/music.png',
    ),
    25 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/pdf.png',
      'to' => 'custom/themes/default/images/pdf.png',
    ),
    26 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/php.png',
      'to' => 'custom/themes/default/images/php.png',
    ),
    27 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/picture.png',
      'to' => 'custom/themes/default/images/picture.png',
    ),
    28 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/ppt.png',
      'to' => 'custom/themes/default/images/ppt.png',
    ),
    29 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/psd.png',
      'to' => 'custom/themes/default/images/psd.png',
    ),
    30 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/ruby.png',
      'to' => 'custom/themes/default/images/ruby.png',
    ),
    31 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/script.png',
      'to' => 'custom/themes/default/images/script.png',
    ),
    32 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/spinner.gif',
      'to' => 'custom/themes/default/images/spinner.gif',
    ),
    33 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/txt.png',
      'to' => 'custom/themes/default/images/txt.png',
    ),
    34 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/xls.png',
      'to' => 'custom/themes/default/images/xls.png',
    ),
    35 => 
    array (
      'from' => '<basepath>/copy/custom/themes/default/images/zip.png',
      'to' => 'custom/themes/default/images/zip.png',
    ),
  ),
);

?>