/* #depends[typed_field.js] */
/** Time field: if editable, it will be a text input, else only a simple text node
 * @constructor
 * @param config can contain: <code>can_be_null</code>, <code>is_duration</code>
 */
function field_time(data,editable,config) {
	this._strtotime = config && config.is_duration ? parseDurationStringToMinutes : parseTimeStringToMinutes;
	this._timetostr = config && config.is_duration ? getDurationStringFromMinutes : getTimeStringFromMinutes;
	if (data == null) data = "";
	if (typeof data == 'number') data = this._timetostr(data);
	else data = this._timetostr(this._strtotime(data));
	typed_field.call(this, data, editable, config);
}
field_time.prototype = new typed_field();
field_time.prototype.constructor = field_time;		
field_time.prototype.canBeNull = function() { return this.config && this.config.can_be_null; };
field_time.prototype.compare = function(v1,v2) {
	if (v1 == null) return v2 == null ? 0 : 1;
	if (v2 == null) return -1;
	if (typeof v1 == 'string')
		v1 = this._strtotime(v1);
	if (typeof v2 == 'string')
		v2 = this._strtotime(v2);
	if (v1 < v2) return -1;
	if (v1 > v2) return 1;
	return 0;
};
field_time.prototype.exportCell = function(cell) {
	var t = this.getCurrentData();
	if (t == null)
		cell.value = "";
	else {
		cell.value = t;
		cell.format = "time";
	}
};
field_time.prototype._create = function(data) {
	if (this.editable) {
		var t=this;
		var input = document.createElement("INPUT");
		input.type = "text";
		input.maxlength = 5;
		if (typeof data == 'number') data = this._timetostr(data);
		if (data) input.value = data;
		input.style.margin = "0px";
		input.style.padding = "0px";
		require("input_utils.js",function(){inputAutoresize(input);});
		var getTimeFromInput = function() {
			if (input.value.length == 0) {
				if (t.config && t.config.can_be_null) return null;
				return 0;
			}
			return t._strtotime(input.value);
		};
		input.onblur = function(ev) {
			input.value = t._timetostr(getTimeFromInput());
			t._datachange();
		};
		listenEvent(input, 'focus', function() { t.onfocus.fire(); });
		this.focus = function() { input.focus(); };
		this.element.appendChild(input);
		this._getEditedData = function() {
			return this._timetostr(getTimeFromInput());
		};
		this.getCurrentMinutes = function() {
			return getTimeFromInput();
		};
		this._setData = function(data) {
			if (data == null) input.value = "";
			else {
				if (typeof data == 'number') data = this._timetostr(data);
				else data = this._timetostr(this._strtotime(data));
				input.value = data;
			}
			return data;
		};
		this.signalError = function(error) {
			this.error = error;
			input.style.border = error ? "1px solid red" : "";
		};
	} else {
		this.element.appendChild(this.text = document.createTextNode(data));
		this._setData = function(data) {
			var text;
			if (data == null) text = "";
			else if (typeof data == 'number') text = data = this._timetostr(data);
			else text = data = this._timetostr(this._strtotime(data));
			this.text.nodeValue = text;
			return data;
		};
		this.getCurrentMinutes = function() {
			var s = this.text.nodeValue;
			if (s.length == 0) return this.config && this.config.can_be_null ? null : 0;
			return this._strtotime(s);
		};
		this.signalError = function(error) {
			this.error = error;
			this.element.style.color = error ? "red" : "";
		};
	}
};