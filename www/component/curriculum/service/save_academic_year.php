<?php 
class service_save_academic_year extends Service {
	
	public function getRequiredRights() { return array("edit_curriculum"); }
	
	public function documentation() { echo "Save information about an AcademicYear"; }
	public function inputDocumentation() {
		echo "<ul>";
		echo "<li><code>year</code> year of the AcademicYear</li>";
		echo "<li><code>name</code> name of the AcademicYear</li>";
		echo "<li><code>periods</code> list of AcademicPeriod:<ul>";
			echo "<li><code>name</code> name of the period</li>";
			echo "<li><code>start</code> start date of the period</li>";
			echo "<li><code>end</code> end date of the period</li>";
			echo "<li><code>weeks</code> total number of weeks of the period</li>";
			echo "<li><code>weeks_break</code> number of weeks that are not worked during the period</li>";
		echo "</ul></li>";
		echo "</ul>";
	}
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$id = @$input["id"];
		$fields = array(
			"year"=>$input["year"],
			"name"=>$input["name"]
		);
		if ($id <> null && $id > 0) {
			SQLQuery::create()->updateByKey("AcademicYear", $id, $fields);
			$existing_periods_ids = SQLQuery::create()->select("AcademicPeriod")->whereValue("AcademicPeriod","year",$id)->field("AcademicPeriod","id")->executeSingleField();
		} else {
			$id = SQLQuery::create()->insert("AcademicYear", $fields);
			$existing_periods_ids = array();
		}
		
		foreach ($input["periods"] as $period) {
			$fields = array(
				"year"=>$id,
				"name"=>$period["name"],
				"start"=>$period["start"],
				"end"=>$period["end"],
				"weeks"=>$period["weeks"],
				"weeks_break"=>$period["weeks_break"]
			);
			$pid = @$period["id"];
			if ($pid <> null && $pid > 0)
				SQLQuery::create()->updateByKey("AcademicPeriod", $pid, $fields);
			else
				SQLQuery::create()->insert("AcademicPeriod", $fields);
		}
		// remove periods not there anymore
		if (count($existing_periods_ids) > 0) {
			foreach ($input["periods"] as $period) {
				$pid = @$period["id"];
				if ($pid <> null && $pid > 0)
					for ($i = 0; $i < count($existing_periods_ids); $i++)
						if ($existing_periods_ids[$i] == $pid) {
							array_splice($existing_periods_ids, $i, 1);
							break;
						}
			}
			if (count($existing_periods_ids) > 0)
				SQLQuery::create()->removeKeys("AcademicPeriod", $existing_periods_ids);
		}
		
		if (PNApplication::hasErrors()) {
			SQLQuery::rollbackTransaction();
			echo "false";
		} else {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>