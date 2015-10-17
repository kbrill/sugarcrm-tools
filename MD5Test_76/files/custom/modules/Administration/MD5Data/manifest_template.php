<?PHP
$manifest = array(
    'acceptable_sugar_versions' => array (
        'regex_matches' => array (
            0 => "7\.6\.*.*",
        ),
    ),
    'acceptable_sugar_flavors' => array (
        0 => 'OS',
        1 => 'PRO',
        2 => 'ENT',
        3 => 'CE',
        4 => 'ULT',
    ),
    'name' 			    => 'SugarCRM: File Updater',
    'description' 	    => 'A tool to replace and/or add files to a instance',
    'author' 		    => 'Ken Brill',
    'published_date'    => '10/15/2015',
    'version' 		    => '7.6',
    'type' 			    => 'module',
    'is_uninstallable' 	=> true,
);

$installdefs = array(
    'id'=> 'File_Updater',
    'copy' => array(
