<?php 
class page_edit_operation extends Page {
	
	public function getRequiredRights() { return array("edit_student_finance"); }
	
	public function execute() {
		$op = SQLQuery::create()->select("FinanceOperation")->whereValue("FinanceOperation","id",$_GET["id"])->executeSingleRow();
		if ($op["description"] == null) $op["description"] = "";
		$schedule = SQLQuery::create()->select("ScheduledPaymentDate")->whereValue("ScheduledPaymentDate","due_operation",$op["id"])->executeSingleRow();
		if ($schedule <> null) {
			if ($schedule["regular_payment"] <> null)
				$regular_payment = SQLQuery::create()->select("FinanceRegularPayment")->whereValue("FinanceRegularPayment","id",$schedule["regular_payment"])->executeSingleRow();
		}
		$min = null;
		$max = null;
		$payment_of = SQLQuery::create()
			->select("PaymentOperation")
			->whereValue("PaymentOperation","payment_operation",$op["id"])
			->join("PaymentOperation","FinanceOperation",array("due_operation"=>"id"))
			->executeSingleRow();
		if ($payment_of <> null) {
			$min = 0;
			$other_payments = SQLQuery::create()
				->select("PaymentOperation")
				->whereValue("PaymentOperation","due_operation",$payment_of["due_operation"])
				->whereNotValue("PaymentOperation","payment_operation",$op["id"])
				->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
				->execute();
			$other_amount = 0;
			foreach ($other_payments as $p) $other_amount += floatval($p["amount"]);
			$max = -floatval($payment_of["amount"])-$other_amount;
		} else {
			$payments = SQLQuery::create()
				->select("PaymentOperation")
				->whereValue("PaymentOperation","due_operation",$op["id"])
				->join("PaymentOperation","FinanceOperation",array("payment_operation"=>"id"))
				->execute();
			$paid = 0;
			foreach ($payments as $p) $paid += floatval($p["amount"]);
			$max = -$paid;
		}
?>
<div style='padding:5px;background-color:white'>
<form name='edit' onsubmit='return false;'>
<table>
	<tr>
		<td>Date</td>
		<?php 
		$id = $this->generateID();
		echo "<td id='$id'></td>";
		$this->requireJavascript("typed_field.js");
		$this->requireJavascript("field_date.js");
		$this->onload("window.op_date = new field_date(".json_encode($op["date"]).",".($schedule <> null ? "false" : "true").",{can_be_null:false});document.getElementById('$id').appendChild(window.op_date.getHTMLElement());initDate();");
		?>
	</tr>
	<tr>
		<td>Amount</td>
		<td><input name='amount' type='number' <?php if ($min !== null) echo "min='$min' "; if ($max !== null) echo "max='$max' ";?>original='<?php echo $op["amount"];?>' value='<?php echo $op["amount"];?>' onchange="if (parseFloat(this.value) == parseFloat(this.getAttribute('original'))) pnapplication.dataSaved('amount'); else pnapplication.dataUnsaved('amount');"/></td>
	</tr>
	<?php if ($schedule <> null || $payment_of <> null) {
		if ($schedule <> null) {
			$ts = datamodel\ColumnDate::toTimestamp($op["date"]);
			$i = strpos($op["description"], ",");
			$descr = $i === false ? $op["description"] : substr($op["description"], 0, $i);
		} else {
			$descr = $payment_of["description"];
			if ($descr === null) $descr = "";
		}
		if ($op["description"] == $descr)
			$add_descr = "";
		else if (substr($op["description"],0,strlen($descr)+2) == $descr.", ")
			$add_descr = substr($op["description"],strlen($descr)+2);
		else {
			$i = strpos($descr, ",");
			if ($i !== false && substr($op["description"],0,$i) == substr($descr,0,$i)) {
				$descr = substr($descr, 0, $i);
				$add_descr = substr($op["description"],strlen($descr)+2);
				if ($add_descr === false) $add_descr = "";
			} else
				$add_descr = $op["description"];
		}
	?>
	<tr>
		<td>Description</td>
		<td><input name='description' type='text' size=50 disabled='disabled' value=<?php echo toHTMLAttribute($descr);?>/></td>
	</tr>
	<tr>
		<td>Additional description</td>
		<td><input name='add_descr' type='text' size=50 original=<?php echo json_encode($add_descr);?> value=<?php echo toHTMLAttribute($add_descr);?> onchange='if (this.value == this.getAttribute("original")) pnapplication.dataSaved("description"); else pnapplication.dataUnsaved("description");'/></td>
	</tr>
	<?php } else { ?>
	<tr>
		<td>Description</td>
		<td><input name='description' type='text' size=50 original=<?php echo json_encode($op["description"]);?> value=<?php echo json_encode($op["description"]);?> onchange='if (this.value == this.getAttribute("original")) pnapplication.dataSaved("description"); else pnapplication.dataUnsaved("description");'/></td>
	</tr>
	<?php } ?>
</table>
</form>
</div>
<?php 
if ($payment_of <> null) {
	// This is a payment
	echo "<div class='info_footer'>";
	echo "<table><tr><td valign=top>";
	echo "<img src='".theme::$icons_16["info"]."'/>";
	echo "</td><td>";
	echo "This operation is a payment for ".toHTML($payment_of["description"])." (due on ".$payment_of["date"].").<br/>";
	if ($other_amount > 0) {
		$nb = count($other_payments);
		echo $nb." other payment".($nb > 1 ? "s exist" : " exists")." for the same operation with a total of $other_amount.<br/>";
	}
	echo "Maximum amount for this operation is $max";
	echo "</td></tr></table>";
	echo "</div>";
} else if (count($payments) > 0) {
	echo "<div class='info_footer'>";
	echo "<table><tr><td valign=top>";
	echo "<img src='".theme::$icons_16["info"]."'/>";
	echo "</td><td>";
	echo "This operation already started to be paid:<ul>";
	foreach ($payments as $p)
		echo "<li>".$p["amount"]." on ".$p["date"]."</li>";
	echo "</ul>";
	echo "Maximum amount for this operation is $max";
	echo "</td></tr></table>";
	echo "</div>";
}
?>
<script type='text/javascript'>
function initDate() {
	window.op_date.ondatachanged.addListener(function() { pnapplication.dataUnsaved('date'); });
	window.op_date.ondataunchanged.addListener(function() { pnapplication.dataSaved('date'); });
}

var popup = window.parent.getPopupFromFrame(window);
popup.removeButtons();
popup.addFrameSaveButton(function() {
	popup.freeze("Saving...");
	var form = document.forms['edit'];
	var descr = form.elements['description'].value;
	if (typeof form.elements['add_descr'] != 'undefined' && form.elements['add_descr'].value.trim().length > 0)
		descr += ", "+form.elements['add_descr'].value.trim();
	service.json("finance","save_operation",{id:<?php echo $op["id"];?>,date:window.op_date.getCurrentData(),amount:form.elements['amount'].value,description:descr},function(res) {
		popup.unfreeze();
		if (!res) return;
		<?php if (isset($_GET["onsave"])) echo "window.frameElement.".$_GET["onsave"]."();"?>
		window.op_date.setOriginalData(window.op_date.getCurrentData());
		form.elements['amount'].setAttribute("original", form.elements['amount'].value);
		form.elements['description'].setAttribute("original", form.elements['description'].value);
		if (typeof form.elements['add_descr'] != 'undefined')
			form.elements['add_descr'].setAttribute("original", form.elements['add_descr'].value);
		pnapplication.cancelDataUnsaved();
	});
});
popup.addCloseButton();
</script>
<?php 
	}
	
}
?>