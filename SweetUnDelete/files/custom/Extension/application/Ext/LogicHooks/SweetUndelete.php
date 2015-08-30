<?php
$hook_version = 1;

if(!isset($hook_array) || !is_array($hook_array)) {
    $hook_array = array();
}
if(!array_key_exists('before_delete', $hook_array)) {
    $hook_array['before_delete'] = array();
}
$hook_array['before_delete'][] = array(
    1,
    'before we delete a record what is being deleted',
    'custom/modules/SweetUndelete/SweetUndelete.php',
    'SweetUndelete',
    'before_delete'
);

if(!array_key_exists('before_relationship_delete', $hook_array)) {
    $hook_array['before_relationship_delete'] = array();
}
$hook_array['before_relationship_delete'][] = array(
    2,
    'before we delete a relationship what is being deleted',
    'custom/modules/SweetUndelete/SweetUndelete.php',
    'SweetUndelete',
    'before_relationship_delete'
);