/**
 * Return the absolute position of the left edge, relative to the given element or to the document
 * @param {Element} e the element to get the absolute position
 * @param {Element} relative the element from which we want the absolute position, or null to get the position in the document
 * @returns {Number} the left offset in pixels
 */
function absoluteLeft(e,relative) {
	var left = e.offsetLeft;
	try { 
		if (e.offsetParent && e.offsetParent != relative) {
			var p = e;
			do {
				p = p.parentNode;
				left -= p.scrollLeft;
			} while (p != e.offsetParent);
			left += absoluteLeft(e.offsetParent,relative); 
		}
	} catch (ex) {}
	return left;
}
/**
 * Return the absolute position of the top edge, relative to the given element or to the document
 * @param {Element} e the element to get the absolute position
 * @param {Element} relative the element from which we want the absolute position, or null to get the position in the document
 * @returns {Number} the top offset in pixels
 */
function absoluteTop(e,relative) {
	var top = e.offsetTop;
	try { 
		if (e.offsetParent && e.offsetParent != relative) {
			var p = e;
			do {
				p = p.parentNode;
				top -= p.scrollTop;
			} while (p != e.offsetParent);
			top += absoluteTop(e.offsetParent,relative); 
		}
	} catch (ex) {}
	return top;
}
/**
 * Return the first parent having a CSS attribute position:relative, or the document.body
 * @param {Element} e the html element
 * @returns {Element} the first parent having a position set to relative
 */
function getAbsoluteParent(e) {
	var p = e.parentNode;
	do {
		if (getComputedStyle(p).position == 'relative')
			return p;
		p = p.parentNode;
	} while(p != null && p.nodeType == 1);
	return document.body;
}

/** Get the coordinates of a frame relative to the top window
 * @param {window} frame the frame
 * @returns {Object} contains x and y attributes
 */
function getAbsoluteCoordinatesRelativeToWindowTop(frame) {
	if (frame.parent == null || frame.parent == frame || frame.parent == window.top) return {x:0,y:0};
	var pos = getAbsoluteCoordinatesRelativeToWindowTop(frame.parent);
	pos.x += absoluteLeft(frame.frameElement);
	pos.y += absoluteTop(frame.frameElement);
	return pos;
}

/**
 * Return the list of html elements at the given position in the document
 * @param {Number} x horizontal position
 * @param {Number} y vertical position
 * @returns {Array} list of HTML elements at the given position
 */
function getElementsAt(x,y) {
	var list = [];
	var disp = [];
	do {
		var e = document.elementFromPoint(x,y);
		if (e == document || e == document.body || e == window || e.nodeName == "HTML" || e.nodeName == "BODY") break;
		if (e == null) break;
		list.push(e);
		disp.push(e.style.display);
		e.style.display = "none";
	} while (true);
	for (var i = 0; i < list.length; ++i)
		list[i].style.display = disp[i];
	return list;
}

/** Retrieve TR elements in a table, including THEAD, TFOOT and TBODY
 * @param {Element} table the table
 * @returns {Array} list of TR elements
 */
function getTableRows(table) {
	var rows = [];
	for (var i = 0; i < table.childNodes.length; ++i) {
		var e = table.childNodes[i];
		if (e.nodeType != 1) continue;
		if (e.nodeName == 'TR') rows.push(e);
		else {
			var list = getTableRows(e);
			for (var j = 0; j < list.length; ++j) rows.push(list[j]);
		}
	}
	return rows;
}

