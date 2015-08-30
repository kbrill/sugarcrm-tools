<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kenbrill
 * Date: 6/13/12
 * Time: 3:01 PM
 * To change this template use File | Settings | File Templates.
 */
$admin_option_defs=array();
$admin_option_defs['Administration']['SWEETDBADMiN']= array('Administration','LBL_SWEETDBADMIN','LBL_SWEETDBADMIN_DESC','./index.php?module=Administration&action=SweetDBAdmin&skip=0&sql=&command=query');
$admin_group_header[]=array('LBL_SWEETDBADMIN','',false,$admin_option_defs);
