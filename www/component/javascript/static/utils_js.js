/**
 * Clone an object structure 
 * @param {Object} o the object to clone
 * @param {Number} recursive_depth maximum depth of clone
 * @returns {Object} the clone
 */
function objectCopy(o, recursive_depth) {
	if (o == null) return null;
	if (typeof o == 'string') return ""+o;
	if (typeof o == 'number') return o;
	var c = new Object();
	for (var attr in o) {
		var value = o[attr];
		if (!recursive_depth) { c[attr] = value; continue; }
		if (value == null) { c[attr] = null; continue; }
		if (typeof value == 'object') {
			if (value instanceof Date || getObjectClassName(value) == "Date")
				c[attr] = new Date(value.getTime());
			else if (value instanceof Array || getObjectClassName(value) == "Array") {
				c[attr] = [];
				for (var i = 0; i < value.length; ++i)
					c[attr].push(valueCopy(value[i], recursive_depth-1));
			} else {
				c[attr] = objectCopy(value, recursive_depth-1);
			}
		} else
			c[attr] = value;
	}
	return c;
}
/**
 * Copy the given value
 * @param {Object|Number|String|Array} value the value to copy
 * @param {Number} obj_depth maximum depth in objects to copy
 * @returns {Object|Number|String|Array} the copy
 */
function valueCopy(value, obj_depth) {
	if (value == null) return null;
	if (typeof value == 'object') {
		if (value instanceof Date || getObjectClassName(value) == "Date")
			return new Date(value.getTime());
		if (value instanceof Array || getObjectClassName(value) == "Array") {
			var a = [];
			for (var i = 0; i < value.length; ++i)
				if (obj_depth > 0)
					a.push(valueCopy(value[i], obj_depth-1));
				else
					a.push(value[i]);
			return a;
		}
		return objectCopy(value, obj_depth);
	}
	return value;
}

/**
 * Merge 2 objects: put each value of add into o
 * @param {Object} o object
 * @param {Object} add object to add into o
 * @returns {Object} o
 */
function objectMerge(o, add) {
	for (var name in add) o[name] = add[name];
	return o;
}

/**
 * Check if 2 objects are the same
 * @param {Object} o1 object 1
 * @param {Object} o2 object 2
 * @param {Array} done internally used to avoid infinite recursivity
 * @returns {Boolean} true if they are the same
 */
function objectEquals(o1, o2, done) {
	if (typeof o1 != typeof o2) return false;
	if (typeof o1 != 'object') return o1 == o2;
	if (o1 == null) return o2 == null;
	var c1 = getObjectClassName(o1);
	var c2 = getObjectClassName(o2);
	if (c1 != c2) return false;
	if (!done) done = [];
	if (done.contains(o1) || done.contains(o2)) return o1 == o2;
	done.push(o1); done.push(o2);
	if (c1 == "Array") return arrayEquals(o1, o2, done);
	if (c1 == "Date") return o1.getTime() == o2.getTime();
	for (var name in o1) {
		var found = false;
		for (var name2 in o2) if (name2 == name) { found = true; break; }
		if (!found) return false;
	}
	for (var name in o2) {
		var found = false;
		for (var name2 in o1) if (name2 == name) { found = true; break; }
		if (!found) return false;
	}
	for (var name in o1) {
		var v1 = o1[name];
		var v2 = o2[name];
		if (!objectEquals(v1, v2, done)) return false;
	}
	return true;
}
/**
 * Check if 2 arrays are the same
 * @param {Array} a1 array 1
 * @param {Array} a2 array 2
 * @param {Array} done internally used to avoid infinite recursivity
 * @returns {Boolean} true if they are the same
 */
function arrayEquals(a1, a2, done) {
	if (a1.length != a2.length) return false;
	for (var i = 0; i < a1.length; ++i)
		if (!objectEquals(a1[i], a2[i], done)) return false;
	return true;
}

/**
 * 2 arrays are equivalent if they contain the same elements but not necessarily in the same order
 * @param {Array} a1 array 1
 * @param {Array} a2 array 2
 * @returns {Boolean} true if they are equivalent
 */
function arrayEquivalent(a1, a2) {
	if (a1 == null) a1 = [];
	if (a2 == null) a2 = [];
	if (a1.length != a2.length) return false;
	var to_match = [];
	for (var i = 0; i < a2.length; ++i) to_match.push(a2[i]);
	for (var i = 0; i < a1.length; ++i) {
		var found = false;
		for (var j = 0; j < to_match.length; ++j) {
			if (a1[i] == to_match[j]) {
				to_match.splice(j,1);
				found = true;
				break;
			}
		}
		if (!found) return false;
	}
	return true;
}
/**
 * Duplicates an array
 * @param {Array} a the array to duplicate
 * @param {Function} to_copy if specified, the function is used to make a copy of an element.
 * @returns {Array} the duplicate
 */
function arrayCopy(a, to_copy) {
	var c = [];
	for (var i = 0; i < a.length; ++i)
		c.push(to_copy ? to_copy(a[i]) : a[i]);
	return c;
}

/**
 * Can be used to display the content of an object
 * @param {Object} o object
 * @param {Number} indent current indentation, used with recursivity
 * @returns {String} the string
 */
function debugObjectToString(o, indent) {
	if (!indent) indent = "";
	if (typeof o == 'object') {
		if (o instanceof Date)
			return o.toString();
		var s = "{\r\n";
		for (var name in o) {
			s += indent+"    "+name+":"+debugObjectToString(o[name], indent+"    ")+",\r\n";
		}
		s += "}";
		return s;
	}
	return ""+o;
}
