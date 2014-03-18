<?php
class service_picture extends Service {
	public function get_required_rights() { return array(); }
	public function documentation() { echo "Retrieve the profile picture of a people"; }
	public function input_documentation() { echo "<code>people</code>: id of the people to get the picture"; }
	public function output_documentation() { echo "The picture"; }
	public function get_output_format($input) { return "image/jpeg"; }
	public function execute(&$component, $input) {
		$people_id = $_GET["people"];
		if (!$component->canRead($people_id)) {
			header("Location: ".theme::$icons_16["error"]);
			return;
		}
		$people = SQLQuery::create()->select("People")->whereValue("People", "id", $people_id)->field("picture")->field("sex")->executeSingleRow();
		if ($people["picture"] <> null) {
			header("Location: /dynamic/storage/service/get?id=".$people["picture"]);
			return;
		}
		readfile(dirname(__FILE__)."/../static/default_".($people["sex"] == "F" ? "female" : "male").".jpg");
	}
}
?>