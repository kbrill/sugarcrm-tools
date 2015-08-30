<?php
//todo: add teams to table
//todo: filter for created_by, team_id, assigned_user_id
if (!defined('sugarEntry') || !sugarEntry) die('Not A Valid Entry Point');

class SweetUndelete
{
    //Here we are adding this little WHERE clause to any query the listview
    //  generates so only parent records show up on the ListView
//    public function before_filter($bean, $event, $arguments)
//    {
//        $where = "(NULLIF(parent_record, '') IS NULL) ";
//        $arguments[0]->whereRaw($where);
//    }

    public function before_delete($bean, $event, $arguments)
    {
        if($this->parent_module != 'SweetUndelete') {
            $id=$arguments['id'];
            $GLOBALS['DELETED_RECORD'][$id]=array();
            $GLOBALS['DELETED_RECORD'][$id]['user_id_c'] = $GLOBALS['current_user']->id;
            $GLOBALS['DELETED_RECORD'][$id]['module'] = $bean->object_name;

            //collect up info on the object being deleted
            $fieldDefs = $bean->field_defs;
            foreach($fieldDefs as $fieldName=>$fieldDef) {
                if(!array_key_exists($fieldDef,'source') || $fieldDef['source']!='non-db') {
                    if(isset($bean->$fieldName)) {
                        $GLOBALS['DELETED_RECORD'][$id]['BEAN'][$fieldName]=$bean->$fieldName;
                    }
                }
            }
        }
    }

    public function before_relationship_delete($bean, $event, $arguments)
    {
        if($this->parent_module != 'SweetUndelete') {
            if ($bean->load_relationship($arguments['link'])) {
                $record_id = $arguments['related_id'];
                $parent_id = $arguments['id'];
                if (isset($bean->$arguments['link']->relationship->def['join_table'])) {
                    $table = $bean->$arguments['link']->relationship->def['join_table'];

                    //If the record is not already recorded in the database then add it
                    if(!isset($GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id])) {
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['type'] = 'm2m';
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['table'] = $table;
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['rhs_key'] =
                            $bean->$arguments['link']->relationship->def['join_key_rhs'];
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['lhs_key'] =
                            $bean->$arguments['link']->relationship->def['join_key_lhs'];

                    }

                } else {
                    if ($bean->$arguments['link']->relationship->def['relationship_type'] == 'one-to-many' &&
                        $bean->$arguments['link']->relationship->def['rhs_module'] != $this->parent_module
                    ) {
                        $table = $bean->$arguments['link']->relationship->def['rhs_table'];
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['type'] = 'o2m';
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['table'] = $table;
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['rhs_key'] =
                            $bean->$arguments['link']->relationship->def['join_key_rhs'];
                        $GLOBALS['DELETED_RECORD'][$parent_id]['RELATIONSHIPS'][$record_id]['lhs_key'] = '';
                    }
                }
            }
        }
    }

    private function checkRecordExist($id)
    {
        $sweetUndelete = BeanFactory::getBean('SweetUndelete', $id);
        if (empty($sweetUndelete->id)) {
            return false;
        } else {
            return true;
        }
    }

    public function updateSweetUndelete()
    {
        $records = $GLOBALS['DELETED_RECORD'];
        foreach($records as $id=>$data) {

            //If the record is not already recorded in the database then add it
            if (!$this->checkRecordExist($id)) {
                $sweetUndelete = BeanFactory::getBean('SweetUndelete');
                $sweetUndelete->name = $data['name'];
                $sweetUndelete->record = $id;
                $sweetUndelete->module = $data['module'];
                $sweetUndelete->user_id_c = $data['user_id_c'];
                $sweetUndelete->assigned_user_id = $data['assigned_user_id'];
                $sweetUndelete->created_by = $data['created_by'];
                $sweetUndelete->modified_user_id = $data['modified_user_id'];
                $sweetUndelete->team_id = $data['team_id'];
                $sweetUndelete->team_set_id = $data['team_set_id'];



                $sweetUndelete->new_with_id = true;
                $sweetUndelete->id = $id;
                $sweetUndelete->save();
            }

        }
    }
}