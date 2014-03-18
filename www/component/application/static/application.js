if (window == window.top) {
	/**
	 * Handle windows events at application level: list of frames, event when a frame is closed, user activity...
	 */
	window.top.pnapplication = {
		/** Event raised when the user logout, so we can clean some objects that may be on the top window */
		onlogout: new Custom_Event(),
		/** Event raised when the user login */
		onlogin: new Custom_Event(),
		/** Indicates if the user is currently logged or not */
		logged_in: false,
		/** list of windows (private: registerWindow and unregisterWindow must be used) */
		_windows: [],
		/** event when a window/frame is closed. The window is given as parameter to the listeners. */ 
		onwindowclosed: new Custom_Event(),
		/** register a new window
		 * @param {window} w new window/frame 
		 */
		registerWindow: function(w) { 
			if (w.frameElement && w.frameElement.loading_t) {
				if (w.frameElement.unloading_anim) return;
				var start = new Date().getTime();
				var f = function() {
					if (!w.frameElement || !w.frameElement.loading_t) return;
					var now = new Date().getTime();
					if (!w._onload_done || !w._page_ready || !w.layout || w.layout._invalidated.length > 0 || w.layout._last_layout_activity == 0 || now-w.layout._last_layout_activity < 30) {
						if (now-start < 2500) {
							setTimeout(f, 50);
							return;
						}
					}
					if (w.frameElement.loading_anim) {
						animation.stop(w.frameElement.loading_anim);
						w.frameElement.loading_anim = null;
					}
					w.frameElement.unloading_anim = animation.fadeOut(w.frameElement.loading_t, 300, function() {
						if (w.frameElement.loading_t) {
							w.frameElement.loading_t.parentNode.removeChild(w.frameElement.loading_t);
							w.frameElement.loading_t = null;
						}
						w.frameElement.unloading_anim = null;
					});
				};
				f();
			}
			window.top.pnapplication._windows.push(w);
			listenEvent(w,'click',function(ev){
				for (var i = 0; i < window.top.pnapplication._onclick_listeners.length; ++i)
					window.top.pnapplication._onclick_listeners[i][1](ev, w, window.top.pnapplication._onclick_listeners[i][0]);
			});
			listenEvent(w,'mousemove',function(ev){
				if (!window.top.pnapplication) return;
				var w_pos = getAbsoluteCoordinatesRelativeToWindowTop(w);
				var cev = getCompatibleMouseEvent(ev);
				for (var i = 0; i < window.top.pnapplication._onmousemove_listeners.length; ++i) {
					var target = window.top.pnapplication._onmousemove_listeners[i][0];
					var listener = window.top.pnapplication._onmousemove_listeners[i][1];
					var target_pos = getAbsoluteCoordinatesRelativeToWindowTop(target);
					var x = cev.x + w_pos.x - target_pos.x;
					var y = cev.y + w_pos.y - target_pos.y;
					listener(x,y);
				}
			});
			listenEvent(w,'mouseup',function(ev){
				for (var i = 0; i < window.top.pnapplication._onmouseup_listeners.length; ++i)
					window.top.pnapplication._onmouseup_listeners[i][1](ev, w, window.top.pnapplication._onmouseup_listeners[i][0]);
			});
		},
		/** unregister a window (when it is closed)
		 * @param {window} w window/frame which has been closed 
		 */
		unregisterWindow: function(w) {
			if (w.frameElement && typeof animation != 'undefined') {
				setTimeout(function() {
					if (!w || !w.frameElement) return;
					if (w.frameElement.loading_anim) return;
					if (getIFrameWindow(w.frameElement) == null) return; // not anymore in the page
					if (w.frameElement.unloading_anim) {
						animation.stop(w.frameElement.unloading_anim);
						w.frameElement.unloading_anim = null;
					}
					if (w.frameElement.loading_t) {
						if (w.frameElement.loading_t.parentNode) return; // already there
							//w.frameElement.loading_t.parentNode.removeChild(w.frameElement.loading_t);
					}
					var t = document.createElement("TABLE");
					var tr = document.createElement("TR");
					var td = document.createElement("TD");
					td.innerHTML = "<img src='/static/application/loading_100.gif'/>";
					td.style.verticalAlign = "middle";
					td.style.textAlign = "center";
					tr.appendChild(td);
					t.appendChild(tr);
					t.style.position = "absolute";
					t.style.top = w.parent.absoluteTop(w.frameElement)+"px";
					t.style.left = w.parent.absoluteLeft(w.frameElement)+"px";
					t.style.width = w.frameElement.offsetWidth+"px";
					t.style.height = w.frameElement.offsetHeight+"px";
					t.style.visibility = 'hidden';
					t.style.backgroundColor = "white";
					t.loading_frame = w.frameElement;
					w.parent.document.body.appendChild(t);
					w.frameElement.loading_t = t;
					w.frameElement.loading_anim = animation.fadeIn(w.frameElement.loading_t,500,function() {
						if (!w || !w.frameElement || getIFrameWindow(w.frameElement) == null) {
							t.parentNode.removeChild(t);
							if (w.frameElement) {
								w.frameElement.loading_anim = null;
								w.frameElement.loading_t = null;
							}
						} else {
							w.frameElement.loading_anim = null;
							// maximum time to show it: 5s.
							setTimeout(function() {
								if (!w || !w.frameElement || w.frameElement.loading_t != t) return;
								if (w.unloading_anim) return;
								t.parentNode.removeChild(t);
								w.frameElement.loading_t = null;
							}, 5000);
						}
					});
				},1);
			}
			window.top.pnapplication._windows.remove(w);
			for (var i = 0; i < this._onclick_listeners.length; ++i)
				if (this._onclick_listeners[i][0] == w) {
					this._onclick_listeners.splice(i,1);
					i--;
				}
			for (var i = 0; i < this._onmousemove_listeners.length; ++i)
				if (this._onmousemove_listeners[i][0] == w) {
					this._onmousemove_listeners.splice(i,1);
					i--;
				}
			for (var i = 0; i < this._onmouseup_listeners.length; ++i)
				if (this._onmouseup_listeners[i][0] == w) {
					this._onmouseup_listeners.splice(i,1);
					i--;
				}
			window.top.pnapplication.onwindowclosed.fire(w);
		},
		
		/** List of listeners to be called when the user clicks somewhere in the application. (private: registerOnclick and unregisterOnclick must be used) */
		_onclick_listeners: [],
		/** Register the given listener, which will be called when the user clicks somewhere in the application (not only on the window, but on all frames)
		 * @param {window} from_window window containing the listener (used to automatically remove the listener when the window is closed)
		 * @param {Function} listener function to be called
		 */
		registerOnclick: function(from_window, listener) {
			this._onclick_listeners.push([from_window,listener]);
		},
		/** Remove a listener, previously registered by registerOnclick
		 * @param {Function} listener function to be removed, previously registered through registerOnclick 
		 */
		unregisterOnclick: function(listener) {
			for (var i = 0; i < this._onclick_listeners.length; ++i)
				if (this._onclick_listeners[i][1] == listener) {
					this._onclick_listeners.splice(i,1);
					break;
				}
		},
		/** List of listeners to be called when the user moves the mouse somewhere in the application.*/
		_onmousemove_listeners: [],
		/** Register the given listener, which will be called when the user moves the mouse somewhere in the application (not only on the window, but on all frames)
		 * @param {window} from_window window containing the listener (used to automatically remove the listener when the window is closed)
		 * @param {Function} listener function to be called, it takes 2 parameters: <code>x</code> and <code>y</code>
		 */
		registerOnMouseMove: function(from_window, listener) {
			this._onmousemove_listeners.push([from_window,listener]);
		},
		/** Remove a listener, previously registered by registerOnMouseMove
		 * @param {Function} listener function to be removed, previously registered through registerOnMouseMove 
		 */
		unregisterOnMouseMove: function(listener) {
			for (var i = 0; i < this._onmousemove_listeners.length; ++i)
				if (this._onmousemove_listeners[i][1] == listener) {
					this._onmousemove_listeners.splice(i,1);
					break;
				}
		},
		/** List of listeners to be called when the a mouse button goes up somewhere in the application.*/
		_onmouseup_listeners: [],
		/** Register the given listener
		 * @param {window} from_window window containing the listener (used to automatically remove the listener when the window is closed)
		 * @param {Function} listener function to be called
		 */
		registerOnMouseUp: function(from_window, listener) {
			this._onmouseup_listeners.push([from_window,listener]);
		},
		/** Remove a listener, previously registered by registerOnMouseUp
		 * @param {Function} listener function to be removed, previously registered through registerOnMouseUp 
		 */
		unregisterOnMouseUp: function(listener) {
			for (var i = 0; i < this._onmouseup_listeners.length; ++i)
				if (this._onmouseup_listeners[i][1] == listener) {
					this._onmouseup_listeners.splice(i,1);
					break;
				}
		},
		
		/** Event when the whole application is closing */
		onclose: new Custom_Event(),
		/** Called when the top window is closing, meaning the application */
		closeWindow: function() {
			window.top.pnapplication.onclose.fire();
		},
		/** time of the last activity of the user */
		last_activity: new Date().getTime(),
		/** signals the user is active: fire onactivity event on each window */
		userIsActive: function() {
			for (var i = 0; i < window.top.pnapplication._windows.length; ++i)
				if (window.top.pnapplication._windows[i].pnapplication) window.top.pnapplication._windows[i].pnapplication.onactivity.fire();
			window.top.pnapplication.last_activity = new Date().getTime();
		},
		/** check if the user is not inactive since long time: if this is the case, automatically logout */
		checkInactivity: function() {
			var time = new Date().getTime();
			time -= window.top.pnapplication.last_activity;
			for (var i = 0; i < window.top.pnapplication._windows.length; ++i) {
				if (window.top.pnapplication._windows[i].closed) {
					window.top.pnapplication.unregisterWindow(window.top.pnapplication._windows[i]);
					window.top.pnapplication.checkInactivity();
					return;
				}
				if (window.top.pnapplication._windows[i].pnapplication)
					for (var j = 0; j < window.top.pnapplication._windows[i].pnapplication._inactivity_listeners.length; ++j) {
						var il = window.top.pnapplication._windows[i].pnapplication._inactivity_listeners[j];
						if (il.time <= time)
							il.listener();
					}
			}
		}
	};
} else if (typeof Custom_Event != 'undefined'){
	/**
	 * Handle events on the current window, transfered to the top window
	 */
	window.pnapplication = {
		/** event fired when user activity is detected */
		onactivity: new Custom_Event(),
		/** event fired when the current window is closing */
		onclose: new Custom_Event(),
		/** indicates the current window is closing */
		closeWindow: function() {
			this.onclose.fire();
			window.top.pnapplication.unregisterWindow(window);
		},
		/** Internal list of {time,function} to call when the user is inactive */
		_inactivity_listeners: [],
		/**
		 * Register a listener to be called when the user is inactive for the given amount of time.
		 * @param {Number} inactivity_time time in milliseconds of the inactivity
		 * @param {Function} listener function to be called
		 */
		addInactivityListener: function(inactivity_time, listener) {
			this._inactivity_listeners.push({time:inactivity_time,listener:listener});
		},
		onmouseout: new Custom_Event()
	};
	window.onmouseout = function(ev) { window.pnapplication.onmouseout.fire(ev); };
	window.top.pnapplication.registerWindow(window);
}

