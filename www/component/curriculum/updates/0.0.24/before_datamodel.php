<?php 
$role_id = PNApplication::$instance->user_management->getRoleIdFromName("Teacher", true);
PNApplication::$instance->user_management->addRoleRights($role_id, array("consult_curriculum"=>true,"consult_students_list"=>true), true);
?>