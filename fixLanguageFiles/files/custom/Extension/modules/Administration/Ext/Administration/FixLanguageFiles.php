<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kenbrill
 * Date: 6/13/12
 * Time: 3:01 PM
 * To change this template use File | Settings | File Templates.
 */
$admin_option_defs=array();
$admin_option_defs['Administration']['FIXLANGUAGEFILES']= array('Administration','LBL_FIXLANGUAGEFILES','LBL_FIXFIXLANGUAGEFILES_DESC','./index.php?module=Administration&action=fixForecasts');
$admin_group_header[]=array('LBL_FIXLANGUAGEFILES','',false,$admin_option_defs);
