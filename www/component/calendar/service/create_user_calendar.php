<?php 
class service_create_user_calendar extends Service {
	
	public function get_required_rights() { return array(); }
	
	public function documentation() { echo "Create a calendar for the current user"; }
	public function input_documentation() { 
		echo "<code>name</code>: name of the calendar<br/>";
		echo "<code>color</code>: color of the calendar, or null<br/>";
		echo "<code>icon</code>: icon of the calendar, or null<br/>";
	}
	public function output_documentation() {
		echo "<code>id</code>: the new calendar id";
	}
	
	public function execute(&$component, $input) {
		$id = $component->createUserCalendar(PNApplication::$instance->user_management->user_id, $input["name"], @$input["color"], @$input["icon"]);
		echo "{id:".$id."}";
	}
	
}
?>