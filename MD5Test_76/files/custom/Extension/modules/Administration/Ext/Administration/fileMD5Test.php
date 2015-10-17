<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kenbrill
 * Date: 6/13/12
 * Time: 3:01 PM
 * To change this template use File | Settings | File Templates.
 */
$admin_option_defs=array();
$admin_option_defs['Administration']['FILEMD5TEST']= array('Administration','LBL_FILEMD5TEST','LBL_FILEMD5TEST_DESC','./index.php?module=Administration&action=fileMD5Test');
$admin_group_header[]=array('ADMIN_GROUP_HEADER','',false,$admin_option_defs);
