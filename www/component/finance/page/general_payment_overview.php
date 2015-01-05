<?php 
class page_general_payment_overview extends Page {
	
	public function getRequiredRights() { return array("consult_student_finance"); }
	
	public function execute() {
		require_once 'component/students_groups/page/TreeFrameSelection.inc';
		$payment_id = $_GET["id"];
		$payment = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment","id",$payment_id)->executeSingleRow();
		$can_manage = PNApplication::$instance->user_management->hasRight("manage_finance");
		
		theme::css($this, "grid.css");
?>
<div style='width:100%;height:100%;display:flex;flex-direction:column;'>
	<div class='page_title' style='flex:none'>
		<?php
		if (TreeFrameSelection::isSingleBatch() && $can_manage)
			echo "<button class='action red' style='float:right' onclick='removeForBatch(".TreeFrameSelection::getBatchId().");'><img src='".theme::$icons_16["remove_white"]."'/> Remove</button>"; 
		?>
		<img src='/static/finance/finance_32.png'/>
		<?php echo toHTML($payment["name"])?>
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
				$q = PNApplication::$instance->students_groups->getStudentsForPeriod($period_id, $spe_id);
				PNApplication::$instance->people->joinPeople($q, "StudentGroup", "people", false);
			} else {
				$q = PNApplication::$instance->students->getStudentsQueryForBatches(array($batch["id"]));
				PNApplication::$instance->people->joinPeople($q, "Student", "people", false);
			}
			$q->orderBy("People","last_name");
			$q->orderBy("People","first_name");
			$students = $q->execute();
			if (count($students) > 0) {
				$students_ids = array();
				foreach ($students as $s) array_push($students_ids, $s["people_id"]);
				$schedules = SQLQuery::create()
					->select("ScheduledPaymentDate")
					->whereValue("ScheduledPaymentDate","regular_payment",$payment_id)
					->join("ScheduledPaymentDate","FinanceOperation",array("due_operation"=>"id"), "due")
					->whereIn("due","people",$students_ids)
					->orderBy("due","people")
					->orderBy("due","date")
					->execute();
			} else
				$schedules = array();
			if (!TreeFrameSelection::isSingleBatch()) {
				echo "<div class='page_section_title'>";
				echo "Batch ".toHTML($batch["name"]);
				if (count($schedules) > 0 && $can_manage)
					echo "<button class='action red' style='float:right' onclick='removeForBatch(".$batch["id"].");'><img src='".theme::$icons_16["remove_white"]."'/> Remove</button>";
				echo "</div>";
			}
			if (count($students) == 0) {
				echo "<i>No student in this batch</i>";
				continue;
			}
			if (count($schedules) == 0) {
				echo "<i>This payment is not configured for this batch</i><br/>";
				if ($can_manage) {
					echo "<button class='action' onclick='configurePaymentForBatch(".$batch["id"].")'>Configure it</button>";
				}
				continue;
			}
			$due_operations = array();
			foreach ($schedules as $s) array_push($due_operations, $s["due_operation"]);
			$list = SQLQuery::create()
				->select("PaymentOperation")
				->whereIn("PaymentOperation","due_operation", $due_operations)
				->orderBy("PaymentOperation","due_operation")
				->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
				->execute();
			$payments_done = array();
			$op_id = null;
			foreach ($list as $done) {
				if ($done["due_operation"] <> $op_id) {
					$op_id = $done["due_operation"];
					$payments_done[$op_id] = array($done);
				} else
					array_push($payments_done[$op_id], $done);
			}
			$start = null;
			$end = null;
			$student = null;
			foreach ($schedules as $schedule) {
				if ($student == null || $student["people_id"] <> $schedule["people"]) {
					unset($student);
					for ($i = 0; $i < count($students); $i++)
						if ($students[$i]["people_id"] == $schedule["people"]) {
							$student = &$students[$i];
							$student["schedules"] = array();
							break;
						}
				}
				array_push($student["schedules"], $schedule);
				$date = datamodel\ColumnDate::toTimestamp($schedule["date"]);
				if ($start == null || $start > $date) $start = $date;
				if ($end == null || $end < $date) $end = $date;
			}
			$dates = array();
			echo "<table class='grid selected_hover'>";
			echo "<thead>";
			echo "<tr>";
				echo "<th>Student</th>";
				$d = $start;
				while ($d <= $end) {
					$date = getdate($d);
					$sql_date = datamodel\ColumnDate::toSQLDate($date);
					array_push($dates, $sql_date);
					echo "<th date='$sql_date'>";
					switch ($payment["frequency"]) {
						case "Daily":
							echo date("d M Y", $d);
							$d += 24*60*60;
							break;
						case "Weekly":
							echo date("d M Y", $d);
							$d += 7*24*60*60;
							break;
						case "Monthly":
							echo date("M Y", $d);
							$dd = getdate($d);
							$d = mktime(0,0,0,$dd["mon"]+1,1,$dd["year"]);
							break;
						case "Yearly":
							$dd = getdate($d);
							echo $dd["year"];
							$d = mktime(0,0,0,$dd["mon"],1,$dd["year"]+1);
							break;
					}
					echo "</th>";
				}
			echo "</tr>";
			echo "</thead><tbody>";
			foreach ($students as $s) {
				echo "<tr student_id='".$s["people_id"]."'>";
				echo "<td style='white-space:nowrap;'>".toHTML($s["last_name"]." ".$s["first_name"])."</td>";
				foreach ($dates as $d) {
					$schedule = null;
					foreach ($s["schedules"] as $sched)
						if ($sched["date"] == $d) { $schedule = $sched; break; }
					$ts = datamodel\ColumnDate::toTimestamp($d);
					if ($schedule <> null) {
						$payments = @$payments_done[$schedule["due_operation"]];
						if ($payments == null) $payments = array();
						$paid = 0;
						foreach ($payments as $p) $paid += floatval($p["amount"]);
						$balance = $paid+floatval($schedule["amount"]);
						if ($balance == 0)
							echo "<td op='".$schedule["due_operation"]."' style='padding:1px;cursor:pointer;text-align:center;' onmouseover='mouseOverCell(this);' onmouseout='mouseOutCell(this);' onclick='cellClicked(this);'><div style='background-color:".($ts < time() ? "#60FF60" : "#A0FFA0").";'>Paid</div></td>";
						else if ($balance == $schedule["amount"])
							echo "<td op='".$schedule["due_operation"]."' style='padding:1px;cursor:pointer;text-align:center;' onmouseover='mouseOverCell(this);' onmouseout='mouseOutCell(this);' onclick='cellClicked(this);'><div style='background-color:".($ts < time() ? "#FF0000" : "#FF8080").";'>$balance</div></td>";
						else if ($balance < 0)
							echo "<td op='".$schedule["due_operation"]."' style='padding:1px;cursor:pointer;text-align:center;' onmouseover='mouseOverCell(this);' onmouseout='mouseOutCell(this);' onclick='cellClicked(this);'><div style='background-color:".($ts < time() ? "#FF9040" : "#FFB080").";'>$balance</div></td>";
						else
							echo "<td op='".$schedule["due_operation"]."' style='padding:1px;cursor:pointer;text-align:center;' onmouseover='mouseOverCell(this);' onmouseout='mouseOutCell(this);' onclick='cellClicked(this);'><div style='background-color:#0080FF;'>$balance</td>";
					} else {
						echo "<td style='padding:1px;cursor:pointer;text-align:center;' onmouseover='mouseOverCell(this);' onmouseout='mouseOutCell(this);' onclick='cellClicked(this);'><div style='background-color:#A0A0A0;'>N/A</div></td>";
					}
				}
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";
		}
		?>
	</div>
</div>
<script type='text/javascript'>
var batches = <?php echo json_encode($batches);?>;
var freq = "<?php echo $payment["frequency"];?>";

var selected_td = null;
var selected_th = null;

function removeSelectedCell() {
	if (selected_td == null) return;
	selected_td.style.color = "";
	selected_th.style.backgroundColor = "";
	selected_td = null;
	selected_th = null;
}
function mouseOverCell(td) {
	removeSelectedCell();
	var col_index = 0;
	var e = td;
	while (e.previousSibling) { col_index++; e = e.previousSibling; }
	var tr = td.parentNode;
	var tbody = tr.parentNode;
	var table = tbody.parentNode;
	var thead = table.childNodes[0];
	tr = thead.childNodes[0];
	var th = tr.childNodes[col_index];
	th.style.backgroundColor = "#FFC080";
	td.style.color = "#FFE0C0";
	selected_td = td;
	selected_th = th;
}
function mouseOutCell(td) {
	removeSelectedCell();
}
function cellClicked(td) {
	if (td.hasAttribute("op")) {
		var op_id = td.getAttribute("op");
		window.top.popupFrame("/static/finance/finance_16.png","Finance","/dynamic/finance/page/operation?id="+op_id+"&onchange=paid",null,null,null,function(frame,popup) {
			frame.paid = function() {
				location.reload();
			};
		});
	} else {
		var tr = td.parentNode;
		var student_id = tr.getAttribute("student_id");
		var col_index = 0;
		var e = td;
		while (e.previousSibling) { col_index++; e = e.previousSibling; }
		var tbody = tr.parentNode;
		var table = tbody.parentNode;
		var thead = table.childNodes[0];
		tr = thead.childNodes[0];
		var th = tr.childNodes[col_index];
		var date = th.getAttribute("date");
		inputDialog("/static/finance/finance_16.png","Create "+<?php echo json_encode($payment["name"]);?>+" on "+date,"Amount","",30,function(text) {
			var amount = parseFloat(text.trim());
			if (amount <= 0 || isNaN(amount)) return "Invalid amount";
			return null;
		},function(text) {
			var amount = parseFloat(text.trim());
			service.json("finance","new_scheduled_payment",{student:student_id,regular_payment:<?php echo $payment_id;?>,date:date,amount:amount},function(res) {
				if (res) location.reload();
			});
		});
	}
}

function configurePaymentForBatch(batch_id) {
	require(["popup_window.js","start_end_dates.js",["typed_field.js","field_decimal.js"]],function() {
		var content = document.createElement("DIV");
		content.style.padding = "5px";
		var batch = null;
		for (var i = 0; i < batches.length; ++i) if (batches[i].id == batch_id) { batch = batches[i]; break; }
		var title = document.createElement("DIV");
		title.className = "page_section_title3";
		title.appendChild(document.createTextNode("Create <?php echo $payment["name"];?> for Batch "+batch.name));
		content.appendChild(title);
		var table = document.createElement("TABLE");
		content.appendChild(table);
		var tr;
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Payment starts on";
		tr.appendChild(td = document.createElement("TD"));
		var start;
		switch (freq) {
		case "Daily": start = new SelectDay(td, parseSQLDate(batch.start_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		case "Weekly": start = new SelectDay(td, parseSQLDate(batch.start_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		case "Monthly": start = new SelectMonth(td, parseSQLDate(batch.start_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		case "Yearly": start = new SelectYear(td, parseSQLDate(batch.start_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		}
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = "Last payment on";
		tr.appendChild(td = document.createElement("TD"));
		var end;
		switch (freq) {
		case "Daily": end = new SelectDay(td, parseSQLDate(batch.end_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		case "Weekly": end = new SelectDay(td, parseSQLDate(batch.end_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		case "Monthly": end = new SelectMonth(td, parseSQLDate(batch.end_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		case "Yearly": end = new SelectYear(td, parseSQLDate(batch.end_date), parseSQLDate(batch.start_date),parseSQLDate(batch.end_date)); break;
		}
		new StartEndDates(start, end);
		table.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.innerHTML = freq+" amount";
		tr.appendChild(td = document.createElement("TD"));
		var amount = new field_decimal(null,true,{integer_digits:10,decimal_digits:2,can_be_null:false,min:0});
		td.appendChild(amount.getHTMLElement());
		var popup = new popup_window("Configure payment",null,content);
		popup.addOkCancelButtons(function() {
			popup.freeze("Creating payments...");
			service.json("finance","create_batch_regular_payment",{batch:batch_id,payment:<?php echo $payment_id;?>,start:dateToSQL(start.getDate()),end:dateToSQL(end.getDate()),amount:amount.getCurrentData()},function(res) {
				if(!res) {
					popup.unfreeze();
					return;
				}
				popup.close();
				location.reload();
			});
		});
		popup.show();
	});
}

function removeForBatch(batch_id) {
	var batch = null;
	for (var i = 0; i < batches.length; ++i) if (batches[i].id == batch_id) { batch = batches[i]; break; }
	confirmDialog("Are you sure you want to remove <?php echo $payment["name"];?> for Batch "+batch.name+" ?<br/><b>All payments already registered will be removed</b>",function(yes) {
		if (!yes) return;
		var locker = lockScreen(null,"Removing <?php echo $payment["name"];?> for Batch "+batch.name);
		service.json("finance","remove_batch_regular_payment",{payment:<?php echo $payment_id?>,batch:batch_id},function(res) {
			if (!res) unlockScreen(locker);
			else location.reload();
		});
	});
}
</script>
<?php 
	}
	
}
?>