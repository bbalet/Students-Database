<?php 
require_once("component/application/ApplicationSectionPlugin.inc");
class TrainingApplicationSection implements ApplicationSectionPlugin {
	
	public function getId() { return "training"; }
	public function getName() { return "Training"; }
	public function getDescription() { return "Consult the curriculum, students list by batch and class, grades..."; }
	public function getPriority() { return 50; }
	public function getIcon16White() { return "/static/training/training_white.png"; }
	public function getIcon32() { return "/static/curriculum/curriculum_32.png"; }
	public function getComponentsForURL() { return array("training","transcripts","curriculum"); }
	public function getMenuURL() { return "/dynamic/training/service/menu"; }
	public function getDefaultPageURL() { return "/dynamic/curriculum/page/tree_frame#/dynamic/students/page/list"; }
	public function canAccess() { return PNApplication::$instance->user_management->has_right("consult_students_list"); }
	
}
?>