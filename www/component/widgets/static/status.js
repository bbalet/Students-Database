window.Status_TYPE_INFO = 0;
window.Status_TYPE_ERROR = 1;
window.Status_TYPE_WARNING = 2;
window.Status_TYPE_PROCESSING = 3;
window.Status_TYPE_OK = 4;
window.Status_TYPE_ERROR_NOICON = 5;

function StatusMessage(type,message,actions,timeout) {
	this.id = generateID();
	this.type = type;
	this.message = message;
	this.actions = actions;
	this.timeout = timeout;
}
/** Status message for an error, including an exception stack trace if given
 * @param {Exception} err the error, or null if no exception
 * @param {String} message error message
 * @param {Number} timeout if given, the message will automatically disappear after this timeout specified in milliseconds
 */
function StatusMessageError(err, message, timeout) {
	this.id = generateID();
	this.type = Status_TYPE_ERROR;
	this.timeout = timeout;
	this.message = (message != null ? " "+message : "");
	if (err != null)
		this.message += ": "+err.message;
	this.actions = [];
	if (timeout) this.actions.push({action:"popup"});
	this.actions.push({action:"close"});
	if (err != null) {
		if (err.stack)
			this.stack = err.stack;
		else if(err.stacktrace)
			this.stack = err.stacktrace;
		else {
			var s = "";
		    var currentFunction = arguments.callee.caller;
		    while (currentFunction) {
		      var fn = currentFunction.toString();
		      var fname = fn.substring(0, fn.indexOf('{'));;
		      s += fname+"\r\n";
		      currentFunction = currentFunction.caller;
		    }
		    this.stack = s;
		}
		if (this.stack != null) {
			this.actions.splice(0,0,{text:"Show stack trace",action:"show_stack_trace"});
		}
	}
}


function StatusManager() {
	this.status = [];
	this.status_id_counter = 0;
	this.status_ui = null;
	
	this.add_status= function(status) {
		this.status.push(status);
		this.status_ui.update(this.status);
		return status;
	};
	this.remove_status= function(status) {
		if (typeof status == 'string' || typeof status == 'number') status = this.get_status(status);
		if (status == null) return;
		for (var i = 0; i < this.status.length; ++i)
			if (this.status[i] == status) {
				this.status.splice(i,1);
				break;
			}
		this.status_ui.update(this.status);
	};
	this.update_status= function(status) {
		this.status_ui.update_status(status);
	};
	this.get_status= function(id) {
		for (var i = 0; i < this.status.length; ++i)
			if (this.status[i].id == id)
				return this.status[i];
		return null;
	};
}