//useful functions to set height and width, taking into account borders, margins, and paddings
function setWidth(element, width) {
	var win = getWindowFromElement(element);
	if (win != window) { win.setWidth(element, width); return; }
	var s = getComputedStyleSizes(element);
	width -= parseInt(s.borderLeftWidth);
	width -= parseInt(s.borderRightWidth);
	width -= parseInt(s.marginLeft);
	width -= parseInt(s.marginRight);
	width -= parseInt(s.paddingLeft);
	width -= parseInt(s.paddingRight);
	element.style.width = width+"px";
}
function setHeight(element, height) {
	var win = getWindowFromElement(element);
	if (win != window) { win.setHeight(element, height); return; }
	var s = getComputedStyleSizes(element);
	height -= parseInt(s.borderTopWidth);
	height -= parseInt(s.borderBottomWidth);
	height -= parseInt(s.marginTop);
	height -= parseInt(s.marginBottom);
	height -= parseInt(s.paddingTop);
	height -= parseInt(s.paddingBottom);
	element.style.height = height+"px";
}
function getWidth(element) {
	var win = getWindowFromElement(element);
	if (win != window) return win.getWidth(element);
	var s = getComputedStyleSizes(element);
	var w = element.offsetWidth;
	w += parseInt(s.marginLeft) + parseInt(s.marginRight);
	return w;
}
function getHeight(element) {
	var win = getWindowFromElement(element);
	if (win != window) return win.getHeight(element);
	var s = getComputedStyleSizes(element);
	var h = element.offsetHeight;
	h += parseInt(s.marginTop) + parseInt(s.marginBottom);
	return h;
}
function getInnerHeight(element) {
	var win = getWindowFromElement(element);
	if (win != window) return win.getInnerHeight(element);
	var s = getComputedStyleSizes(element);
	var h = element.clientHeight;
	h -= parseInt(s.paddingTop) + parseInt(s.paddingBottom);
	return h;
}
function getFixedPosition(elem) {
	return _getFixedPosition(window,elem);
}
function _getFixedPosition(win,elem) {
	var x = elem.offsetLeft;
	var y = elem.offsetTop;
	if (elem.nodeName != 'BODY') {
		while (elem.offsetParent) {
			var p = elem.parentNode;
			while (p != elem.offsetParent) {
				x -= p.scrollLeft;
				y -= p.scrollTop;
				p = p.parentNode;
			}
			x -= elem.offsetParent.scrollLeft;
			y -= elem.offsetParent.scrollTop;
			if (elem.nodeName == 'BODY') break;
			elem = elem.offsetParent;
			x += elem.offsetLeft;
			y += elem.offsetTop;
		}
	}
	if (win.frameElement) {
		if (win.scrollX) x -= win.scrollX;
		if (win.scrollY) y -= win.scrollY;
		var pos = _getFixedPosition(getWindowFromElement(win.frameElement), win.frameElement);
		x += pos.x;
		y += pos.y;
	}
	return {x:x,y:y};
}
function getComputedStyleSizes(e) {
	if (e.nodeType != 1) {
		return {
			borderLeftWidth: 0, borderRightWidth: 0, borderTopWidth: 0, borderBottomWidth: 0,
			marginLeft: 0, marginRight: 0, marginTop: 0, marginBottom: 0,
			paddingTop: 0, paddingBottom: 0, paddingLeft: 0, paddingRight: 0
		};
	}
	var ss = getComputedStyle(e);
	if (ss == null)
		return {
			borderLeftWidth: 0, borderRightWidth: 0, borderTopWidth: 0, borderBottomWidth: 0,
			marginLeft: 0, marginRight: 0, marginTop: 0, marginBottom: 0,
			paddingTop: 0, paddingBottom: 0, paddingLeft: 0, paddingRight: 0
		};
	var s = {};
	if (ss.width.indexOf('%') > 0 || ss.width == "auto") s.width = e.scrollWidth+'px'; else s.width = ss.width;
	if (ss.height.indexOf('%') > 0 || ss.height == "auto") s.height = e.scrollHeight+'px'; else s.height = ss.height;
	s.borderLeftWidth = _styleBorderValue(ss.borderLeftStyle, ss.borderLeftWidth);
	s.borderRightWidth = _styleBorderValue(ss.borderRightStyle, ss.borderRightWidth);
	s.borderTopWidth = _styleBorderValue(ss.borderTopStyle, ss.borderTopWidth);
	s.borderBottomWidth = _styleBorderValue(ss.borderBottomStyle, ss.borderBottomWidth);
	s.marginLeft = _styleMargin(ss.marginLeft);
	s.marginRight = _styleMargin(ss.marginRight);
	s.marginTop = _styleMargin(ss.marginTop);
	s.marginBottom = _styleMargin(ss.marginBottom);
	s.paddingTop = _stylePadding(ss.paddingTop);
	s.paddingBottom = _stylePadding(ss.paddingBottom);
	s.paddingLeft = _stylePadding(ss.paddingLeft);
	s.paddingRight = _stylePadding(ss.paddingRight);
	return s;
};
function _styleBorderValue(t, s) {
	if (t == "none") return "0px";
	if (s == "medium") return "4px";
	if (s == "thick") return "6px";
	if (s.length == 0) return "0px";
	return s;
};
function _styleMargin(s) {
	if (s == "auto") return "0px";
	if (s.length == 0) return "0px";
	return s;
};
function _stylePadding(s) {
	if (s.length == 0) return "0px";
	return s;
};

function findFrame(name) {
	return _findFrame(window.top, name);
}
function _findFrame(win, name) {
	for (var i = 0; i < win.frames.length; ++i) {
		var f = win.frames[i];
		try {
			if (f.frameElement && (f.frameElement.name == name || f.frameElement.id == name)) return f.frameElement;
		} catch (e) {}
		if (f == win) continue;
		f = _findFrame(f, name);
		if (f) return f;
	}
	return null;
}