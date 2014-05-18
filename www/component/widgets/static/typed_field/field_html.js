/* #depends[typed_field.js] */
function field_html(data,editable,config) {
	typed_field.call(this, data, editable, config);
}
field_html.prototype = new typed_field();
field_html.prototype.constructor = field_html;		
field_html.prototype._create = function(data) {
	if (typeof data == 'string')
		this.element.innerHTML = data;
	else if (data != null)
		this.element.appendChild(data);
	this._setData = function(data) {
		while (this.element.childNodes.length > 0) this.element.removeChild(this.element.childNodes[0]);
		if (typeof data == 'string')
			this.element.innerHTML = data;
		else if (data != null)
			this.element.appendChild(data);
	};
};