/**
 * Initialize: listen click, mousemove, beforeunload
 */
function initPNApplication() {
	if (typeof listenEvent == 'undefined' || window.top.frames.length == 0)
		setTimeout(initPNApplication, 100);
	else {
		var listener = function() {
			if (!window || !window.top || !window.top.pnapplication) return;
			window.top.pnapplication.userIsActive();
		};
		listenEvent(window,'click',listener);
		listenEvent(window,'mousemove',listener);
		var closeRaised = false;
		if (window.frameElement) {
			var prev = window.frameElement.onunload; 
			window.frameElement.onunload = function(ev) {
				if (!closeRaised && window.pnapplication) window.pnapplication.closeWindow();
				if (prev) prev(ev);
			};
		}
		window.onunload = function() {
			if (!closeRaised && window.pnapplication) window.pnapplication.closeWindow();
		};
		window.onbeforeunload = function() {
			if (!closeRaised && window.pnapplication) window.pnapplication.closeWindow();
		};
		if (window==window.top)
			setInterval(window.pnapplication.checkInactivity, 2000);
	}
};
initPNApplication();


// override add_javascript and add_stylesheet
//window._add_javascript_original = window.add_javascript;
//window.add_javascript = function(url, onload) {
//	if (!window.top._loading_application_status) {
//		if (window.top.StatusMessage) {
//			window.top._loading_application_status = new window.top.StatusMessage(window.top.Status_TYPE_PROCESSING, "Loading...");
//			window.top._loading_application_nb = 0;
//		} else {
//			window._add_javascript_original(url, onload);
//			return;
//		}
//	}
//	var p = new URL(url).path;
//	var load = !_scripts_loaded.contains(p);
//	if (load) {
//		window.top._loading_application_nb++;
//		if (window.top._loading_application_nb == 1)
//			window.top.status_manager.add_status(window.top._loading_application_status);
//	}
//	window._add_javascript_original(url, function() {
//		if (onload) onload();
//		if (load) {
//			window.top._loading_application_nb--;
//			if (window.top._loading_application_nb == 0)
//				window.top.status_manager.remove_status(window.top._loading_application_status);
//		}
//	});
//};
