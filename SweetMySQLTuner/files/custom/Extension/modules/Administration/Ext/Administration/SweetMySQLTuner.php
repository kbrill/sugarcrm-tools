<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kenbrill
 * Date: 6/13/12
 * Time: 3:01 PM
 * To change this template use File | Settings | File Templates.
 */
$admin_option_defs=array();
$admin_option_defs['Administration']['SWEETMYSQLTUNER']= array('Administration','LBL_SWEETMYSQLTUNER','LBL_SWEETMYSQLTUNER_DESC','./index.php?module=Administration&action=SweetMySQLTuners');
$admin_group_header[]=array('LBL_SWEETMYSQLTUNER','',false,$admin_option_defs);
