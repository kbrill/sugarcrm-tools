<?php
if(!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');
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

require_once('include/MVC/View/views/view.list.php');

class SweetUndeleteViewList extends ViewList
{
    /**
     * @see SugarView::preDisplay()
     */
    public function preDisplay()
    {
        parent::preDisplay();
        $this->lv = new ListViewSmarty();
        $this->lv->export = false;
        $this->lv->delete = false;
        $this->lv->email = false;
        $this->lv->showMassUpdateFields = false;
        $this->lv->mergeduplicates = false;
        $this->lv->quickViewLinks = false;
        $this->lv->contextMenus = false;
    }

    function SweetUndeleteViewList()
    {
        parent::ViewList();
        die('TEST !');
    }

    public function listViewProcess()
    {
        die('TEST 2');
        if(!empty($this->where)){
            $this->where .= " AND ";
        }
        $this->where .= "(NULLIF(parent_record, '') IS NULL) ";
        $this->lv->setup($this->seed, 'include/ListView/ListViewGeneric.tpl', $this->where, $this->params);
        echo $this->lv->display();
    }
}