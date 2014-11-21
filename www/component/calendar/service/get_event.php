<?php class service_get_event extends Service {		public function getRequiredRights() {		return array();	}		public function documentation() { echo "Return event details of the given event id"; } 	public function inputDocumentation() {		echo "<ul>";			echo "<li><code>id</code>: the id of the event</li>";			echo "<li><code>calendar_id</code>: the id of the calendar</li>";		echo "</ul>"; 	}	public function outputDocumentation() { echo "Returns a CalendarEvent object"; }			public function execute(&$component, $input) {		require_once 'component/calendar/CalendarJSON.inc';		$calendar_id = $input["calendar_id"];		$id = $input["id"];		$res = CalendarJSON::CalendarEventFromID($id, $calendar_id, true);		if($res == false)			echo "false";		else{			echo $res;		}	}		}