if (typeof require != 'undefined') {
	require([["typed_field.js","field_text.js"],"editable_cell.js","labels.js","contacts.js","contact_objects.js","address_text.js","notes_section.js"]);
	window.top.require("google_maps.js");
}

/**
 * UI Control for an organization
 * @param {Element} container where to display
 * @param {Organization} org organization to display
 * @param {Array} existing_types list of {id:...,name:...} listing all existing organization types in database that can be used
 * @param {Boolean} can_edit indicates if the user can modify the organization
 */
function organization(container, org, existing_types, can_edit) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.container = container;
	this.org = org;
	this.existing_types = existing_types;
	this.can_edit = can_edit;
	this.popup = window.parent.getPopupFromFrame(window);
	this.onchange = new Custom_Event();
	this._init();
}
organization.prototype = {
	_location_id_counter: -1,
	_init: function() {
		this.container.style.display = "flex";
		this.container.style.flexDirection = "column";
		var javascripts = [["typed_field.js","field_text.js"],"labels.js","contacts.js","contact_points.js","address_text.js","notes_section.js"];
		if (this.org.id != -1) javascripts.push("editable_cell.js");
		if (this.can_edit) javascripts.push("contact_objects.js");
		require(javascripts, function(t) {
			t._initHeader();

			var div_content = document.createElement("DIV");
			div_content.style.display = 'flex';
			div_content.style.flexDirection = 'row';
			div_content.style.alignItems = 'flex-start';
			t.container.appendChild(div_content);
			var div_contacts = document.createElement("DIV");
			var div_right = document.createElement("DIV");
			div_content.appendChild(div_contacts);
			div_content.appendChild(div_right);
			div_right.style.flex = "none";
			div_contacts.style.flex = "none";
			div_contacts.style.borderRight = "1px solid #808080";
			div_contacts.style.backgroundColor = "white";
			var div_map = document.createElement("DIV");
			div_right.appendChild(div_map);
			div_map.style.borderBottom = "1px solid #808080";
			div_map.style.paddingLeft = "3px";
			div_map.style.paddingBottom = "3px";
			div_map.style.paddingTop = "3px";
			var map_container = document.createElement("DIV");
			map_container.style.width = "400px";
			map_container.style.height = "300px";
			div_map.appendChild(map_container);
			var div_notes = document.createElement("DIV");
			div_notes.style.margin = "3px";
			div_right.appendChild(div_notes);
			t.notes = new notes_section("Organization", t.org.id, null, null, t.can_edit);
			t.notes.createInsideSection(div_notes, null, null, false, 'soft');

			var general_title = document.createElement("DIV");
			general_title.className = "page_section_header_blue";
			general_title.innerHTML = "General Contacts";
			div_contacts.appendChild(general_title);
			var div_general_contacts = document.createElement("DIV");
			div_general_contacts.style.display = "flex";
			div_general_contacts.style.flexDirection = "row";
			div_contacts.appendChild(div_general_contacts);
			var div_contacts_container = document.createElement("DIV");
			var div_contact_points = document.createElement("DIV");
			div_general_contacts.appendChild(div_contacts_container);
			div_general_contacts.appendChild(div_contact_points);
			div_contacts_container.style.paddingLeft = "5px";
			t._initGeneralContacts(div_contacts_container);
			div_contact_points.style.paddingLeft = "5px";
			div_contact_points.style.paddingRight = "5px";
			t._contact_points = new contact_points(div_contact_points, t.org, t.org.general_contact_points, null);
			t._contact_points.onchange.addListener(function() { t.onchange.fire(); });
			
			var locations_title = document.createElement("DIV");
			locations_title.className = "page_section_header_blue";
			locations_title.style.borderTop = "1px solid #808080";
			locations_title.innerHTML = "Locations / Addresses";
			div_contacts.appendChild(locations_title);

			var div = document.createElement("DIV");
			div.style.padding = "2px 5px";
			t._add_location_button = document.createElement("BUTTON");
			t._add_location_button.className = "action";
			t._add_location_button.innerHTML = "Add Location / Address";
			t._add_location_button.onclick = function() {
				t._createLocationDiv(div_contacts, div, null);
			};
			div.appendChild(t._add_location_button);
			div_contacts.appendChild(div);
			
			for (var i = 0; i < t.org.locations.length; ++i)
				t._createLocationDiv(div_contacts, div, t.org.locations[i]);
			
			window.top.require("google_maps.js", function() {
				t._map = new window.top.GoogleMap(map_container, function() {
					t._markers = [];
					t._updateMap();
					t.onchange.addListener(function(){ t._updateMap(); });
					layout.changed(div_map);
				});
			});
			
			if (t.org.id > 0) {
				t.popup.addCloseButton();
			} else {
				t.popup.addCreateButton(function() { t._createNewOrg(); });
				t.popup.addCancelButton();
			}
		}, this);
	},
	_initHeader: function() {
		var header = document.createElement("DIV");
		header.style.backgroundColor = 'white';
		header.style.borderBottom = "1px solid #808080";
		header.style.flex = "none";
		header.style.textAlign = "center";
		this.container.appendChild(header);
		// name of organization
		var name_container = document.createElement("DIV");
		header.appendChild(name_container);
		if (this.org.id != -1) {
			var t=this;
			this._name = new editable_cell(name_container, "Organization", "name", this.org.id, "field_text", {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}}, this.org.name, null, function(field){
				t.org.name = field.getCurrentData();
				t.onchange.fire();
			}, function(edit){
				if (!t.can_edit) edit.cancelEditable();
			});
		} else {
			this._name = new field_text(this.org.name, true, {min_length:1,max_length:100,can_be_null:false,style:{fontSize:"x-large"}});
			name_container.appendChild(this._name.getHTMLElement());
			var t=this;
			this._name.onchange.addListener(function() {
				t.org.name = t._name.getCurrentData();
				t.onchange.fire();
			});
		}
		
		// list of organization types
		var t=this;
		var types = [];
		for (var i = 0; i < t.org.types_ids.length; ++i) {
			for (var j = 0; j < t.existing_types.length; ++j)
				if (t.existing_types[j].id == t.org.types_ids[i]) {
					types.push({
						id: t.existing_types[j].id,
						name: t.existing_types[j].name,
						editable: t.can_edit && t.existing_types[j].builtin != 1,
						removable: t.can_edit
					});
					break;
				}
		}
		t._types = new labels("#90D090", types, function(id,onedited) {
			// onedit
			var name;
			for (var j = 0; j < t.existing_types.length; ++j)
				if (t.existing_types[j].id == id) { name = t.existing_types[j].name; break; }
			inputDialog(null,"Rename Organization Type","New name",name,100,function(name) {
				name = name.trim();
				if (name.length == 0) return "Please enter a name";
				for (var j = 0; j < t.existing_types.length; ++j)
					if (t.existing_types[j].id != id && t.existing_types[j].name.isSame(name)) return "A type already exists with this name";
				return null;
			},function(name) {
				if (!name) return;
				name = name.trim();
				for (var j = 0; j < t.existing_types.length; ++j)
					if (t.existing_types[j].id == id)
						t.existing_types[j].name = name;
				service.json("data_model","save_cell",{table:'OrganizationType',column:'name',row_key:id,value:name,lock:null},function(res){
					if (res) onedited(name);
				});
			});
		}, function(id, handler) {
			// onremove
			var ok = function() {
				for (var i = 0; i < t.org.types_ids.length; ++i)
					if (t.org.types_ids[i] == id) {
						t.org.types_ids.splice(i,1);
						t.onchange.fire();
						handler();
						break;
					}
			};
			if (t.org.id != -1) {
				service.json("contact", "unassign_organization_type", {organization:t.org.id,type:id}, function(res) {
					if (res) ok();
				});
			} else
				ok();
		}, function() {
			// add_list_provider
			var items = [];
			for (var i = 0; i < t.existing_types.length; ++i) {
				var found = false;
				for (var j = 0; j < t.org.types_ids.length; ++j)
					if (t.org.types_ids[j] == t.existing_types[i].id) { found = true; break; }
				if (!found) {
					var item = document.createElement("DIV");
					item.className = "context_menu_item";
					item.innerHTML = t.existing_types[i].name;
					item.org_type = t.existing_types[i];
					item.style.fontSize = "8pt";
					item.onclick = function() {
						if (t.org.id != -1) {
							var tt=this;
							service.json("contact", "assign_organization_type", {organization:t.org.id,type:this.org_type.id}, function(res) {
								if (res) {
									t.org.types_ids.push(tt.org_type.id);
									t._types.addItem(tt.org_type.id, tt.org_type.name, tt.org_type.builtin != 1, true);
									t.onchange.fire();
								}
							});
						} else {
							t.org.types_ids.push(this.org_type.id);
							t._types.addItem(this.org_type.id, this.org_type.name, this.org_type.builtin != 1, true);
							t.onchange.fire();
						}
					};
					items.push(item);
				}
			}
			var item = document.createElement("DIV");
			item.className = "context_menu_item";
			item.innerHTML = "<img src='"+theme.icons_10.add+"' style='vertical-align:bottom;padding-right:3px;margin-bottom:1px'/> Create a new type";
			item.style.fontSize = "8pt";
			item.onclick = function() {
				inputDialog(theme.icons_16.add,"New Organization Type","Enter the name of the organization type","",100,function(name){
					if (name.length == 0) return "Please enter a name";
					for (var i = 0; i < t.existing_types.length; ++i)
						if (t.existing_types[i].name.isSame(name))
							return "This organization type already exists";
					return null;
				},function(name){
					if (!name) return;
					service.json("contact","new_organization_type",{creator:org.creator,name:name},function(res){
						if (!res) return;
						if (t.org.id != -1) {
							service.json("contact", "assign_organization_type", {organization:t.org.id,type:res.id}, function(res2) {
								if (res2) {
									t.org.types_ids.push(res.id);
									existing_types.push({id:res.id,name:name});
									t._types.addItem(res.id, name, t.can_edit, t.can_edit);
									t.onchange.fire();
								}
							});
						} else {
							t.org.types_ids.push(res.id);
							t.existing_types.push({id:res.id,name:name});
							t._types.addItem(res.id, name, t.can_edit, t.can_edit);
							t.onchange.fire();
						}
					});
				});
			};
			items.push(item);
			return items;
		});
		var types_container = document.createElement("DIV");
		types_container.style.margin = "2px";
		types_container.style.verticalAlign = "middle";
		types_container.appendChild(document.createTextNode("Types: "));
		types_container.appendChild(this._types.element);
		header.appendChild(types_container);
	},
	_initGeneralContacts: function(container) {
		this._contacts_widget = new contacts(container, "organization", this.org.id, this.org.general_contacts, null, true, this.can_edit, this.can_edit, this.can_edit);
		var t=this;
		this._contacts_widget.onchange.addListener(function(c){
			t.org.general_contacts = c.getContacts();
			t.onchange.fire();
		});
	},
	_createLocationDiv: function(div_contacts, div, location) {
		var container = document.createElement("DIV");
		div_contacts.insertBefore(container, div);
		container.style.borderBottom = "1px solid #808080";
		container.style.padding = "2px 5px";
		if (location == null) {
			if (this.org.id != -1) {
				container.innerHTML = "<img src='"+theme.icons_16.loading+"'/>";
				this.popup.freeze();
				layout.changed(div_contacts);
				var addr = new PostalAddress(-1,window.top.default_country_id);
				var t = this;
				service.json("contact","add_address",{type:"organization",type_id:this.org.id,address:addr},function(res) {
					if (!res || !res.id) {
						div_contacts.removeChild(container);
						t.popup.unfreeze();
						layout.changed(div_contacts);
						return;
					}
					addr.id = res.id;
					var loc = new OrganizationLocation("",addr,[],[]);
					t.org.locations.push(loc);
					container.removeAllChildren();
					new OrganizationLocationControl(container, t, loc, true);
					layout.changed(div_contacts);
					t.onchange.fire();
					t.popup.unfreeze();
				});
			} else {
				var loc = new OrganizationLocation("",new PostalAddress(this._location_id_counter--,window.top.default_country_id),[],[]);
				this.org.locations.push(loc);
				new OrganizationLocationControl(container, this, loc, true);
				layout.changed(div_contacts);
				this.onchange.fire();
				// TODO map
			}
		} else {
			new OrganizationLocationControl(container, this, location, this.can_edit);
			layout.changed(div_contacts);
			// TODO map
		}
		// TODO remove location
	},
	_updateMap: function() {
		for (var i = 0; i < this.org.locations.length; ++i) {
			var loc = this.org.locations[i];
			var marker = null;
			for (var j = 0; j < this._markers.length; ++j)
				if (this._markers[j].loc_id == loc.address.id) { marker = this._markers[j]; break; }
			var t=this;
			var updateMarker = function(marker, lat, lng, name, loc_id) {
				if (lat) {
					if (marker) {
						if (lat == marker.marker.lat() && lng == marker.marker.lng() && name == marker.name) return;
						t._map.removeShape(marker.marker);
						marker.marker.setPosition(lat, lng);
						marker.marker.setContent(name);
						marker.name = name;
						t._map.addShape(marker.marker);
						t._map.fitToShapes(true);
					} else {
						marker = {
							loc_id: loc_id,
							name: name,
							marker: t._map.addPNMarker(lat, lng, "#FFFF80", name)
						};
						t._markers.push(marker);
						t._map.fitToShapes(true);
					}
				} else {
					if (!marker) return;
					t._markers.removeUnique(marker);
					t._map.removeShape(marker.marker);
				}
			};
			if (loc.address.lat)
				updateMarekr(marker, loc.address.lat, loc.address.lng, loc.name, loc.address.id);
			else if(loc.address.geographic_area != null && loc.address.geographic_area.id > 0) {
				var closure = {
					loc:loc,
					searchCenter: function() {
						var clo=this;
						window.top.geography.getCountryAreaRect(loc.address.country_id, loc.address.geographic_area.id, function(rect) {
							if (!rect)
								updateMarker(marker);
							else {
								var center = window.top.geography.boxCenter(rect);
								updateMarker(marker, center.lat, center.lng, clo.loc.name, clo.loc.address.id);
							}
						});
					}
				};
				closure.searchCenter();
			} else
				updateMarker(marker);
		}
	},
	_createNewOrg: function() {
		this.popup.freeze();
		var t=this;
		service.json("contact", "add_organization", this.org, function(res) {
			if (!res) { t.popup.unfreeze(); return; }
			t.notes.save(res.id, function() {
				t.popup.close();
			});
		});
	}
};

