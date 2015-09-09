<?php
/**
 * Created by JetBrains PhpStorm.
 * User: kenbrill
 * Date: 6/13/12
 * Time: 3:01 PM
 * To change this template use File | Settings | File Templates.
 */
$admin_option_defs=array();
$admin_option_defs['Administration']['FIXTEAMS']= array('Administration','LBL_FIXTEAMS','LBL_FIXTEAMS_DESC','./index.php?module=Administration&action=fixTeams');
$admin_group_header[]=array('LBL_FIXTEAMS','',false,$admin_option_defs);
