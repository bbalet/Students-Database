<?php
class service_save_user_rights extends Service {
	
	public function documentation() {
		?>Save the list of rights associated with the given user.<?php
	}
	public function get_required_rights() {
		return array("edit_user_rights");
	}
	public function input_documentation() {
	?>
	<ul>
		<li><code>domain</code>: domain of the user</li>
		<li><code>username</code>: username of the user</li>
		<li><code>lock</code>: lock id</li>
		<li>list of rights to save: <code><i>right_name=right_value</i></code>
	</ul>
	<?php 
	}
	public function output_documentation() {
	?>return true on success.<?php 
	}
	public function execute(&$component) {
// check domain and username
if (!isset($_POST["domain"])) { PNApplication::error("domain missing"); return; }
if (!isset($_POST["username"])) { PNApplication::error("username missing"); return; }

$domain = $_POST["domain"];
$username = $_POST["username"];

// check data were locked before
if (!isset($_GET["lock"]))  { PNApplication::error("missing lock"); return; }
require_once("component/data_model/DataBaseLock.inc");
if (!DataBaseLock::check_lock($_GET["lock"], "UserRights", null, null)) {
	PNApplication::error("You do not have the data locked, meaning you cannot modify them. This may be due to a long inactivity. Please refresh the page and try again");
	return;
}

$r = SQLQuery::create()->select("Users")->field("username")->where("domain",$domain)->where("username",$username);
if ($r == null || count($r) == 0) {
	PNApplication::error("unknown user");
	return;
}

// retrieve all possible rights
$all_rights = array();
foreach (PNApplication::$instance->components as $c) {
	foreach ($c->get_readable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
	foreach ($c->get_writable_rights() as $cat) foreach ($cat->rights as $r) $all_rights[$r->name] = $r;
}

$rights = array();
foreach ($_POST as $name=>$value) {
	if ($name == "domain" || $name == "username") continue;
	if (!isset($all_rights[$name])) {
		PNApplication::error("unknown right ".$name);
		return;
	}
	$rights[$name] = $all_rights[$name]->parse_value($value);
}

// save in database: (1) remove all previous rights, (2) add all rights from the request
SQLQuery::get_db_system_without_security()->execute("DELETE FROM UserRights WHERE domain='".$domain."' AND username='".$username."'");
if (count($rights) > 0) {
	$sql = "INSERT INTO UserRights (domain,username,`right`,`value`) VALUES ";
	$first = true;
	foreach ($rights as $name=>$value) {
		if ($first) $first = false; else $sql .= ",";
		$sql .= "('".$domain."','".$username."','".SQLQuery::escape($name)."','".SQLQuery::escape($value)."')";
	}
	SQLQuery::get_db_system_without_security()->execute($sql);
}
echo "true";
	}		
}
