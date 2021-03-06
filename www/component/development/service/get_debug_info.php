<?php 
class service_get_debug_info extends Service {
	
	public function getRequiredRights() { return array(); }
	public function documentation() {}
	public function inputDocumentation() {}
	public function outputDocumentation() {}
	
	public function execute(&$component, $input) {
		$locks = SQLQuery::getDataBaseAccessWithoutSecurity()->execute("SELECT * FROM datalocks WHERE locker_domain='".PNApplication::$instance->user_management->domain."' AND locker_username='".PNApplication::$instance->user_management->username."'");
		echo "{";
		echo "requests:".json_encode($component->requests);
		echo ",locks:[";
		$first = true;
		while (($lock = SQLQuery::getDataBaseAccessWithoutSecurity()->nextRow($locks)) <> false) {
			if ($first) $first = false; else echo ",";
			echo "{";
			echo "id:".$lock["id"];
			echo ",table:".json_encode($lock["table"]);
			echo ",column:".json_encode($lock["column"]);
			echo ",row_key:".json_encode($lock["row_key"]);
			echo "}";
		}
		echo "]";
		echo ",cfg:{";
		echo "xdebug_profiling:".json_encode(isset($_COOKIE["XDEBUG_PROFILE"]));
		echo "}";
		echo "}";
	}
	
}
?>