function OrganizationLocationControl(container, org, loc, can_edit) {
	this._init = function() {
		// name
		var name_div = document.createElement("DIV");
		name_div.style.marginBottom = "3px";
		name_div.innerHTML = "<img src='"+theme.icons_16.label+"'/>";
		name_div.appendChild(document.createTextNode(" Name "));
		if (loc.address.id > 0) {
			this._name = new editable_cell(name_div, "OrganizationAddress", "name", {organisation:org.org.id,address:loc.address.id}, "field_text", {min_length:0,max_length:100,can_be_null:true,style:{fontWeight:'bold',fontSize:'11pt'}}, loc.name, null, function(field){
				loc.name = field.getCurrentData();
				org.onchange.fire();
			}, function(edit){
				if (!can_edit) edit.cancelEditable();
			});
		} else {
			this._name = new field_text(loc.name, true, {min_length:0,max_length:100,can_be_null:true,style:{fontWeight:'bold',fontSize:'11pt'}});
			name_div.appendChild(this._name.getHTMLElement());
			this._name.onchange.addListener(function(f) {
				loc.name = f.getCurrentData();
				org.onchange.fire();
			});
		}
		container.appendChild(name_div);
		
		// address
		var table = document.createElement("TABLE");
		container.appendChild(table);
		table.style.backgroundColor = "white";
		table.style.borderSpacing = "0px";
		table.style.border = "1px solid #808080";
		table.style.marginBottom = "3px";
		setBorderRadius(table, 5, 5, 5, 5, 5, 5, 5, 5);
		var tr = document.createElement("TR");
		table.appendChild(tr);
		var td = document.createElement("TD");
		tr.appendChild(td);
		td.style.padding = "2px 5px 2px 5px";
		td.innerHTML = "<img src='/static/contact/address_16.png' style='vertical-align:bottom;padding-right:3px' onload='layout.changed(this);'/>Address";
		td.style.backgroundColor = "#F0F0F0";
		setBorderRadius(td, 5, 5, 5, 5, 0, 0, 0, 0);
		tr = document.createElement("TR");
		table.appendChild(tr);
		td = document.createElement("TD");
		tr.appendChild(td);
		td.style.padding = "2px";
		this._address = new address_text(loc.address);
		td.appendChild(this._address.element);
		if (loc.address.id == -1 || can_edit) {
			var t=this;
			td.onmouseover = function() {
				t._address.element.style.textDecoration = 'underline';
				t._address.element.style.cursor = 'pointer';
			};
			td.onmouseout = function () {
				t._address.element.style.textDecoration = '';
				t._address.element.style.cursor = '';
			};
			td.onclick = function(ev) {
				if (ev) stopEventPropagation(ev);
				require(["popup_window.js","edit_address.js"], function() {
					var show_popup = function(lock_id) {
						var content = document.createElement("DIV");
						content.style.padding = "5px";
						var copy = objectCopy(loc.address);
						var edit = new edit_address(content, copy);
						var p = new popup_window("Edit Postal Address", "/static/contact/address_16.png", content);
						p.addOkCancelButtons(function() {
							p.freeze();
							updatePostalAddress(loc.address, edit.address);
							org.onchange.fire();
							var end = function() {
								p.close();
								td.removeChild(t._address.element);
								t._address = new address_text(loc.address);
								td.appendChild(t._address.element);
							};
							if (loc.address.id > 0) {
								service.json("data_model","save_entity",{
									table: "PostalAddress",
									key: loc.address.id,
									lock: lock_id,
									field_country: loc.address.country_id,
									field_geographic_area: loc.address.geographic_area ? loc.address.geographic_area.id : null,
									field_street: loc.address.street,
									field_street_number: loc.address.street_number,
									field_building: loc.address.building,
									field_unit: loc.address.unit,
									field_additional: loc.address.additional,
									field_lat: loc.address.lat,
									field_lng: loc.address.lng,
									unlock:true
								},function(res) {
									if (!res) { p.unfreeze(); return; }
									window.databaselock.removeLock(lock_id);
									end();
								});
							} else
								end();
						},function() {
							return true;
						});
						p.show();
					};
					if (loc.address.id > 0) {
						service.json("data_model", "lock_row", {
							table: "PostalAddress",
							row_key: loc.address.id
						}, function(res) {
							if (!res) return;
							window.databaselock.addLock(res.lock);
							show_popup(res.lock);
						});
					} else
						show_popup();
				});
			};
		}
		
		// contacts and contact points
		var ccp_container = document.createElement("DIV");
		ccp_container.style.display = "flex";
		ccp_container.style.flexDirection = "row";
		ccp_container.style.alignItems = "top";
		container.appendChild(ccp_container);
		var contacts_container = document.createElement("DIV");
		var contact_points_container = document.createElement("DIV");
		ccp_container.appendChild(contacts_container);
		ccp_container.appendChild(contact_points_container);
		contact_points_container.style.marginLeft = "5px";
		this._contacts_widget = new contacts(contacts_container, "organization", org.org.id, loc.contacts, {attached_location:loc.address.id}, true, can_edit, can_edit, can_edit);
		this._contacts_widget.onchange.addListener(function(c){
			loc.contacts = c.getContacts();
			org.onchange.fire();
		});
		this._contact_points = new contact_points(contact_points_container, org.org, loc.contact_points, loc.address.id);
		this._contact_points.onchange.addListener(function() { org.onchange.fire(); });
	};
	this._init();
}