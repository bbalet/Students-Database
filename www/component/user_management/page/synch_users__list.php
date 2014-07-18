<?php 
class page_synch_users__list extends Page {
	
	public function getRequiredRights() { return array("manage_users"); }
	
	public function execute() {
		$domain = $_GET["domain"];
		$token = $_GET["token"];
		echo "<div style='background-color:white;padding:10px'>";
		$as = PNApplication::$instance->authentication->getAuthenticationSystem($domain);
		$list = $as->getUserList($token);
		if ($list === null) {
			echo "The authentication system of ".$domain." does not support synchronization";
		} else {
			$current = SQLQuery::create()->select("Users")->whereValue("Users","domain",$domain)->execute();
			$current_internal = SQLQuery::create()->bypassSecurity()->select("InternalUser")->field("username")->executeSingleField();
			// match between current users, and list from authentication system
			for ($i = 0; $i < count($list); $i++) {
				$username = $list[$i]["username"];
				for ($j = 0; $j < count($current); $j++) {
					$un = $current[$j]["username"];
					if ($un == $username) {
						// match found
						array_splice($current, $j, 1);
						array_splice($list, $i, 1);
						$i--;
						break;
					}
				}
			}
			// remove the internal users
			for ($i = 0; $i < count($current); $i++) {
				for ($j = 0; $j < count($current_internal); ++$j) {
					if ($current_internal[$j] == $current[$i]["username"]) {
						array_splice($current_internal, $j, 1);
						array_splice($current, $i, 1);
						$i--;
						break;
					}
				}
			}
			if (count($current) > 0) {
				echo "<form name='removed_users' onsubmit='return false;'>";
				echo "The following users do not exist anymore in ".$domain.":<ul>";
				foreach ($current as $user) {
					echo "<li>";
					echo $user["username"];
					echo "<br/>";
					echo "<input type='radio' value='keep' name='".$user["username"]."' selected='selected'/> Keep it<br/>";
					echo "<input type='radio' value='remove' name='".$user["username"]."' selected='selected'/> Remove it (information about it will be kept, be this user won't be able to login anymore)<br/>";
					echo "</li>";
				}
				echo "</ul>";
				echo "</form>";
			}
			// check if internal users became present in authentication system
			$internal_to_as = array();
			for ($i = 0; $i < count($list); $i++) {
				if (in_array($list[$i]["username"], $current_internal)) {
					array_push($internal_to_as, $list[$i]);
					array_splice($list, $i, 1);
					$i--;
				}
			}
			if (count($internal_to_as) > 0) {
				echo "<form name='internal_to_as' onsubmit='return false;'>";
				echo "The following users are currently internal to the software, but they are now present in the authentication system:<ul>";
				foreach ($internal_to_as as $user) {
					echo "<li>";
					echo htmlentities($user["username"]);
					echo "<br/>";
					echo "<input type='radio' value='keep_internal' name='".$user["username"]."'/> Keep it internal<br/>";
					echo "<input type='radio' value='move_to_as' name='".$user["username"]."'/> Use the authentication system now, and remove it from internal users<br/>";
					echo "</li>";
				}
				echo "</ul>";
				echo "</form>";
			}
			if (count($list) > 0) {
				echo "<form name='new_users' onsubmit='return false'>";
				echo "The following users exist in ".$domain." but not yet in the software:<ul>";
				foreach ($list as $user) {
					echo "<li>";
					echo htmlentities($user["username"]);
					if (isset($user["info"])) {
						if (isset($user["info"]["People"])) {
							if (isset($user["info"]["People"]["first_name"]) && isset($user["info"]["People"]["last_name"])) {
								$fullname = $user["info"]["People"]["first_name"];
								if (isset($user["info"]["People"]["middle_name"]))
									$fullname .= " ".$user["info"]["People"]["middle_name"];
								$fullname .= $user["info"]["People"]["last_name"];
								echo " (".$fullname.")";
							}
						}
					}
					if (isset($user["groups"]) && count($user["groups"]) > 0) {
						echo " member of ";
						for ($i = 0; $i < count($user["groups"]); $i++) {
							if ($i > 0) echo ",";
							echo htmlentities($user["groups"][$i]);
						}
					}
					echo "<br/>";
					echo "<input type='radio' name='".$user["username"]."' value='create_user'/> Create the user without any information<br/>";
					if (isset($user["info"])) {
						if (isset($user["info"]["People"])) {
							if (isset($user["info"]["People"]["first_name"]) && isset($user["info"]["People"]["last_name"])) {
								$q = PNApplication::$instance->people->searchPeopleByFirstAndLastName($user["info"]["People"]["first_name"], $user["info"]["People"]["last_name"]);
								PNApplication::$instance->user_people->joinUserToPeople($q);
								$q->whereNull("Users", "username");
								$match = $q->execute();
								foreach ($match as $row) {
									echo "<input type='radio' name='".$user["username"]."' value='link_".$row["id"]."'/> Create user and link with existing people: ".$row["first_name"]." ".$row["last_name"]."<br/>";
								}
							}
						}
					}
					echo "<input type='radio' name='".$user["username"]."' value='create_people'/> Create user with the following information:<br/>";
					echo "<div style='margin-left:20px'>First name: <input type='text' name='".$user["username"]."_first_name' maxlength=100 value='".(isset($user["info"]) && isset($user["info"]["People"]) && isset($user["info"]["People"]["first_name"]) ? $user["info"]["People"]["first_name"] : "")."'/></div>";
					echo "<div style='margin-left:20px'>Last name: <input type='text' name='".$user["username"]."_last_name' maxlength=100 value='".(isset($user["info"]) && isset($user["info"]["People"]) && isset($user["info"]["People"]["last_name"]) ? $user["info"]["People"]["last_name"] : "")."'/></div>";
					echo "<div style='margin-left:20px'>Gender: <select name='".$user["username"]."_gender'>";
					echo "<option value=''></option>";
					echo "<option value='M'".(isset($user["info"]) && isset($user["info"]["People"]) && isset($user["info"]["People"]["gender"]) && $user["info"]["People"]["gender"] == "M" ? " selected='selected'" : "").">M</option>";
					echo "<option value='F'".(isset($user["info"]) && isset($user["info"]["People"]) && isset($user["info"]["People"]["gender"]) && $user["info"]["People"]["gender"] == "F" ? " selected='selected'" : "").">F</option>";
					echo "</select></div>";
					echo "</li>";
				}
				echo "</ul>";
				echo "</form>";
			}
			if (count($list) == 0 && count($current) == 0) {
				echo "All users match between ".$domain." system and this software.";
			}
		}
		echo "</div>";
		?>
<script type='text/javascript'>
var popup = window.parent.get_popup_window_from_frame(window);

function process_removed_users(ondone) {
	if (typeof document.forms["removed_users"] == 'undefined') { ondone(); return; }
	var form = document.forms["removed_users"];
	var users = [];
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		if (!e.checked) continue;
		if (e.value == "keep") continue;
		users.push(e.name);
	}
	if (users.length == 0) { ondone(); return; }
	var next = function() {
		if (users.length == 0) { ondone(); return; }
		var username = users[0];
		popup.set_freeze_content("Removing user "+username);
		users.splice(0,1);
		service.json("user_management","remove_user",{domain:<?php echo json_encode($domain);?>,token:<?php echo json_encode($token);?>,username:username},function(res) {
			next();
		});
	};
	next();
}
function process_internal_to_as(ondone) {
	if (typeof document.forms["internal_to_as"] == 'undefined') { ondone(); return; }
	var form = document.forms["internal_to_as"];
	var users = [];
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		if (!e.checked) continue;
		if (e.value == "keep_internal") continue;
		users.push(e.name);
	}
	if (users.length == 0) { ondone(); return; }
	var next = function() {
		if (users.length == 0) { ondone(); return; }
		var username = users[0];
		popup.set_freeze_content("Moving user "+username+" from internal to authentication system of <?php echo $domain;?>");
		users.splice(0,1);
		service.json("user_management","internal_to_authentication_system",{domain:<?php echo json_encode($domain);?>,token:<?php echo json_encode($token);?>,username:username},function(res) {
			next();
		});
	};
	next();
}
function process_new_users(ondone) {
	if (typeof document.forms["new_users"] == 'undefined') { ondone(); return; }
	var form = document.forms["new_users"];
	var to_create = [];
	var to_link = [];
	for (var i = 0; i < form.elements.length; ++i) {
		var e = form.elements[i];
		if (!e.checked) continue;
		if (e.value == "create_user") {
			to_create.push({username:e.name});
		} else if (e.value.substr(0,5) == "link_") {
			to_link.push({username:e.name,people_id:e.value.substr(5)});
		} else if (e.value == 'create_people') {
			// TODO
			alert("Create user "+e.name+" and people "+e.getAttribute("first_name")+" "+e.getAttribute("last_name"));
		}
	}
	// TODO
}

popup.addOkButton(function() {
	popup.freeze();
	process_removed_users(function() {
		process_internal_to_as(function() {
			process_new_users(function() {
				popup.close();
			});
		});
	});
});
popup.addCancelButton();
</script>
		<?php 
	}
	
}
?>