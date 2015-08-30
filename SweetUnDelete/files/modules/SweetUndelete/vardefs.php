<?php

/*
 * Your installation or use of this SugarCRM file is subject to the applicable
 * terms available at
 * http://support.sugarcrm.com/06_Customer_Center/10_Master_Subscription_Agreements/.
 * If you do not agree to all of the applicable terms or do not have the
 * authority to bind the entity as an authorized representative, then do not
 * install or use this SugarCRM file.
 *
 * Copyright (C) SugarCRM Inc. All rights reserved.
 */

$dictionary['SweetUndelete'] = array(
    'table' => 'SweetUndelete',
    'audited' => false,
    'activity_enabled' => false,
    'duplicate_merge' => false,
    'fields' => array(
        'module' =>
            array(
                'required' => false,
                'name' => 'module',
                'vname' => 'LBL_RECORD_MODULE',
                'type' => 'varchar',
                'massupdate' => false,
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array(
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'len' => '255',
                'size' => '20',
            ),
        'user_id_c' =>
            array(
                'required' => false,
                'name' => 'user_id_c',
                'vname' => 'LBL_DELETED_BY_USER_ID',
                'type' => 'id',
                'massupdate' => false,
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => 1,
                'audited' => false,
                'reportable' => false,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'calculated' => false,
                'len' => 36,
                'size' => '20',
            ),
        'deleted_by' =>
            array(
                'required' => false,
                'source' => 'non-db',
                'name' => 'deleted_by',
                'vname' => 'LBL_DELETED_BY',
                'type' => 'relate',
                'massupdate' => false,
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => true,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array(
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'len' => '255',
                'size' => '20',
                'id_name' => 'user_id_c',
                'ext2' => 'Users',
                'module' => 'Users',
                'rname' => 'name',
                'quicksearch' => 'enabled',
                'studio' => 'visible',
            ),

        'relationship_data' =>
            array(
                'required' => false,
                'name' => 'relationship_data',
                'vname' => 'LBL_RELATIONSHIP_DATA',
                'type' => 'longtext',
                'comment' => 'Full text of the note',
                'rows' => 6,
                'cols' => 80,
                'massupdate' => false,
                'no_default' => false,
                'comments' => '',
                'help' => '',
                'importable' => 'true',
                'duplicate_merge' => 'enabled',
                'duplicate_merge_dom_value' => '1',
                'audited' => false,
                'reportable' => false,
                'unified_search' => false,
                'merge_filter' => 'disabled',
                'full_text_search' =>
                    array(
                        'boost' => '0',
                        'enabled' => false,
                    ),
                'calculated' => false,
                'len' => '255',
                'size' => '20',
            ),
    ),
    'relationships' => array(),
    'optimistic_locking' => true,
    'unified_search' => false,
    'favorites' => false,
);

if (!class_exists('VardefManager')) {
    require_once 'include/SugarObjects/VardefManager.php';
}
VardefManager::createVardef('SweetUndelete', 'SweetUndelete', array('basic', 'team_security', 'assignable'));