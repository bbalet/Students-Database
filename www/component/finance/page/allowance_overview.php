<?php 
class page_allowance_overview extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		require_once 'component/students_groups/page/TreeFrameSelection.inc';
		$allowance_id = $_GET["id"];
		$allowance = SQLQuery::create()->select("Allowance")->whereValue("Allowance","id",$allowance_id)->executeSingleRow();
		if (@$_GET["edit"] == "true") {
			$can_manage = PNApplication::$instance->user_management->hasRight("manage_finance");
			$can_edit = PNApplication::$instance->user_management->hasRight("edit_student_finance");
		} else {
			$can_edit = false;
			$can_manage = false;
			$may_edit = PNApplication::$instance->user_management->hasRight("manage_finance") || PNApplication::$instance->user_management->hasRight("edit_student_finance");
		}

		$locked_by = null;
		if ($can_edit) {
			require_once 'component/data_model/DataBaseLock.inc';
			$lock_id = DataBaseLock::lockTable("StudentAllowance", $locked_by);
			if ($lock_id <> null) DataBaseLock::generateScript($lock_id);
			else {
				$can_edit = $can_manage = false;
				$may_edit = true;
			}
		}
		
		if ($can_edit) {
			$this->requireJavascript("typed_field.js");
			$this->requireJavascript("field_decimal.js");
			$this->requireJavascript("popup_window.js");
		}
		
		theme::css($this, "grid.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none'>
		<?php
		if (TreeFrameSelection::isSingleBatch() && $can_manage)
			echo "<button class='action red' style='float:right' onclick='removeForBatch(".TreeFrameSelection::getBatchId().");'><img src='".theme::$icons_16["remove_white"]."'/> Remove</button>";
		if (!$can_manage && !$can_edit && $may_edit)
			echo "<button class='action' style='float:right' onclick=\"var u = new window.URL(location.href);u.params['edit'] = true; location.href = u.toString();\"><img src='".theme::$icons_16["edit"]."'/> Edit</button>";
		else if ($can_manage || $can_edit)
			echo "<button class='action' style='float:right' onclick=\"var u = new window.URL(location.href);delete u.params.edit; location.href = u.toString();\"><img src='".theme::$icons_16["no_edit"]."'/> Stop Editing</button>";
		?>
		<img src='/static/finance/finance_32.png'/>
		<?php echo toHTML($allowance["name"])?>
		<div style='display:inline-block;font-size:12pt;margin-left:10px;font-style:italic'>
			<?php
			$batch_id = TreeFrameSelection::getBatchId();
			if ($batch_id <> null)
				echo "Batch ".toHTML(TreeFrameSelection::getBatchName());
			else if (TreeFrameSelection::isAllBatches()) echo "All batches";
			else if (TreeFrameSelection::isCurrentBatches()) echo "Current batches";
			else if (TreeFrameSelection::isAlumniBatches()) echo "Alumni batches";
			?>
		</div>
	</div>
	<?php 
	if ($locked_by <> null) {
		echo "<div class='info_header'>You cannot edit because ".toHTML($locked_by)." is currently editing allowances.</div>";
	}
	?>
	<div style='flex: 1 1 100%;overflow:auto;background-color:white'>
		<?php 
		$batches_ids = TreeFrameSelection::getBatchesIds();
		$batches = PNApplication::$instance->curriculum->getBatches($batches_ids);
		foreach ($batches as $batch) {
			$group_id = TreeFrameSelection::getGroupId();
			$period_id = TreeFrameSelection::getPeriodId();
			if ($group_id <> null) {
				$q = PNApplication::$instance->students_groups->getStudentsQueryForGroup($group_id);
				PNApplication::$instance->people->joinPeople($q, "StudentGroup", "people", false); 
			} else if ($period_id <> null) {
				$spe_id = TreeFrameSelection::getSpecializationId();
				if ($spe_id == null) $spe_id = false;
				$students_ids = PNApplication::$instance->students_groups->getStudentsForPeriod($period_id, $spe_id);
				$q = PNApplication::$instance->people->getPeoplesSQLQuery($students_ids,false,true);
			} else {
				$q = PNApplication::$instance->students->getStudentsQueryForBatches(array($batch["id"]));
				PNApplication::$instance->people->joinPeople($q, "Student", "people", false);
			}
			$q->orderBy("People","last_name");
			$q->orderBy("People","first_name");
			$list = $q->execute();
			if (!TreeFrameSelection::isSingleBatch()) {
				echo "<div class='page_section_title'>";
				echo "Batch ".toHTML($batch["name"]);
				if (count($schedules) > 0 && $can_manage)
					echo "<button class='action red' style='float:right' onclick='removeForBatch(".$batch["id"].");'><img src='".theme::$icons_16["remove_white"]."'/> Remove</button>";
				echo "</div>";
				echo "<div style='border-bottom:1px solid black;margin-bottom:5px;'>";
			}
			if (count($list) == 0) {
				echo "<i>No student in this batch</i>";
				if (!TreeFrameSelection::isSingleBatch()) echo "</div>";
				continue;
			}
			$students = array();
			foreach ($list as $student) $students[$student["people_id"]] = $student;
			$base_amount = SQLQuery::create()
				->select("StudentAllowance")
				->whereValue("StudentAllowance","allowance",$allowance_id)
				->whereIn("StudentAllowance","student",array_keys($students))
				->whereNull("StudentAllowance","date")
				->execute()
				;
			$students_allowances_ids = array();
			$students_deductions = array();
			foreach ($base_amount as $a) {
				$students[$a["student"]]["base_amount"] = $a;
				$students_deductions[$a["id"]] = array();
				array_push($students_allowances_ids, $a["id"]);
			}
			$list = SQLQuery::create()
				->select("StudentAllowanceDeduction")
				->whereIn("StudentAllowanceDeduction","student_allowance",$students_allowances_ids)
				->execute();
			$deductions = array();
			foreach ($list as $d) {
				if (!in_array($d["name"], $deductions)) array_push($deductions, $d["name"]);
				array_push($students_deductions[$d["student_allowance"]], $d);
			}
			if ($can_edit) {
				echo "<div class='page_section_title shadow'>";
				$can_skip = count($base_amount) > 0 && count($base_amount) < count($students);
				echo "<button class='action' onclick='setBaseAmountForBatch(".$batch["id"].",".json_encode($can_skip).");'>Set Base Amount</button>";
				if (count($base_amount) > 0)
					echo "<button class='action' onclick='modifyBaseAmount(".$batch["id"].");'>Modify Base Amount</button>";
				echo "</div>";
			}
			$cols_deductions = count($deductions);
			if ($cols_deductions == 0) $cols_deductions = 1;
			echo "<table class='grid selected_hover'><thead>";
			echo "<tr>";
				echo "<th rowspan=2>Student</th>";
				echo "<th rowspan=2>Base amount</th>";
				echo "<th colspan=$cols_deductions class='first_in_container last_in_container'>Deductions";
				if ($can_edit) echo " <button class='flat small_icon' title='Add a new deduction for all students' onclick='addGlobalDeduction(".$batch["id"].");'><img src='".theme::$icons_10["add"]."'/></button>";
				echo "</th>";
				echo "<th rowspan=2>Total</th>";
			echo "</tr>";
			echo "<tr>";
				if (count($deductions) == 0) echo "<th></th>";
				else foreach ($deductions as $name) {
					echo "<th>";
					echo toHTML($name);
					if ($can_edit) {
						echo "<button class='flat small_icon' title='Edit this deduction' onclick='editDeduction(".$batch["id"].",".toHTMLAttribute($name).");'><img src='".theme::$icons_10["edit"]."'/></button>";
						echo "<button class='flat small_icon' title='Remove this deduction for all students' onclick='removeDeduction(".toHTMLAttribute($name).");'><img src='".theme::$icons_10["remove"]."'/></button>";
					}
					echo "</th>";
				}
			echo "</tr>";
			echo "</thead><tbody>";
			foreach ($students as $student) {
				echo "<tr student_name=".toHTMLAttribute($student["last_name"]." ".$student["first_name"]).">";
				echo "<td>".toHTML($student["last_name"]." ".$student["first_name"])."</td>";
				if (!isset($student["base_amount"])) {
					echo "<td colspan=".(2+$cols_deductions)." style='font-style:italic;".($can_edit ? "cursor:pointer;' title=".toHTMLAttribute("Click to set an allowance for ".$student["last_name"]." ".$student["first_name"])." onclick='setBaseAmountForStudent(".$student["people_id"].",undefined,this.parentNode);'" : "'").">No allowance</td>";
				} else {
					echo "<td style='text-align:right;".($can_edit ? "cursor:pointer' title=".toHTMLAttribute("Click to edit the base amount for ".$student["last_name"]." ".$student["first_name"])." onclick='setBaseAmountForStudent(".$student["people_id"].",".$student["base_amount"]["amount"].",this.parentNode);'" : "'").">".$student["base_amount"]["amount"]."</td>";
					$total = $student["base_amount"]["amount"];
					if (count($deductions) == 0) echo "<td></td>";
					else for ($i = 0; $i < count($deductions); $i++) {
						$cl = "";
						if ($i == 0) $cl = "first_in_container";
						if ($i == count($deductions)-1) $cl .= ($cl <> "" ? " " : "")."last_in_container";
						$deduc = null;
						foreach ($students_deductions[$student["base_amount"]["id"]] as $d)
							if ($d["name"] == $deductions[$i]) { $deduc = $d; break; }
						echo "<td class='$cl' style='text-align:right;".($can_edit ? "cursor:pointer;' title=".toHTMLAttribute("Click to edit deduction ".$deductions[$i]." for ".$student["last_name"]." ".$student["first_name"])." onclick='editStudentDeduction(".$student["people_id"].",".($deduc <> null ? $deduc["id"] : "null").");'" : "'").">";
						if ($deduc <> null) {
							echo $deduc["amount"];
							$total -= $deduc["amount"];
						}
						echo "</td>";
					}
					echo "<td style='text-align:right'>";
					echo number_format($total,2);
					echo "</td>";
				}
				echo "</tr>";
			}
			echo "</tbody></table>";
			if (!TreeFrameSelection::isSingleBatch()) echo "</div>";
		}
		?>
	</div>
</div>
<div style='display:none;padding:10px;' id='popup_set_base_amount_for_batch'>
	Enter the base amount for all students: <span id='base_amount_for_batch'></span>
	<div style='display:none' id='skip_no_allowance_container'>
		<input type='checkbox' id='skip_no_allowance'/>
		Except for students having no allowance
	</div>
</div>
<div style='display:none;' id='popup_modify_base_amount'>
	<div style='padding:10px;'>
		Change the amount by <span id='modify_base_amount'></span><br/>
	</div>
	<div class='info_footer'>
		<div style='display:inline-block;vertical-align:top'>
			<img src='<?php echo theme::$icons_32["info"];?>'/>
		</div>
		<div style='display:inline-block;vertical-align:top'>
			A positive value will increase the amount for every student<br/>
			A negative value will decrease it
		</div>
	</div>
</div>
<div style='display:none;padding:10px;' id='popup_set_base_amount_for_student'>
	Enter the base amount: <span id='base_amount_for_student'></span>
</div>
<div style='display:none;padding:10px;' id='popup_add_global_deduction'>
	<table>
		<tr>
			<td>Name of the deduction</td>
			<td><input type='text' size=30 maxlength=30 id='global_deduction_name'/></td>
		</tr>
		<tr>
			<td>Amount to deduct</td>
			<td id='global_deduction_amount'></td>
		</tr>
	</table>
</div>
<script type='text/javascript'>
var batches = <?php echo json_encode($batches);?>;

function removeForBatch(batch_id) {
	var batch = null;
	for (var i = 0; i < batches.length; ++i) if (batches[i].id == batch_id) { batch = batches[i]; break; }
	confirmDialog("Are you sure you want to remove <?php echo $allowance["name"];?> for Batch "+batch.name+" ?",function(yes) {
		if (!yes) return;
		service.json("finance","remove_students_allowance",{batch:batch_id,allowance:<?php echo $allowance_id;?>},function(res) {
			if (!res) popup.unfreeze();
			else location.reload();
		});
	});
}

<?php if ($can_edit) { ?>
var base_amount_for_batch = new field_decimal(1,true,{can_be_null:false,min:1,integer_digits:10,decimal_digits:2});
document.getElementById('base_amount_for_batch').appendChild(base_amount_for_batch.getHTMLElement());
function setBaseAmountForBatch(batch_id, can_skip) {
	var popup = new popup_window("Set Base Amount For Batch",null,document.getElementById('popup_set_base_amount_for_batch'));
	popup.keep_content_on_close = true;
	base_amount_for_batch.setData(1);
	var skip_no_allowance = document.getElementById('skip_no_allowance');
	skip_no_allowance.checked = '';
	document.getElementById('skip_no_allowance_container').style.display = can_skip ? "" : "none";
	popup.addOkCancelButtons(function() {
		if (base_amount_for_batch.hasError()) { alert("Please enter a valid amount"); return; }
		popup.freeze("Setting base amount...");
		service.json("finance","set_allowance_base_amount",{batch:batch_id,amount:base_amount_for_batch.getCurrentData(),allowance:<?php echo $allowance_id;?>,skip_no_allowance:skip_no_allowance.checked},function(res) {
			if (!res)
				popup.unfreeze();
			else
				location.reload();
		});
	});
	popup.show();
}

var modify_base_amount = new field_decimal(0,true,{can_be_null:false,integer_digits:10,decimal_digits:2});
document.getElementById('modify_base_amount').appendChild(modify_base_amount.getHTMLElement());
function modifyBaseAmount(batch_id) {
	var popup = new popup_window("Modify Base Amount For Batch",null,document.getElementById('popup_modify_base_amount'));
	popup.keep_content_on_close = true;
	modify_base_amount.setData(0);
	popup.addOkCancelButtons(function() {
		if (modify_base_amount.hasError()) { alert("Please enter a valid amount"); return; }
		if (modify_base_amount.getCurrentData() == 0) return;
		popup.freeze("Setting base amount...");
		service.json("finance","modify_allowance_base_amount",{batch:batch_id,change:modify_base_amount.getCurrentData(),allowance:<?php echo $allowance_id;?>},function(res) {
			if (!res)
				popup.unfreeze();
			else
				location.reload();
		});
	});
	popup.show();
}

var base_amount_for_student = new field_decimal(1,true,{can_be_null:false,min:1,integer_digits:10,decimal_digits:2});
document.getElementById('base_amount_for_student').appendChild(base_amount_for_student.getHTMLElement());
function setBaseAmountForStudent(student_id, current_amount, tr) {
	var student_name = tr.getAttribute("student_name");
	var popup = new popup_window("Set Base Amount For Student "+student_name,null,document.getElementById('popup_set_base_amount_for_student'));
	popup.keep_content_on_close = true;
	base_amount_for_student.setData(current_amount ? current_amount : 1);
	if (current_amount)
		popup.addIconTextButton(theme.icons_16.remove,"Remove allowance for this student",'remove',function() {
			confirmDialog("Are you sure you want to remove this allowance for "+student_name+" ?<br/>This will remove any other information entered about this allowance.",function(yes) {
				if (!yes) return;
				popup.freeze("Removing allowance...");
				service.json("finance","remove_students_allowance",{student:student_id,allowance:<?php echo $allowance_id;?>},function(res) {
					if (!res) popup.unfreeze();
					else location.reload();
				});
			});
		});
	popup.addOkCancelButtons(function() {
		if (base_amount_for_student.hasError()) { alert("Please enter a valid amount"); return; }
		popup.freeze("Setting base amount...");
		service.json("finance","set_allowance_base_amount",{student:student_id,amount:base_amount_for_student.getCurrentData(),allowance:<?php echo $allowance_id;?>},function(res) {
			if (!res)
				popup.unfreeze();
			else
				location.reload();
		});
	});
	popup.show();
}

var global_deduction_amount = new field_decimal(0,true,{can_be_null:false,min:0,integer_digits:10,decimal_digits:2});
document.getElementById('global_deduction_amount').appendChild(global_deduction_amount.getHTMLElement());
function addGlobalDeduction(batch_id) {
	var popup = new popup_window("New Deduction",null,document.getElementById('popup_add_global_deduction'));
	popup.keep_content_on_close = true;
	global_deduction_amount.setData(0);
	var input_name = document.getElementById('global_deduction_name');
	input_name.value = "";
	popup.addOkCancelButtons(function() {
		var name = input_name.value.trim();
		if (name.length == 0) { alert("Please enter a name describing the deduction"); return; }
		if (global_deduction_amount.hasError() || global_deduction_amount.getCurrentData() == 0) { alert("Please enter a valid amount"); return; }
		popup.freeze("Creating new deduction...");
		service.json("finance","new_allowance_deduction",{batch:batch_id,name:name,amount:global_deduction_amount.getCurrentData(),allowance:<?php echo $allowance_id;?>},function(res) {
			if (!res)
				popup.unfreeze();
			else
				location.reload();
		});
	});
	popup.show();
}

function editDeduction(batch_id, deduction_name) {
	var popup = new popup_window("Modify "+deduction_name,null,document.getElementById('popup_modify_base_amount'));
	popup.keep_content_on_close = true;
	modify_base_amount.setData(0);
	popup.addOkCancelButtons(function() {
		if (modify_base_amount.hasError()) { alert("Please enter a valid amount"); return; }
		if (modify_base_amount.getCurrentData() == 0) return;
		popup.freeze("Setting new amount...");
		service.json("finance","modify_allowance_deduction",{batch:batch_id,change:modify_base_amount.getCurrentData(),allowance:<?php echo $allowance_id;?>,deduction_name:deduction_name},function(res) {
			if (!res)
				popup.unfreeze();
			else
				location.reload();
		});
	});
	popup.show();
}

function removeDeduction(deduction_name) {
	// TODO
}

function editStudentDeduction(student_id, deduc_id) {
	// TODO
}
<?php } ?>
</script>
<?php 
	}
	
}
?>