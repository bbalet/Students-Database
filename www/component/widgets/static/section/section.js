if (typeof require != 'undefined') {
	require("color.js");
	require("animation.js");
}

function section_from_html(container) {
	if (typeof container == 'string') container = document.getElementById(container);
	var icon = null;
	var title = "";
	var collapsable = false;
	var border_color = null;
	var title_background_from = null;
	var title_background_to = null;
	if (container.hasAttribute("icon")) {
		icon = container.getAttribute("icon");
		container.removeAttribute("icon");
	}
	if (container.hasAttribute("title")) {
		title = container.getAttribute("title");
		container.removeAttribute("title");
	}
	if (container.hasAttribute("border_color")) {
		border_color = container.getAttribute("border_color");
		container.removeAttribute("border_color");
	}
	if (container.hasAttribute("title_background_from")) {
		title_background_from = container.getAttribute("title_background_from");
		container.removeAttribute("title_background_from");
	}
	if (container.hasAttribute("title_background_to")) {
		title_background_to = container.getAttribute("title_background_to");
		container.removeAttribute("title_background_to");
	}
	if (container.hasAttribute("collapsable")) {
		collapsable = container.getAttribute("collapsable") == "true" ? true : false;
		container.removeAttribute("collapsable");
	}
	var content = document.createElement("DIV");
	while (container.childNodes.length > 0) content.appendChild(container.childNodes[0]);
	var s = new section(icon,title,content,collapsable,border_color,title_background_from,title_background_to);
	container.appendChild(s.element);
	return s;
}

