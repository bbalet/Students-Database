<?php
class page_logout extends Page {
	public function get_required_rights() { return array(); }
	public function execute() {
		PNApplication::$instance->user_management->logout();
?>
<script type='text/javascript'>
if (window.top.pnapplication) {
	window.top.pnapplication.logged_in = false;
	window.top.pnapplication.onlogout.fire();
}
if (window.top.pn_loading_start) {
	window.top.pn_loading_start();
	window.top.set_loading_message('Loading authentication page...');
	window.location.href = "<?php echo "/dynamic/application/page/enter?".(isset($_GET["from"]) ? "&from=".$_GET["from"] : "").(isset($_GET["testing"]) ? "&testing=".$_GET["testing"] : "");?>";
} else {
	window.location.href = "/?<?php (isset($_GET["from"]) ? "&from=".$_GET["from"] : "").(isset($_GET["testing"]) ? "&testing=".$_GET["testing"] : "")?>";
}
</script>
<?php 
	}
} 
?>