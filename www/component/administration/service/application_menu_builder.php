<?php 
class service_application_menu_builder extends Service {
	
	public function getRequiredRights() { return array(); }
	
	public function documentation() { echo "Provides JavaScript to build the application menu"; }
	public function inputDocumentation() { echo "No"; }
	public function outputDocumentation() { echo "The JavaScript that builds the menu"; }
	
	public function getOutputFormat($input) { return "text/javascript"; }
	
	public function execute(&$component, $input) {
		echo "resetAllMenus();";
		require_once("component/administration/AdministrationPlugin.inc");
		foreach (PNApplication::$instance->components as $name=>$c) {
			foreach ($c->getPluginImplementations() as $pi) {
				if (!($pi instanceof AdministrationPlugin)) continue;
				foreach ($pi->getAdministrationPages() as $page) {
					echo "addMenuItem(".json_encode($page->getIcon16()).", ".json_encode($page->getTitle()).", ".json_encode($page->getInfoText()).", ".json_encode($page->getPage()).");";
				}
			}
		}
	}
	
}
?>