function section(icon, title, content, collapsable, border_color, title_background_from, title_background_to, title_style) {
	if (!border_color) border_color = "#80A060";
	if (!title_background_from) title_background_from = "#E0F0E0";
	if (!title_background_to) title_background_to = "#C0E8B0";

	var t=this;
	this.element = document.createElement("DIV");
	
	this.addTool = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar.appendChild(element);
		element.style.display = "inline-block";
		fireLayoutEventFor(this.element);
	};
	this.addToolLeft = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar_left.appendChild(element);
		element.style.display = "inline-block";
		fireLayoutEventFor(this.element);
	};
	this.addToolRight = function(element) {
		if (typeof element == 'string') { var d = document.createElement("DIV"); d.innerHTML = element; element = d; }
		this.toolbar_right.appendChild(element);
		element.style.display = "inline-block";
		fireLayoutEventFor(this.element);
	};
	
	this._init = function() {
		this.element.style.border = "1px solid "+border_color;
		setBorderRadius(this.element, 5, 5, 5, 5, 5, 5, 5, 5);
		this.header = document.createElement("DIV");
		this.element.appendChild(this.header);
		this.header.style.whiteSpace = 'nowrap';
		setBorderRadius(this.header, 5, 5, 5, 5, 0, 0, 0, 0);
		this.header.style.borderBottom = "1px solid "+border_color;
		require("color.js",function() {
			var col_from = parse_color(title_background_from);
			var col_to = parse_color(title_background_to);
			var intermediate_color = color_string(color_between(col_from, col_to, 20));
			var intermediate_color2 = color_string(color_between(col_from, col_to, 50));
			//setBackgroundGradient(t.header, "vertical", [{pos:0,color:title_background_from},{pos:55,color:intermediate_color},{pos:56,color:title_background_to},{pos:100,color:intermediate_color2}]);
			setBackgroundGradient(t.header, "vertical", [{pos:0,color:title_background_from},{pos:100,color:intermediate_color2}]);
		});
		this.header.style.height = "25px";

		this.title_container = document.createElement("DIV");
		this.title_container.setAttribute("layout", "fill");
		this.title_container.style.marginLeft = "5px";
		this.header.appendChild(this.title_container);
		if (icon) {
			this.icon = document.createElement("IMG");
			this.icon.src = icon;
			this.icon.style.verticalAlign = "bottom";
			this.icon.style.marginRight = "3px";
			this.icon.onload = function() { fireLayoutEventFor(t.element); };
			this.title_container.appendChild(this.icon);
		}
		this.title = document.createElement("DIV");
		this.title.innerHTML = title;
		this.title.style.fontWeight = "bold";
		this.title.style.fontSize = "11pt";
		this.title.style.display = "inline-block";
		this.title.style.color = "#505050";
		//this.title.style.fontFamily = "Calibri";
		if (title_style)
			for (var att in title_style) this.title.style[att] = title_style[att];
		this.title_container.appendChild(this.title);
		this.toolbar_left = document.createElement("DIV");
		this.header.appendChild(this.toolbar_left);
		this.toolbar = document.createElement("DIV");
		this.header.appendChild(this.toolbar);
		this.toolbar_right = document.createElement("DIV");
		this.header.appendChild(this.toolbar_right);
		if (collapsable) {
			this.collapse_container = document.createElement("DIV");
			this.collapse_container.style.padding = "4px";
			this.collapse_button = document.createElement("IMG");
			this.collapse_button.src = get_script_path("section.js")+"collapse.png";
			this.collapse_button.onload = function() { fireLayoutEventFor(t.element); };
			this.collapse_button.style.cursor = 'pointer';
			this.collapsed = false;
			this.collapse_button.onclick = function() { t.toggleCollapseExpand(); }; 
			this.collapse_container.appendChild(this.collapse_button);
			this.header.appendChild(this.collapse_container);
		}
		require("horizontal_layout.js",function(){
			new horizontal_layout(t.header);
		});
		require("vertical_align.js",function(){
			new vertical_align(t.title_container, "middle");
		});
		
		this.content_container = document.createElement("DIV");
		this.content_container.style.backgroundColor = "#ffffff";
		setBorderRadius(this.content_container, 0, 0, 0, 0, 5, 5, 5, 5);
		setBorderRadius(content, 0, 0, 0, 0, 5, 5, 5, 5);
		this.element.appendChild(this.content_container);
		this.content_container.appendChild(content);
	};
	
	this.toggleCollapseExpand = function() {
		if (this.collapsed) {
			this.collapse_button.src = get_script_path("section.js")+"collapse.png";
			this.collapsed = false;
			require("animation.js",function() {
				if (t.content_container.anim1) animation.stop(t.content_container.anim1);
				if (t.content_container.anim2) animation.stop(t.content_container.anim2);
				t.content_container.anim1 = animation.create(t.content_container, 0, t.content_container.originalHeight, 500, function(value, element) {
					element.style.height = Math.floor(value)+'px';
					element.style.overflow = "hidden";
					if (value == t.content_container.originalHeight) fireLayoutEventFor(t.element.parentNode);
				});
				t.content_container.anim2 = animation.fadeIn(t.content_container, 600, function() {
					t.content_container.style.position = 'static';
					t.content_container.style.visibility = 'visible';
					t.header.style.borderBottom = "1px solid "+border_color;
					setBorderRadius(t.header, 5, 5, 5, 5, 0, 0, 0, 0);
					t.content_container.style.height = "";
					t.content_container.style.overflow = "";
				});
			});
			this.content_container.style.position = 'static';
			this.content_container.style.visibility = 'visible';
			this.header.style.borderBottom = "1px solid "+border_color;
			setBorderRadius(this.header, 5, 5, 5, 5, 0, 0, 0, 0);
		} else {
			this.collapse_button.src = get_script_path("section.js")+"expand.png";
			this.collapsed = true;
			require("animation.js",function() {
				if (t.content_container.anim1) animation.stop(t.content_container.anim1);
				if (t.content_container.anim2) animation.stop(t.content_container.anim2);
				var start = t.content_container.offsetHeight;
				t.content_container.originalHeight = start;
				t.content_container.anim1 = animation.create(t.content_container, start, 0, 600, function(value, element) {
					element.style.height = Math.floor(value)+'px';
					element.style.overflow = "hidden";
					if (value == 0) fireLayoutEventFor(t.element.parentNode);
				});
				t.content_container.anim2 = animation.fadeOut(t.content_container, 500, function() {
					t.content_container.style.position = 'absolute';
					t.content_container.style.visibility = 'hidden';
					t.content_container.style.top = '-10000px';
					t.content_container.style.left = '-10000px';
					t.header.style.borderBottom = "none";
					setBorderRadius(t.header, 5, 5, 5, 5, 5, 5, 5, 5);
				});
			});
		}
	};
	
	this._init();
}