if (typeof require != 'undefined') require("color.js");

/**
 * Display a screen to choose a color
 * @param {Element} container where to put the screen
 * @param {String} current_color currently selected color in #RRGGBB format, or null
 */
function color_choice(container, current_color) {
	var t=this;
	/** Create the screen */
	this._init = function() {
		var page = document.createElement("TABLE"); container.appendChild(page);
		page.style.display = "inline-block";
		var tr, td;
		page.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		var simple_td = td;
		var simple_choice_table = document.createElement("TABLE");
		simple_choice_table.style.borderCollapse = "collapse";
		simple_choice_table.style.borderSpacing = "0px";
		simple_choice_table.style.backgroundColor = "white";
		td.appendChild(simple_choice_table);
		tr.appendChild(this.custom_choice_container = document.createElement("TD"));
		this.custom_choice_container.style.position = 'absolute';
		this.custom_choice_container.style.visibility = 'hidden';
		this.custom_choice_container.style.top = '-10000px';
		var default_colors = [
		  ["#FFFFFF", "#C0C0C0", "#808080", "#404040", "#000000"],
		  ["#0000FF", "#4040FF", "#8080FF", "#A0A0FF", "#C0C0FF"],
		  ["#00FF00", "#40FF40", "#80FF80", "#A0FFA0", "#C0FFC0"],
		  ["#FF0000", "#FF4040", "#FF8080", "#FFA0A0", "#FFC0C0"],
		  ["#FFFF00", "#FFFF40", "#FFFF80", "#FFFFA0", "#FFFFC0"],
		  ["#FF8000", "#FF8040", "#FF8080", "#FF80A0", "#FF80C0"],
		  ["#80FF00", "#80FF40", "#80FF80", "#80FFA0", "#80FFC0"],
		  ["#FF00FF", "#FF40FF", "#FF80FF", "#FFA0FF", "#FFC0FF"],
		  ["#FF0080", "#FF4080", "#FF8080", "#FFA080", "#FFC080"],
		  ["#8000FF", "#8040FF", "#8080FF", "#80A0FF", "#80C0FF"],
		  ["#00FFFF", "#40FFFF", "#80FFFF", "#A0FFFF", "#C0FFFF"],
		  ["#0080FF", "#4080FF", "#8080FF", "#A080FF", "#C080FF"],
		  ["#00FF80", "#40FF80", "#80FF80", "#A0FF80", "#C0FF80"],
		];
		this.default_boxes = [];
		for (var i = 0; i < default_colors.length; ++i) {
			simple_choice_table.appendChild(tr = document.createElement("TR"));
			this.default_boxes.push([]);
			for (var j = 0; j < default_colors[i].length; ++j) {
				var color = parseColor(default_colors[i][j]);
				tr.appendChild(td = document.createElement("TD"));
				var box = document.createElement("DIV");
				box.color = color;
				box.style.border = "2px solid white";
				box.style.backgroundColor = colorToString(color);
				box.style.width = "15px";
				box.style.height = "15px";
				td.appendChild(box);
				this.default_boxes[i].push(box);
				box.onclick = function() { t.setColor(this.color); };
			}
		}

		page.appendChild(tr = document.createElement("TR"));
		tr.appendChild(td = document.createElement("TD"));
		td.colSpan = 2;
		td.style.textAlign = "center";
		var button = document.createElement("BUTTON");
		button.className = "action";
		button.innerHTML = "Custom Color";
		td.appendChild(button);
		button.onclick = function() {
			if (t.custom_choice_container.childNodes.length == 0) {
				// show
				this.innerHTML = "Default Colors";
				var frame = document.createElement("IFRAME");
				t.frame = frame;
				frame.oncolorchanged = function(hex) {
					t.setColor('#'+hex);
				};
				frame.src = "/static/widgets/color_choice/lib_colorpicker/default.html"+colorToString(t.color);
				frame.onload = function() {
					layout.changed(container);
				};
				frame.style.height = "280px";
				frame.style.width = "450px";
				t.custom_choice_container.appendChild(frame);
				frame.style.border = "0px";
				t.custom_choice_container.style.position = 'static';
				t.custom_choice_container.style.visibility = 'visible';
				simple_td.style.position = 'absolute';
				simple_td.style.visibility = 'hidden';
				simple_td.style.top = '-10000px';
				layout.changed(container);
			} else {
				// hide
				this.innerHTML = "Custom Color";
				var col = getIFrameDocument(t.frame).getElementById('cp1_Hex').value;
				while (t.custom_choice_container.childNodes.length > 0) t.custom_choice_container.removeChild(t.custom_choice_container.childNodes[0]);
				simple_td.style.position = 'static';
				simple_td.style.visibility = 'visible';
				t.custom_choice_container.style.position = 'absolute';
				t.custom_choice_container.style.visibility = 'hidden';
				layout.changed(container);
				t.setColor('#'+col);
			}
		};
		
		var pn_colors = ["#009DE1","#22BBEA","#CC6600","#FF9933"];
		var div = document.createElement("DIV");
		div.style.display = "inline-block";
		div.style.verticalAlign = "top";
		div.style.textAlign = "center";
		div.innerHTML = "<b>PN Colors</b><br/>";
		for (var i = 0; i < pn_colors.length; ++i) {
			var color = parseColor(pn_colors[i]);
			var box = document.createElement("DIV");
			box.color = color;
			box.style.border = "2px solid white";
			box.style.backgroundColor = colorToString(color);
			box.style.width = "15px";
			box.style.height = "15px";
			box.style.display = "inline-block";
			div.appendChild(box);
			div.appendChild(document.createElement("BR"));
			this.default_boxes[i].push(box);
			box.onclick = function() { t.setColor(this.color); };
		}
		container.appendChild(div);
	};
	/** Change the selected color
	 * @param {String} color the color in string format which will be parsed using parseColor
	 */
	this.setColor = function(color) {
		if (typeof color == 'string') color = parseColor(color);
		for (var i = 0; i < this.default_boxes.length; ++i) {
			for (var j = 0; j < this.default_boxes[i].length; ++j) {
				var box = this.default_boxes[i][j];
				if (colorEquals(box.color, color))
					box.style.border = "2px solid #FF8000";
				else
					box.style.border = "2px solid white";
			}
		}
		this.color = color;
	};
	
	require("color.js", function() {
		t._init();
		if (!current_color) current_color = "#000000";
		t.setColor(current_color);
	});
}

/** Small box with selected color, and showing a color_choice when clicking on it
 * @param {Element} container where to put the box
 * @param {String} color current color
 * @no_name_check
 */
function color_widget(container, color) {
	if (typeof container == 'string') container = document.getElementById(container);
	this.color = color;
	this.onchange = null;
	var div = document.createElement("DIV");
	div.style.display = "inline-block";
	div.style.width = "16px";
	div.style.height = "12px";
	div.style.border = "1px solid black";
	div.style.cursor = "pointer";
	div.style.backgroundColor = color;
	container.appendChild(div);
	var t=this;
	div.onclick = function() {
		require("popup_window.js", function() {
			var content = document.createElement("DIV");
			var popup = new popup_window("Change Color", theme.icons_16.color, content);
			var chooser = new color_choice(content, t.color);
			popup.addOkCancelButtons(function() {
				t.color = colorToString(chooser.color);
				div.style.backgroundColor = t.color;
				if (t.onchange) t.onchange(t);
				popup.close();
			});
			popup.show();
		});
	};
}