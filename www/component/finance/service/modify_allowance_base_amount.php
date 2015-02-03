<?php 
class service_modify_allowance_base_amount extends Service {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function documentation() { echo "Modify the base amount for an allowance"; }
	public function inputDocumentation() { echo "allowance,change,batch"; }
	public function outputDocumentation() { echo "true on success"; }
	
	public function execute(&$component, $input) {
		SQLQuery::startTransaction();
		$students = PNApplication::$instance->students->getStudentsIdsForBatch($input["batch"]);
		$existing = SQLQuery::create()
			->select("StudentAllowance")
			->whereValue("StudentAllowance","allowance",$input["allowance"])
			->whereIn("StudentAllowance","student",$students)
			->whereNull("StudentAllowance","date")
			->execute();
		if (count($existing) > 0) {
			$keys = array();
			foreach ($existing as $e) array_push($keys, $e["id"]);
			$locked_by = null;
			$locks = DataBaseLock::lockRows("StudentAllowance", $keys, $locked_by);
			if ($locks == null) {
				PNApplication::error("Allowances are being edited by $locked_by and cannot be modified right now.");
				return;
			}
			for ($i = count($existing)-1; $i >= 0; $i--)
				SQLQuery::create()->updateByKey("StudentAllowance", $existing[$i]["id"], array("amount"=>$existing[$i]["amount"]+$input["change"]), $locks[$i]);
			DataBaseLock::unlockMultiple($locks);
		}
		if (!PNApplication::hasErrors()) {
			SQLQuery::commitTransaction();
			echo "true";
		}
	}
	
}
?>