<?php 
class page_app_admin extends Page {
	
	public function getRequiredRights() { return array("manage_application"); }
	
	public function execute() {
		$this->requireJavascript("section.js");
		theme::css($this, "section.css");
		
		$sessions_path = ini_get("session.save_path");
		$i = strrpos($sessions_path, ";");
		if ($i !== false) $sessions_path = substr($sessions_path, $i+1);
		$sessions_path = realpath($sessions_path);
		$sessions = array();
		$dir = opendir($sessions_path);
		while (($filename = readdir($dir)) <> null) {
			if (is_dir($sessions_path."/".$filename)) continue;
			array_push($sessions, $filename);
		}
		closedir($dir);
?>
<div style='padding:10px'>
<div id='section_updates' icon='<?php echo theme::$icons_16["refresh"];?>' title='Software Update'>
	<div>
		<?php
		global $pn_app_version;
		echo "Current version: ".$pn_app_version."<br/>";
		echo "Latest version: <span id='latest_version'><img src='".theme::$icons_16["loading"]."'/></span><br/>";
		?>
	</div>
</div>
<div id='section_sessions' title='Open Sessions' collapsable='true' style='margin-top:10px'>
	<table>
		<tr><th>Session ID</th><th>Creation</th><th>Last modification</th><th>User</th></tr>
		<?php 
		$method = ini_get("session.serialize_handler");
		foreach ($sessions as $session) {
			$id = substr($session,5);
			echo "<tr>";
			echo "<td>".$id."</td>";
			$info = stat($sessions_path."/".$session);
			echo "<td>".date("Y-m-d h:i A", $info["ctime"])."</td>";
			echo "<td>".date("Y-m-d h:i A", $info["mtime"])."</td>";
			echo "<td>";
			if ($id == session_id()) {
				echo "<b>You</b>";
			} else {
				$content = file_get_contents($sessions_path."/".$session);
				if (strpos($content, "\"PNApplication\"") === false)
					echo "<i>Another application</i>";
				else {
					$data = self::decode_session($content);
					if ($data <> null) {
						echo @$data["app"]->user_management->username;
					}
				}
			}
			echo "</td>";
			echo "</tr>";
		}
		?>
	</table>		
</div>
<div id='section_maintenance' title='Maintenance' collapsable='true' style='margin-top:10px'>
	<div style='padding:10px'>
		You can put the software into <i>Maintenance Mode</i>.<br/>
		When in maintenance mode, all the users will be disconnected and won't be able to use the software until it will come back into <i>Normal Mode</i>.<br/>
		To put the software into Maintenance Mode, you need first to inform the users, so they can finish their work and save what they are currently doing,
		then the application won't be accessible. Only you, using a specific password, will be able to perform operations and put back the application in Normal Mode.<br/>
		<br/>
		<form name='maintenance' onsubmit='return false;'> 
		Inform the users, and put the software into Maintenance Mode in <input name='timing' type='text' size=3 value='5'/> minutes.<br/>
		I will use the username <i>maintenance</i> with the password <input name='pass1' type='password' size=15/>.<br/>
		Please re-type the maintenance password to confirm:  <input name='pass2' type='password' size=15/><br/>
		</form>
		<button class='action important' onclick="startMaintenance();">Start</button>
	</div>
</div>
</div>
<script type='text/javascript'>
section_updates = sectionFromHTML('section_updates');
section_sessions = sectionFromHTML('section_sessions');
section_maintenance = sectionFromHTML('section_maintenance');

service.json("administration","latest_version",null,function(res) {
	var span = document.getElementById('latest_version');
	if (res && res.version) {
		span.innerHTML = res.version;
		var current = "<?php echo $pn_app_version?>";
		current = current.split(".");
		var latest = res.version.split(".");
		var need_update = false;
		for (var i = 0; i < current.length; ++i) {
			if (latest.length <= i) break;
			var c = parseInt(current[i]);
			var l = parseInt(latest[i]);
			if (l > c) { need_update = true; break; }
			if (l < c) break;
		}
		if (need_update) {
			section_updates.content.appendChild(document.createTextNode("A newer version is available ! "));
			var button = document.createElement("BUTTON");
			button.className = "action important";
			button.innerHTML = "Update Software";
			section_updates.content.appendChild(button);
			button.onclick = function() {
				alert("TODO");
			};
		} else {
			var s = document.createElement("SPAN");
			s.innerHTML = "<img src='"+theme.icons_16.ok+"' style='vertical-align:bottom'/> The version is up to date !";
			section_updates.content.appendChild(s);
		}
	} else
		span.innerHTML = "<img src='"+theme.icons_16.error+"' style='vertical-align:bottom'/> Error";
});

section_sessions.addButton(null,"Remove all sessions except mine","action",function() {
	alert("TODO");
});

function startMaintenance() {
	var form = document.forms['maintenance'];
	var timing = form.elements['timing'].value;
	timing = parseInt(timing);
	if (isNaN(timing)) { alert("Please enter a valid number of minutes"); return; }
	if (timing < 2) { alert("You must give at least 2 mintues to the users before they will be disconnected..."); return; }
	if (timing > 15) { if (!confirm("Are you sure you want to wait "+timing+" minutes before maintenance mode ?")) return; }
	var pass1 = form.elements['pass1'].value;
	if (pass1.length == 0) { alert("Please enter a password"); return; }
	if (pass1.length < 6) { alert("Password is too short: you must use at least 6 characters."); return; }
	if (pass1 == "maintenance") { alert("You cannot use maintenance as password, this is too easy to guess..."); return; }
	var pass2 = form.elements['pass2'].value;
	if (pass1 != pass2) { alert("The 2 passwords are different. Please retry."); return; }
	var locker = lock_screen(null, "Starting maintenance mode...");
	service.json("administration","start_maintenance",{timing:timing,password:pass1},function(res) {
		unlock_screen(locker);
		if (res) {
			window.open("/maintenance");
		}
	});
}
</script>
	<?php 
	}

	private static function decode_session($session_string){
	    $current_session = session_encode();
	    foreach ($_SESSION as $key => $value){
	        unset($_SESSION[$key]);
	    }
	    session_decode($session_string);
	    $restored_session = $_SESSION;
	    foreach ($_SESSION as $key => $value){
	        unset($_SESSION[$key]);
	    }
	    session_decode($current_session);
	    return $restored_session;
	}
}
?>