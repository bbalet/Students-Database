<?php
require_once("component/selection/page/SelectionPage.inc");
class page_is_profile extends SelectionPage {
	
	public function getRequiredRights() { return array("see_information_session"); }
	
	public function executeSelectionPage(){
		$id = @$_GET["id"];
		$onsaved = @$_GET["onsaved"];
		if ($id <> null && $id <= 0) $id = null;
		$campaign_id = @$_GET["campaign"];
		if ($campaign_id == null) $campaign_id = PNApplication::$instance->selection->getCampaignId();
		$calendar_id = PNApplication::$instance->selection->getCampaignCalendar($campaign_id);
		if ($id <> null) {
			$q = SQLQuery::create()
				->selectSubModel("SelectionCampaign", $campaign_id)
				->select("InformationSession")
				->whereValue("InformationSession", "id", $id)
				;
			PNApplication::$instance->geography->joinGeographicArea($q, "InformationSession", "geographic_area");
			require_once("component/geography/GeographyJSON.inc");
			GeographyJSON::GeographicAreaTextSQL($q);
			$q->fieldsOfTable("InformationSession");
			$session = $q->executeSingleRow();
			if (@$session["date"] <> null) {
				require_once("component/calendar/CalendarJSON.inc");
				$event = CalendarJSON::getEventFromDB($session["date"], $calendar_id);
			}
		} else
			$session = null;
		if (@$_GET["readonly"] == "true") $editable = false;
		else $editable = $id == null || PNApplication::$instance->user_management->hasRight("manage_information_session");
		if ($campaign_id <> PNApplication::$instance->selection->getCampaignId()) $editable = false;
		$db_lock = null;
		if ($editable && $id <> null) {
			$locked_by = null;
			$db_lock = $this->performRequiredLocks("InformationSession",$id,null,$campaign_id, $locked_by);
			//if db_lock = null => read only
			if($db_lock == null){
				$editable = false;
				echo "<div class='info_header'>This Information Session is already open by ".$locked_by.": you cannot edit it</div>";
			}
		}
		$all_configs = include("component/selection/config.inc");
		$this->requireJavascript("is_date.js");
		$this->requireJavascript("who.js");
		$this->requireJavascript("is_statistics.js");
		?>
		<div style='width:100%;height:100%;overflow:auto;'>
		<div style='display:flex;flex-direction:row;'>
		<div style='flex:none;margin:10px;margin-right:5px;vertical-align:top;'>
			<?php if ($this->component->getOneConfigAttributeValue("give_name_to_IS",$campaign_id)) {
				$this->requireJavascript("center_name.js");
			?>
				<div id='center_name_container'></div>
				<script type='text/javascript'>
				window.center_name = new center_name(
					'center_name_container', 
					<?php echo $session <> null ? json_encode($session["name"]) : "null";?>,
					<?php echo json_encode($editable);?>,
					"Information Session name"
				); 
				</script>
			<?php } ?>
			<div id='is_schedule'></div>
			<script type='text/javascript'>
			window.is_schedule = new is_date(
				'is_schedule', 
				<?php if (@$session["date"] <> null) echo CalendarJSON::JSON($event); else echo "null";?>,
				<?php echo $session <> null ? $session["id"] : "-1";?>,
				<?php echo $this->component->getCalendarId();?>,
				<?php echo json_encode($this->component->getOneConfigAttributeValue("default_duration_IS",$campaign_id));?>,
				<?php echo json_encode($editable);?>,
				<?php echo json_encode($all_configs["default_duration_IS"][2]);?>
			); 
			</script>
			<div id='is_who'></div>
			<script type='text/javascript'>
			function addWho() {
				window.is_who = new who_section(
					'is_who',
					[<?php
					if (@$session["date"] <> null) {
						$people_ids = array();
						foreach ($event["attendees"] as $a) if ($a["people"] && !in_array($a["people"], $people_ids)) array_push($people_ids, $a["people"]);
						if (count($people_ids) > 0) {
							$peoples = PNApplication::$instance->people->getPeoples($people_ids, true, false, true, true);
							$can_do = SQLQuery::create()->selectSubModel("SelectionCampaign", $campaign_id)->select("StaffStatus")->whereIn("StaffStatus","people",$people_ids)->field("people")->field("is")->execute();
						} else {
							$peoples = array();
							$can_do = array();
						}
						$first = true;
						foreach ($event["attendees"] as $a) {
							if ($a["role"] == "NONE") continue;
							if ($first) $first = false; else echo ",";
							if ($a["people"] > 0) {
								echo "{people:";
								foreach ($peoples as $p) if ($p["people_id"] == $a["people"]) { echo PeopleJSON::People($p); break; }
								echo ",can_do:";
								$value = false;
								foreach ($can_do as $c) if ($c["people"] == $a["people"]) { $value = $c["is"] == 1; break; }
								echo json_encode($value);
								echo "}";
							} else
								echo json_encode($a["name"]);
						}
					} 
					?>],
					<?php echo json_encode($editable);?>,
					'is',
					"Who will conduct this Information Session ?"
				); 
			}
			function removeWho() {
				window.is_who = null;
				document.getElementById('is_who').removeAllChildren();
			}
			window.is_who = null;
			<?php if (@$session["date"] <> null) echo "addWho();";?>
			window.is_schedule.onchange.addListener(function() {
				var ev = window.is_schedule.getEvent();
				if (ev && ev.start) {
					if (!window.is_who) addWho();
				} else if (window.is_who)
					removeWho();
			});
			</script>
			<div id='is_stats'></div>
			<script type='text/javascript'>
			window.is_stats = new is_statistics(
				'is_stats', 
				<?php echo json_encode($this->component->getOneConfigAttributeValue("separate_boys_girls_IS",$campaign_id));?>,
				<?php echo json_encode($editable);?>,
				<?php echo json_encode(@$session["number_boys_expected"]);?>,
				<?php echo json_encode(@$session["number_boys_real"]);?>,
				<?php echo json_encode(@$session["number_girls_expected"]);?>,
				<?php echo json_encode(@$session["number_girls_real"]);?>
			); 
			</script>
		</div>
		<div style='flex:none;margin:10px;margin-left:0px;vertical-align:top;' id='location_and_partners'>
		<?php
		require_once("component/selection/page/common_centers/location_and_partners.inc");
		locationAndPartners($this, $id, $campaign_id, "InformationSession", $session <> null ? GeographyJSON::GeographicAreaText($session) : "null", $editable, true); 
		?>
		</div>
		</div>
		<div style='margin:0px 5px 5px 5px;' id='applicants_list_container'>
		</div>
		</div>
		<script type='text/javascript'>
		var is_popup = window.parent.getPopupFromFrame(window);
		var is_id = <?php echo $id <> null ? $id : -1;?>;

		function save_is() {
			if (window.center_location.geographic_area_text == null) {
				errorDialog("You must at set a location before saving");
				return;
			}
			is_popup.freeze("Saving...");
			// get date (calendar event)
			var event = window.is_schedule.getEvent();
			// prepare data of information session
			var data = {};
			data.id = is_id;
			data.geographic_area = window.center_location.geographic_area_text.id;
			// get from statistics
			var figures = window.is_stats.getFigures();
			data.number_boys_expected = figures.boys_expected;
			data.number_girls_expected = figures.girls_expected;
			data.number_boys_real = figures.boys_real;
			data.number_girls_real = figures.girls_real;
			// get from name
			if (window.center_name) {
				data.name = window.center_name.getName();
				if (data.name != null && !data.name.checkVisible()) data.name = null;
			} else
				data.name = null;
			// partners
			data.partners = [];
			for (var i = 0; i < window.center_location.partners.length; ++i) {
				var partner = window.center_location.partners[i];
				var p = {host:partner.host,host_address:partner.host_address_id,organization:partner.organization.id,contact_points_selected:partner.selected_contact_points_id};
				data.partners.push(p);
			}
			data.who = [];
			if (window.is_who)
				for (var i = 0; i < window.is_who.peoples.length; ++i)
					if (typeof window.is_who.peoples[i] == 'string')
						data.who.push(window.is_who.peoples[i]);
					else
						data.who.push(window.is_who.peoples[i].people.id);

			service.json("selection","is/save",{event:event, data:data},function(res){
				if(!res) {
					is_popup.unfreeze();
					errorDialog("An error occured, your informations were not saved");
				} else {
					window.top.status_manager.addStatus(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Information session successfuly saved!", [{action:"close"}], 5000));
					// Update the data on the page (some ids have been generated)
					if (is_id == -1) {
						// first save
						is_id = res.id;
						displayApplicantsList();
					}
					if (res.date) window.is_schedule.setEventId(res.date);
					window.pnapplication.cancelDataUnsaved(); // everything is saved
					is_popup.unfreeze();
					<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
				}
			});
		}

		function displayApplicantsList() {
			var frame = document.createElement("IFRAME");
			frame.style.display = "block";
			frame.style.width = "100%";
			frame.style.height = "300px";
			frame.className = "section soft";
			frame.name = "applicants_frame";
			document.getElementById('applicants_list_container').appendChild(frame);
			postFrame('/dynamic/selection/page/applicant/list?all=true&campaign=<?php echo $campaign_id;?>',{filters:[{category:'Selection',name:'Information Session',force:true,data:{values:[is_id]}}]}, 'applicants_frame');
		}
		
		is_popup.removeButtons();
		<?php if ($editable && $id <> null) {?>
		is_popup.addIconTextButton(theme.icons_16.remove, "Remove this session", "remove", function() {
			confirmDialog("Are you sure you want to remove this information session ?<br/>Note: Any applicant already assigned to this information session will remain in the system, but without information session.",function(res){
				if(res){
					is_popup.freeze();
					service.json("selection","is/remove",{id:<?php echo $id;?>},function(r){
						is_popup.unfreeze();
						if(r) {
							<?php if ($onsaved <> null) echo "window.frameElement.".$onsaved."();"?>
							is_popup.close();
						} else
							errorDialog("An error occured");
					});
				}
			});
		});
		<?php } ?>
		<?php if($id <> null){?>
		displayApplicantsList();
		<?php }?>
		<?php if ($editable || $id == null) {?>
		is_popup.addFrameSaveButton(save_is);
		<?php }?>
		is_popup.addCloseButton();
		</script>
		<?php 
	}
	
}
?>