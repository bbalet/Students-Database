<?php require_once("/../selection_page.inc");class page_config_manage extends selection_page {	public function getRequiredRights() { return array("manage_selection_campaign"); }	public function execute_selection_page(){		/** Start by locking the row in the database
		 * In case the data is already locked, generate an error message
		 * The row will be unlocked when leaving from the page
		 */
		require_once("component/data_model/DataBaseLock.inc");
		$campaign_id = PNApplication::$instance->components["selection"]->getCampaignId();
		require_once("component/data_model/Model.inc");
		$table = DataModel::get()->getTable("SelectionCampaignConfig");
		$lock_id = $this->performRequiredLocks("SelectionCampaignConfig",null,null,PNApplication::$instance->selection->getCampaignId());
		
		$rights = array();
		$rights['manage'] = PNApplication::$instance->user_management->has_right("manage_selection_campaign",true);
		/* Get all the possible configs */
		$all_configs = include("component/selection/config.inc");
		/* Get the current config */
		$config = PNApplication::$instance->selection->getConfig();
						$this->addJavascript("/static/widgets/vertical_layout.js");		$this->onload("new vertical_layout('config_page_container');");		$this->requireJavascript("typed_field.js");		$this->requireJavascript("field_date.js");		$this->requireJavascript("popup_window.js");		?>		<div id="config_page_container" style="height:100%" class="page_container">			<div id="config_header" class="page_title">				<img src="<?php echo theme::$icons_32["config"];?>"/>				Selection Process Configuration			</div>			<div id='manage_config' style="overflow:auto;margin-left:20px;" layout="fill"></div>			<div id="config_footer" class="page_footer">				<button class='action' onclick="window.manage_config._save();">					<img src="<?php echo theme::$icons_16["save"];?>"/>					Save				</button>				<button class='action important' onclick="getIFrameWindow(findFrame('pn_application_frame')).renameCampaign();">					<img src="<?php echo theme::$icons_16["edit"];?>"/>					Rename Campaign				</button>				<button class='action important' onclick="getIFrameWindow(findFrame('pn_application_frame')).removeCampaign();">					<img src="<?php echo theme::$icons_16["remove"];?>"/>					Remove Campaign				</button>			</div>		</div>		<script type='text/javascript'>		function ManageConfig() {			var t=this;			t.categories = getConfigCategoryIndexes();			t.dependencies = {};			t.date_fields = []; //contains all the existing fields_date			var campaign_id = <?php echo $campaign_id.";"; ?>			t._lock_id = <?php echo $lock_id.";";?>			var rights = {};			rights.manage = <?php echo json_encode($rights['manage']).";";?>					var container = document.getElementById('manage_config');						var campaigns = <?php require_once 'component/selection/SelectionJSON.inc';			echo SelectionJSON::Steps(); ?>;						/* Create an object all_configs = [{name:, text:, default_value:, values:[], category:, dependencies:[{name:, value:},...]}] */			var all_configs = [<?php			$first = true;			foreach($all_configs as $name => $data){				if(!$first) echo ", ";				$first = false;				echo "{name:".json_encode($name);				echo ", ";				echo "text:".json_encode($data[0]);				echo ", ";				echo "default_value:".json_encode($data[1]);				echo ", ";				echo "type:".json_encode($data[3]);				echo ", ";				echo "values:[";				$first_value = true;				foreach($data[2] as $default_value){					if(!$first_value) echo ", ";					$first_value = false;					echo json_encode($default_value);				}				echo "]";				echo ",category:".json_encode($data[4]).",";				echo "dependencies:[";				$first_d = true;				foreach($data[5] as $d_name => $d_value){					if(!$first_d)						echo ", ";					$first_d = false;					echo "{name:".json_encode($d_name).",value:".json_encode($d_value)."}";				}				echo "]";				echo "}";			}			?>];						/**			 * Create an object containing the current config = [{name:, value:}]			 */			var config = [<?php				if(count($config) > 0){					$first = true;					foreach($config as $c){						if(!$first) echo ", ";						$first = false;						echo "{name:".json_encode($c["name"]).", value:".json_encode($c["value"])."}";					}				}			?>];						/**			 * Check that the user can manage a selection campaign			 * if yes, run the createTable method			 */			t._init = function(){				if(!rights.manage){					var div = document.createElement("div");					div.innerHTML = "You are not allowed to edit the configuration of this campaign";					div.style.fontStyle = "italic";					div.style.color = "red";					container.appendChild(div);				} else {					this._setDependencies();					this._createTable();				}			};				/**			 * Reset all the container content			 */			t.reset = function(){				//reset tbody				container.removeChild(t.table);				delete t.table;				//reset t.date_fields				delete t.date_fields;				t.date_fields = [];				t.table = document.createElement("table");				t._setBody();				container.appendChild(t.table);			};				/**			 * Set the dependencies attribute			 * t.dependencies is an object with the following structure			 * {config_name: [{config_name: ,value:},...],...}			 */			t._setDependencies = function(){				for(var i = 0; i < all_configs.length; i++){					if(all_configs[i].dependencies.length > 0){						t.dependencies[all_configs[i].name] = all_configs[i].dependencies;					}				}			};						/**			 * Creates the manage_config string			 */			t._createTable = function(){				t.table = document.createElement("table");				// t._setHeader(theader);				t._setBody();				// t._setFoot(tfoot);				container.appendChild(t.table);			};				/**			 * Set the page header buttons			 */			t._setPageHeader = function(){				//t.header = new header_bar(t.header_cont,'toolbar');				//t.header.setTitle(theme.icons_16.config, "Selection Campaign Configuration");				/*t.header.addMenuButton(theme.icons_16.back, "Back to selection", function(){					location.assign("/dynamic/selection/page/selection_main_page");				});				t.header.addMenuButton(theme.icons_16.save, "Save", t._save);				t.header.addMenuButton(theme.icons_16.edit, "Rename campaign", t._dialogRename);				t.header.addMenuButton(theme.icons_16.remove, "Remove campaign", t._dialogRemoveCampaign);				*/			};					/**			 * Call the method addRow for each config attribute set in the all_config objects			 */			t._setBody = function(){					t._setCategoriesOrdered();				for(var category_index = 0; category_index < t.ordered.length; category_index++){					//create a table for each category					var table = document.createElement("table");					var th = document.createElement("th");					th.colSpan = 2;					th.style.textAlign = "left";					var tr = document.createElement("tr");					var ul = document.createElement("ul");					tr.appendChild(th);					table.appendChild(tr);					th.innerHTML = t.ordered[category_index].uniformFirstLetterCapitalized();					for(var i = 0; i < all_configs.length; i++){						if(all_configs[i].category == t.ordered[category_index])							t._addRowToCategoryTable(all_configs[i].name,ul);					}					table.appendChild(ul);					t.table.appendChild(table);				}			};				/**			 * Set the ordered attribute			 * ordered is an array containing the categories name ordered			 */			t._setCategoriesOrdered = function(){				t.ordered = [];				for(var i = 0; i < t.categories.length; i++)					t.ordered[i] = t.categories[t._findCategoryIndex(i+1)].name;			};				/**			 * Find the category index in t.categories object, from the willing index in ordered			 * @param {Number} index the index in the ordered array 			 */			t._findCategoryIndex = function(index){				var index_in_categories = null;				for(var i = 0; i < t.categories.length; i++){					if(index == t.categories[i].index){						index_in_categories = i;						break;					}				}				return index_in_categories;			};				/**			 * Each category as its own related table in the container			 * This method adds a row for a given config attribute into the matching category table			 * @param {String} config_name the name of the config attribute			 * @param {HTML} ul the ul into which the row would be inserted			 */			t._addRowToCategoryTable = function(config_name,ul){				if(t._allowedByDependencies(config_name)){					var tr = document.createElement("tr");					var td1 = document.createElement("td");					var td2 = document.createElement("td");					var li = document.createElement("li");					var all_configs_index = t._findAllConfigIndex(config_name);					li.innerHTML = all_configs[all_configs_index].text;					td1.appendChild(li);					var config_index = findIndexInConfig(config, config_name);					if(all_configs[all_configs_index].type == "boolean"){						var check = document.createElement("input");						check.type = "checkbox";						check.value = true;						check.name = all_configs[all_configs_index].name;						/* Set the current config */						/*config_index cannot be null because at least all the config parameters are set to default value*/						if(config[config_index].value == true)							check.checked = check._original = true;						else							check.checked = check._original = false;						check.onchange = function(){							/* We update the config object */							config[config_index].value = check.checked;							if (check.checked == check._original)								window.pnapplication.dataSaved("selection_config_"+all_configs[all_configs_index].name);							else								window.pnapplication.dataUnsaved("selection_config_"+all_configs[all_configs_index].name);							if(t._isTargetedByDependencies(this.name))								t.reset(); //may have allowed | forbidden some other fields						};						td2.appendChild(check);					} else if(all_configs[all_configs_index].type == "enum"){						var select = document.createElement("select");						for(var i = 0; i < all_configs[all_configs_index].values.length; i++){							var option = document.createElement("option");							option.value = all_configs[all_configs_index].values[i];							option.text = all_configs[all_configs_index].values[i];							if(config[config_index].value == all_configs[all_configs_index].values[i])								option.selected = true;							select.appendChild(option);						}						select._original = all_configs[all_configs_index].values[i];						select.onchange = function(){							var option = this.options[this.selectedIndex];							config[config_index].value = option.value;							if (option.value == select._original)								window.pnapplication.dataSaved("selection_config_"+all_configs[all_configs_index].name);							else								window.pnapplication.dataUnsaved("selection_config_"+all_configs[all_configs_index].name);						};						td2.appendChild(select);					} else if(all_configs[all_configs_index].type == "date"){						//add the field_date to the t.date_fields array						var index = t.date_fields.length;						t.date_fields[index] = {};						t.date_fields[index].field_date = new field_date(config[config_index].value,true);						t.date_fields[index].config_name = config_name;						t.date_fields[index].field_date._config_field_name = all_configs[all_configs_index].name;						t.date_fields[index].field_date.ondatachanged = function(f) {							window.pnapplication.dataUnSaved("selection_config_"+f._config_field_name);						};						t.date_fields[index].field_date.ondataunchanged = function(f) {							window.pnapplication.dataSaved("selection_config_"+f._config_field_name);						};						td2.appendChild(t.date_fields[index].field_date.getHTMLElement());					}					tr.appendChild(td1);					tr.appendChild(td2);					ul.appendChild(tr);				}			};				/**			 * Find the config index into the all_configs object			 * @param {String} name the name of the config attribute			 * @return {Number | Null} index			 */			t._findAllConfigIndex = function(name){				var index = null;				for(var i = 0; i < all_configs.length; i++){					if(all_configs[i].name == name){						index = i;						break;					}				}				return index;			};				/**			 * Check that a row can be displayed, according to the config dependencies			 * @param {Boolean} true if can be displayed, els false			 */			t._allowedByDependencies = function(name){				var allowed = true;				if(t._hasDependencies(name)){					for(var i = 0; i < t.dependencies[name].length; i++){						var index = findIndexInConfig(config, t.dependencies[name][i].name);						if(config[index].value != t.dependencies[name][i].value){							allowed = false;							break;						}					}				}				return allowed;			};				/**			 * Check that a config attribute has dependencies			 * @param {String} name the name of the config attribute			 * @return {Boolean}			 */			t._hasDependencies = function(name){				if(typeof(t.dependencies[name]) != "undefined")					return true;				else					return false;			};				/**			 * Check if the given config attribute is targeted by some other attributes dependencies			 * @return {Boolean}			 */			t._isTargetedByDependencies = function(name){				var is_targeted = false;				for(var i = 0; i < all_configs.length; i++){					if(t._hasDependencies(all_configs[i].name)){						for(var j = 0; j < t.dependencies[all_configs[i].name].length; j++){							if(t.dependencies[all_configs[i].name][j].name == name){								is_targeted = true;								break;							}						}					}				}				return is_targeted;			};						/**			 * Method called when clicking on the save button			 * Locks the screen, calls the saveConfig service, and popup a message when it is saved, then unlock the screen			 */			t._save = function(){				/* Get the data from all the field_dates */				var errors = false;				for(var i = 0; i < t.date_fields.length; i++){					var index = findIndexInConfig(config,t.date_fields[i].config_name);					var value = t.date_fields[i].field_date.getCurrentData();					if(value == null)						errors = true;					else						config[index].value = value;				}				if(errors)					error_dialog("Please check that all the dates fields are fulfilled");				else {					var div_locker = lock_screen();					service.json("selection","config/save",{fields:config, db_lock:t._lock_id}, function(res){						unlock_screen(div_locker);						if(!res){							error_dialog("An error occured, your informations were not saved");							return;						}						for (var i = 0; i < all_configs.length; ++i)							window.pnapplication.dataSaved("selection_config_"+all_configs[i].name);						window.top.status_manager.add_status(new window.top.StatusMessage(window.top.Status_TYPE_OK, "Your informations have been successfuly saved!", [{action:"close"}], 5000));					});				}			};		}		//Start the process		window.manage_config = new ManageConfig();		window.manage_config._init();		</script>		<?php		}			}