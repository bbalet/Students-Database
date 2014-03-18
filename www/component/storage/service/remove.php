<?php 
class service_remove extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() {}
	public function input_documentation() {}
	public function output_documentation() {}
	
	public function execute(&$component, $input) {
		$id = $input["id"];
		$file = SQLQuery::create()->bypassSecurity()->select("Storage")->whereValue("Storage", "id", $id)->executeSingleRow();
		if ($file == null) {
			PNApplication::error("Invalid storage id");
			return;
		}
		if ($file["username"] <> PNApplication::$instance->user_management->username || $file["domain"] <> PNApplication::$instance->user_management->domain) {
			PNApplication::error("Access denied.");
			return;
		}
		$component->remove_data($id);
		echo "true";
	}
	
}
?>