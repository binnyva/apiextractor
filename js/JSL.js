(function() {
	window.JSL = {
		"_getUserArgs": function(arguments) {
			var user_args = [];
			for (var i=1; i<arguments.length; i++) { //We dont need the first
				user_args.push(arguments[i]);
			}
			return user_args;
		},
		
		/// If a string is passed in for the function, make a function for it (a handy shortcut)
		/// Thanks, jQuery!
		"_makeFunc": function(func, args) {
			if ( typeof func == "string" )
				func = eval("false||function("+ args +"){return " + func + "}");
			return func;
		}
	}
})();

//jsl is a global shortcut for JSL.* functions.
var jsl = window.jsl = function() {
	if(typeof arguments[0] == "number") return JSL.number.apply(this, arguments);
	else if(typeof arguments[0] == "object") return JSL.array.apply(this, arguments);
	else return JSL.dom.apply(this, arguments);
};
if(typeof $ == "undefined") window.$ = jsl;
/**
 * Class : JSL.array
 * Handles all the array related functions.
 */
(function() {
	/// Constructor
	function _array_init(arr) {
		this.array = arr;
		return this;
	}
	
	_array_init.prototype = {
		/**
		 * This function will loop thru the array and call the given function for each element. 
		 *		The values returned by the user defined function will be used to create a new 
		 *		array/object that is returned by the function at the end. 3 arguments will be passed to
		 *		the user defined function - 
		 *			current_item - The value of the current element
		 *			index - The index we are currently at
		 *			full_array - The entire array
		 *			user_args - The data provided by the user, if any.
		 *
		 * Argument:
		 *		func - The user function.
		 *		user_args - Custom data passed into the function [OPTIONAL]
		 * Example:
		 *	<pre>var result = JSL.array([4,10,65]).map(function(current_item) {
		 *		return current_item+1;
		 *	});</pre>
		 *	Result will be [5, 11, 66]
		 *
		 *	<pre>var result = JSL.array([4,10,65]).map(function(current_item, x) {
		 *		return current_item + x;
		 *	}, 5);</pre>
		 *	Result will be [9, 15, 70] - 5, the second argument of the map function is passed into the 1st argument function.
		 */
		"map" : function() {
			var func = arguments[0];
			func = JSL._makeFunc(func,"ele,i,all");
			var is_array = this.isArray();
			
			var user_args = JSL._getUserArgs(arguments);
			
			var result = (is_array) ? [] : {};
			for(var index in this.array) {
				var new_value = func.apply(this, [this.array[index], index, this.array].concat(user_args));
				
				if(is_array) result.push(new_value);
				else result[index] = new_value;
			}
			
			this.array = result;
			return this;
		},
		

		/**
		 * This function will loop thru the array and call the given function for each element. 3 arguments will be passed to
		 *		the user defined function - 
		 *			current_item - The value of the current element
		 *			index - The index we are currently at
		 *			full_array - The entire array
		 *			user_args - The data provided by the user, if any, as an array
		 * 		This is basically an alias of map()
		 *
		 * Argument:
		 *		func - The user function.
		 *		user_args - Custom data passed into the function [OPTIONAL]
		 * Example:
		 *	JSL.array(document.getElementsByTagName("a")).each(function(ele) {
		 *		ele.onclick = false; //Disable all links
		 *	});
		 */
		"each" : function() {
			this.map.apply(this, arguments);
			return this;
		},
		
		/**
		 * Function will loop thru the array and collects all the element for which the user function returned a true value.
		 *		This array will be returned as a JSL.array object.
		 * Argument:
		 *		func - The user function.
		 *		user_args - Custom data passed into the function [OPTIONAL].
		 * Example:
		 *	JSL.array(["hello", "world", "virus", "worm", "virus", "trojan"]).filter(function(current_item, i, full_array, bad_word) {
		 *		return current_item !== bad_word; //Removes all the elements that don't say 'virus'
		 *	}, "virus"); //"virus" is the bad_word
		 */
		 "filter" : function() {
			var func = arguments[0];
			func = JSL._makeFunc(func,"ele,i,all");
			var is_array = this.isArray();
			
			var user_args = JSL._getUserArgs(arguments);
			
			var result = (is_array) ? [] : {};
			
			for(var index in this.array) {
				var return_value = func.apply(this, [this.array[index], index, this.array].concat(user_args));
				if(return_value) {
					if(is_array) result.push(this.array[index]);
					else result[index] = this.array[index];
				}
			}
			this.array = result;
			return this;
		},
		
		/**
		 * Searches thru the array until the given element is found - and returns it index.
		 * Return : -1 if searched element was not found - or it will return the index of the first found element.
		 * Example:
		 *		JSL.array([2,6,19,34]).indexOf(19); // Returns 2
		 *		JSL.array([2,6,19,34]).indexOf(17); // Returns -1
		 */
		"indexOf": function(value) {
			for(var index in this.array) {
				if (this.array[index] === value) return index;
			}
			return -1;
		},
		
		
		/**
		 * Apply a function simultaneously against two values of the array (from left-to-right) as to reduce it to a single value.
		 * Taken from : http://developer.mozilla.org/en/docs/Core_JavaScript_1.5_Reference:Objects:Array:reduce
		 * Return: The Result
		 * Example:
		 * 		//Find the Total of all elements
		 * 		JSL.array([0, 1, 2, 3]).reduce(function(a, b){ return a + b; }); // == 6
		 */
		"reduce": function(func, initial_value) {
			var len = this.array.length;

			func = JSL._makeFunc(func,"a,b");

			//No value to return if no initial value and an empty array
			if (len == 0 && arguments.length == 1) throw new TypeError();
			
			var i = 0;
			if (arguments.length >= 2) {
				var return_value = arguments[1];
			} else {
				do {
					if (i in this.array) {
						return_value = this.array[i++];
						break;
					}
				
					//If array contains no values, no initial value to return
					if (++i >= len) throw new TypeError();
				} while (true);
			}
			
			for (; i < len; i++) {
				if (i in this.array)
					return_value = func.call(null, return_value, this.array[i], i, this.array);
			}
			
			return return_value;
		},
		
		/**
		 * Returns all the elements in the selected array that matches the provided regular expression
		 * Argument:
		 *		regexp - The regular expression that should be matched against all the elements in the array.
		 * Example:
		 *		JSL.array(['hello', 'world', 'hot', 'water']).grep(/^w/);
		 *		//Returns ['world', 'water']
		 */
		"grep": function(regexp) {
			return this.filter("ele.match("+regexp+");");
		},
		
		/**
		 * Get the number of elements currently seleted.
		 * Return:
		 * 		length(Integer) - The size of the currently selected array
		 */
		"getSize": function() {
			return this.array.length;
		},
		
		/**
		 * Returns the base element - in this case, an array.
		 */
		"get" : function() {
			return this.array;
		},
		
		/**
		 * Checks wether the current array is an Numerical Array(List)  - if so return true. Objects return false.  
		 * Return: Boolean - true if its an numerical array(list) and false if its anything else
		 */
		"isArray": function() {
			var arr_obj = this.array;
			return (arr_obj && (arr_obj.propertyIsEnumerable && !(arr_obj.propertyIsEnumerable('length'))) 
				&& typeof arr_obj === 'object' && typeof arr_obj.length === 'number');
		}
	}
	
	
	window.JSL["array"] = function() {
		//A quick hack to make this possible - JSL.array(3,4,5).each();
		var args = arguments;
		if(arguments.length == 1) args = arguments[0];
		return new _array_init(args);
	}
})();
(function() {
	function _dom_init(arguments) {
		var selected_elements = [];
		for(var i=0, args_length = arguments.length; i<args_length; i++) {
			var arg = arguments[i];
			var new_eles;

			if(typeof arg == "string") { //The arg is a selector
				this.selector[i] = arg;
				new_eles = this._select(arg);
			
			} else { //The argument is a DOM Node
				new_eles = arg;
			}
			selected_elements = selected_elements.concat(new_eles);
		}
		
		var elements_count = selected_elements.length;
		this.nodes = JSL.array(selected_elements);

		//If is the user gave us a specific dom node, we must return just that - with our additions
		if(arguments.length == 1 && elements_count == 1) {
			var arg_type = this._getType(arguments[0]);
			
			if(arg_type == 'node' || arg_type == 'id') {
				var ele = selected_elements[0]; //If an ID was specified, it must be available thru the get as a single element.
				for(var i in this) {
					if(ele[i]) { //If the function already exists, back it up first.
						ele["_" + i] = ele[i];
					}
					ele[i] = this[i];
				}
				this.single = ele;

				return ele; //Returns the Element - with all our additions
			}
		}
		//Get all the array function in the 'this' element itself - stuff like map(), each
		for(var i in this.nodes) {
			this[i] = this.nodes[i];
		}
		
		return this;
	}
	
	_dom_init.prototype = {
		"selector":[],
		"nodes":[],
		"single":false,
		"valid_tags":["a","abbr","acronym","address","applet","area","b","base","basefont","bdo",
						"big","blockquote","body","br","button","caption","center","cite","code",
						"col","colgroup","dd","del","dir","div","dfn","dl","dt","em","fieldset",
						"font","form","frame","frameset","h1","h2","h3","h4","h5","h6","head","hr",
						"html","i","iframe","img","input","ins","isindex","kbd","label","legend",
						"li","link","map","menu","meta","noframes","noscript","object","ol","optgroup",
						"option","p","param","pre","q","s","samp","script","select","small","span",
						"strike","strong","style","sub","sup","table","tbody","td","textarea","tfoot",
						"th","thead","title","tr","tt","u","ul","var"],
		
		///////////////////////////////// Styles /////////////////////////
		/**
		 * Adds the given class to the currently selected items
		 * Example:
		 *	JSL.dom("a").addClass("external");
		 */
		"addClass": function(class_name) {
			var self = this;
			this.nodes.each(function(ele) {
				self._class.add(ele,class_name);
			});
			return this;
		},
		/**
		 * Removes the said class from all the element the selected list
		 */
		"removeClass": function(class_name) {
			var self = this;
			this.nodes.each(function(ele) {
				self._class.remove(ele,class_name);
			});
			return this;
		},
		
		/**
		 * Get the X,Y coordinates of the given element
		 * Taken from http://txt.binnyva.com/2007/06/find-elements-position-using-javascript/
		 */
		"getPosition": function() {
			var ele = this.nodes.array[0]; //You can have the position for only 1 element.
			var leftx = topy = 0;
			if (ele.offsetParent) {
				leftx = ele.offsetLeft;
				topy = ele.offsetTop;
				while (ele = ele.offsetParent) {
					leftx += ele.offsetLeft;
					topy += ele.offsetTop;
				}
			}

			return {"left":leftx,"x":leftx,"top":topy,"y":topy};
		},
		
		/**
		 * Get the specified style of the active element
		 * Inspired by http://www.quirksmode.org/dom/getstyles.html
		 */
		"getStyle": function(property) {
			var ele = this.nodes.array[0];
			if (ele.currentStyle) {
				var alt_property_name = property.replace(/\-(\w)/g,function(m,c){return c.toUpperCase();});//background-color becomes backgroundColor
				var value = ele.currentStyle[property]||ele.currentStyle[alt_property_name];
			
			} else if (window.getComputedStyle) {
				property = property.replace(/([A-Z])/g,"-$1").toLowerCase();//backgroundColor becomes background-color

				var value = document.defaultView.getComputedStyle(ele,null).getPropertyValue(property);
			}
			
			//Some properties are special cases
			if(property == "opacity" && ele.filter) value = (parseFloat( ele.filter.match(/opacity\=([^)]*)/)[1] ) / 100);
			else if(property == "width" && isNaN(value)) value = ele.clientWidth || ele.offsetWidth;
			else if(property == "height" && isNaN(value)) value = ele.clientHeight || ele.offsetHeight;
			
			//Remove the 'px' from the end of values
			if(typeof value == "string" && value.match(/^\d+px$/)) {
				value = Number(value.replace(/px/, ""));
			}
			
			return value;
		},
		
		/**
		 * Set the style of the element.
		 * Example:
		 *	JSL.dom("element").setStyle("position", "absolute");
		 *		OR
		 *	JSL.dom("element").setStyle({
		 *				"position":"absolute",
		 *				"top":50px,
		 *				"left":100px
		 *			});
		 */
		"setStyle": function(property, value) {
			var all_styles = {};
			if(typeof property === "string") all_styles[property] = value;
			else all_styles = property;
			
			this.nodes.each(function(ele) {
				JSL.array(all_styles).each(function(value, property, all, ele) {
					property = property.replace(/\-(\w)/g,function(m,c){return c.toUpperCase();});//background-color becomes backgroundColor
					
					//Append a 'px' at the end of all numbers.
					if(value && value.constructor == Number) {
						var non_px = JSL.array(['zIindex','fontWeight','opacity','zoom','lineHeight']); //...except for these ones
						if( non_px.indexOf(property) == -1 ) {
							value += 'px';
						}
					}
					
					if(property == "opacity") {
						ele.style.opacity = value;
						ele.style.filter = 'alpha(opacity='+value+')';	//IE
					} else {
						ele.style[property] = value;
					}
				},ele);
			});
			return this;
		},
		
		/**
		 * Shows all currently selected elements.
		 * Arguments:
		 *		display[OPTIONAL] - could be 'hidden', 'visible', 'inline' or 'block'. Defaults to 'block'
		 * Example:
		 *	JSL.dom("example").show()
		 */
		"show" : function(display) {
			this.nodes.each(function(ele) {
				if(display === "hidden") ele.style.visibility = "hidden";
				else if(display === "visible") ele.style.visibility = "visible";
				else if(display === "inline") ele.style.display = "inline";
				else ele.style.display = "block";
			});
			return this;
		},
		
		/**
		 * Hides all the currently selected elements
		 * Example:
		 *	JSL.dom("example").hide()
		 */
		"hide" : function() {
			this.nodes.each(function(ele) {
				ele.style.display = "none";
			});
			return this;
		},
		
		///////////////////////////////// Events /////////////////////////
		"on" : function(event, func) {
			this.nodes.each(function(ele) {
				JSL.event().add(ele, event, func);
			});
			return this;
		},
		"click":function(func){return this.on("click", func);},
		"load":function(func){return this.on("load", func);},
		
		
		//////////////// The Privates //////////////////
		
		/// CSS Selectors - Taken from http://www.openjs.com/scripts/dom/css_selector/css_selector.js
		"_select" : function(all_selectors) {
			//Find what the selector is...
			var type = this._getType(all_selectors);
			if(type === "id") { //Superfast processing for IDs
				var ele = document.getElementById(all_selectors.replace("#",""));

				//To make sure we get the non existant elements
				if(ele) return [ele];
				else return [];
			}

			var selected = new Array();
			if(!document.getElementsByTagName) return selected;
			all_selectors = all_selectors.replace(/\s*([^\w\.\#])\s*/g,"$1");//Remove the 'beutification' spaces
			var selectors = all_selectors.split(",");
			// Grab all of the tagName elements within current context	
			var getElements = function(context,tag) {
				if (!tag) tag = '*';

				// Get elements matching tag, filter them for class selector
				var found = new Array;
				for (var a=0,len=context.length; con=context[a],a<len; a++) {
					var eles;
					if (tag == '*') eles = (con.all) ? con.all : con.getElementsByTagName("*");
					else eles = con.getElementsByTagName(tag);
		
					for(var b=0,leng=eles.length;b<leng; b++) found.push(eles[b]);
				}
				return found;
			}
		
			COMMA:
			for(var i=0,len1=selectors.length; selector=selectors[i],i<len1; i++) {
				var context = new Array(document);
				var inheriters = selector.split(" ");
		
				SPACE:
				for(var j=0,len2=inheriters.length; element=inheriters[j],j<len2;j++) {
					//This part is to make sure that it is not part of a CSS3 Selector
					var left_bracket = element.indexOf("[");
					var right_bracket = element.indexOf("]");
					var pos = element.indexOf("#");//ID
					if(pos+1 && !(pos>left_bracket && pos<right_bracket)) {
						var parts = element.split("#");
						var tag = parts[0];
						var id = parts[1];
						var ele = document.getElementById(id);
						if(!ele || (tag && ele.nodeName.toLowerCase() != tag)) { //Specified element not found
							continue COMMA;
						}
						context = new Array(ele);
						continue SPACE;
					}
		
					pos = element.indexOf(".");//Class
					if(pos+1 && !(pos>left_bracket&&pos<right_bracket)) {
						var parts = element.split('.');
						var tag = parts[0];
						var class_name = parts[1];
		
						var found = getElements(context,tag);
						context = new Array;
						for (var l=0,len=found.length; fnd=found[l],l<len; l++) {
							if(fnd.className && fnd.className.match(new RegExp('(^|\s)'+class_name+'(\s|$)'))) context.push(fnd);
						}
						continue SPACE;
					}
		
					if(element.indexOf('[')+1) {//If the char '[' appears, that means it needs CSS 3 parsing
						// Code to deal with attribute selectors
						if (element.match(/^(\w*)\[(\w+)([=~\|\^\$\*]?)=?['"]?([^\]'"]*)['"]?\]$/)) {
							var tag = RegExp.$1;
							var attr = RegExp.$2;
							var operator = RegExp.$3;
							var value = RegExp.$4;
						}
						var found = getElements(context,tag);

						context = new Array;
						for (var l=0,len=found.length; fnd=found[l],l<len; l++) {
							if(attr === "class") var attr_value = fnd.className; //IE will not allow getAttribute("class")
							else var attr_value = fnd.getAttribute(attr);

							if(attr_value) {
								if(operator=='=' && attr_value != value) continue;
								else if(operator=='~' && !attr_value.match(new RegExp('(^|\\s)'+value+'(\\s|$)'))) continue;
								else if(operator=='|' && !attr_value.match(new RegExp('^'+value+'-?'))) continue;
								else if(operator=='^' && attr_value.indexOf(value)!=0) continue;
								else if(operator=='$' && attr_value.lastIndexOf(value)!=(attr_value.length-value.length)) continue;
								else if(operator=='*' && !(attr_value.indexOf(value)+1)) continue;
								else if(!attr_value) continue;
								context.push(fnd);
							}
						}
		
						continue SPACE;
					}
		
					//Tag selectors - no class or id specified.
					var found = getElements(context,element);
					context = found;
				}
				for (var o=0,len=context.length;o<len; o++) selected.push(context[o]);
			}
			
			return selected;
		},

		/// Returns the base element
		"get": function() {
			if(this.single) return this.single;
			else return this.nodes.get();
		},

		/// Class manipulations - taken from http://www.openjs.com/scripts/dom/class_manipulation.php
		"_class": {
			"add":function(ele,cls) {
				if (!this.has(ele,cls)) ele.className += " "+cls;
			},
			"has":function(ele, cls) {
				return ele.className.match(new RegExp('(\\s|^)'+cls+'(\\s|$)'));
			},
			"remove":function(ele,cls) {
				if (this.has(ele,cls)) {
					var reg = new RegExp('(\\s|^)'+cls+'(\\s|$)');
					ele.className=ele.className.replace(reg,' ');
				}
			}
		},
		
		/// Takes a string and tries to guess wether it is a 'css selector' or a 'tag' or an 'id'. Returns the result.
		"_getType":function(str) {
			if(typeof str == "string") {
				if(str.indexOf("#") > 0 || str.indexOf(".")+1 || str.indexOf(" ")+1 || str.indexOf(",")+1 || str.indexOf("[")+1 || str.indexOf("*")+1) return "css"; //A CSS Selector
				else if(JSL.array(this.valid_tags).indexOf(str)+1) return "tag"; //Its a tag.
				else return "id"; //An id, perhabs?
			} else {
				return "node";
			}
		}
	}

	window.JSL["dom"] = function() {
		return new _dom_init(arguments);
	}
})();
(function() {
	///////////////////////////////////////////// Event Part ///////////////////////////////////////////
	function _event_init(e) {
		this.event = e || window.event;
		return this;
	}
	
	_event_init.prototype = {
		/**
		 * The famous addEvent function. But calling it in this format is not the preffered method.
		 * Use the DOM interface to make the call.
		 * Example:
		 * 		JSL.dom("#ele").on("mouseover", function(e){ alert("Hello World"); });
		 */
		"add" :function(ele,type,func,capture) {
			function _makeCallback(e){
				var ele = JSL.event(e).getTarget() || document;
				func.call(ele,e);
			}
			
			capture = capture||true;
			if(ele.addEventListener) {
				ele.addEventListener(type, _makeCallback, capture);
				return true;
				
			} else if(ele.attachEvent) {
				return ele.attachEvent('on' + type, _makeCallback);
			} else {
				ele['on' + type] = _makeCallback;
			}
		},

		/**
		 * Stop an event from further propogation.
		 * Taken from http://www.openjs.com/articles/prevent_default_action/
		 * Example:
		 *	JSL.event(e).stop();
		 */
		"stop": function(){
			var e = this.event;
			e.cancelBubble = true;
			e.returnValue = false;
			if(e.stopPropagation) {
				e.stopPropagation();
				e.preventDefault();
			}
			return false;
		},

		/**
		 * Get the target of the current event
		 * Example:
		 *	var ele = JSL.event(e).getTarget();
		 */
		"getTarget": function() {
			var element;
			var e = this.event;
			if(e.target) element=e.target;
			else if(e.srcElement) element=e.srcElement;
			
			if(element && element.nodeType==3) element=element.parentNode; //Safari Bug fix
			return element;
		}
	}

	window.JSL["event"] = function(e) {
		return new _event_init(e);
	}
})();
(function() {
	///////////////////////////////////////////// Numbers Part ///////////////////////////////////////////
	function _number_init(number) {
		this.number = number;
		return this;
	}
	
	_number_init.prototype = {
		"times":function(func) {
			func = JSL._makeFunc(func,"i");
			for(var i=0; i<this.number; i++) {
				func.call(i);
			}
		},
		
		"upto":function(num, func) {
			func = JSL._makeFunc(func,"i");
			for(var i=this.number; i<num; i++) {
				func.call(i);
			}
		}
	}
	
	window.JSL["number"] = function(number) {
		return new _number_init(number);
	}
})();
//Based on jxs V2.01.A - http://www.openjs.com/scripts/jx/
(function() {
	///////////////////////////////////////////// Event Part ///////////////////////////////////////////
	function _ajax_init(url) {
		this.url = url;
		this.init();
		if(!url) return false;
		return this;
	}
	
	_ajax_init.prototype = {
		"http"		: false, //HTTP Object
		"format"	: 'text',
		"callback"	: function(data){},
		"handler"	: false,
		"error"		: false,
		"opt"		: new Object(),
		///Create a xmlHttpRequest object - this is the constructor. 
		"getHTTPObject" : function() {
			var http = false;
			//Use IE's ActiveX items to load the file.
			if(typeof ActiveXObject != 'undefined') {
				try {http = new ActiveXObject("Msxml2.XMLHTTP");}
				catch (e) {
					try {http = new ActiveXObject("Microsoft.XMLHTTP");}
					catch (E) {http = false;}
				}
			//If ActiveX is not available, use the XMLHttpRequest of Firefox/Mozilla etc. to load the document.
			} else if (XMLHttpRequest) {
				try {http = new XMLHttpRequest();}
				catch (e) {http = false;}
			}
			return http;
		},
		
		// This function is called from the user's script. 
		//Arguments - 
		//	callback - Function that must be called once the data is ready.
		//	format - The return type for this function. Could be 'xml','json' or 'text'. If it is json, 
		//			the string will be 'eval'ed before it is returned it. Default:'text'
		//	method - GET or POST. Default 'GET'
		"load" : function (callback,format,method) {
			this.init(); //The XMLHttpRequest object is recreated at every call - to defeat Cache problem in IE
			var url = this.url;
			if(!this.http||!url) return;
			//XML Format need this for some Mozilla Browsers
			if (this.http.overrideMimeType) this.http.overrideMimeType('text/xml');
	
			this.callback=callback;
			method = method||"GET";//Default method is GET
			format = format||"text";//Default return type is 'text'
			this.format = format.toLowerCase();
			method = method.toUpperCase();
			var ths = this;//Closure
			
			//Kill the Cache problem in IE.
			var now = "uid=" + new Date().getTime();
			url += (url.indexOf("?")+1) ? "&" : "?";
			url += now;
	
			var parameters = null;
	
			if(method=="POST") {
				var parts = url.split("\?");
				url = parts[0];
				parameters = parts[1];
			}
			this.http.open(method, url, true);
	
			if(method=="POST") {
				this.http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
				this.http.setRequestHeader("Content-length", parameters.length);
				this.http.setRequestHeader("Connection", "close");
			}
	
			if(this.handler) { //If a custom handler is defined, use it
				this.http.onreadystatechange = this.handler;
			} else {
				this.http.onreadystatechange = function () {//Call a function when the state changes.
					if(!ths) return;
					var http = ths.http;
					if (http.readyState == 4) {//Ready State will be 4 when the document is loaded.
						if(http.status == 200) {
							var result = "";
							if(http.responseText) result = http.responseText;
							//If the return is in JSON format, eval the result before returning it.
							if(ths.format.charAt(0) == "j") {
								//\n's in JSON string, when evaluated will create errors in IE
								result = result.replace(/[\n\r]/g,"");
								result = eval('('+result+')');
	
							} else if(ths.format.charAt(0) == "x") { //XML Return
								result = http.responseXML;
							}
	
							//Give the data to the callback function.
							if(ths.callback) ths.callback(result);
						} else {
							if(ths.opt.loadingText) document.getElementsByTagName("body")[0].removeChild(ths.opt.loadingText); //Remove the loading indicator element
							if(ths.opt.loadingIndicator) document.getElementById(ths.opt.loadingIndicator).style.display="none"; //Hide the given loading indicator.
							
							if(ths.error) ths.error(http.status);
						}
					}
				}
			}
			this.http.send(parameters);
		},
		"bind" : function(user_options) {
			var opt = {
				'onSuccess':false,	//Function that should be called at success
				'onError':false,	//Function that should be called at error
				'format':"text",	//Return type - could be 'xml','json' or 'text'
				'method':"GET",		//GET or POST
				'update':"",		//The id of the element where the resulting data should be shown. 
				'loadingIndicator':"",		//The id of the loading indicator. This will be set to display:block when the url is loading and to display:none when the data has finished loading.
				'loadingText':"" //HTML that would be inserted into the document once the url starts loading and removed when the data has finished loading. This will be inserted into a div with class name 'loading-indicator' and will be placed at 'top:0px;left:0px;'
			}
			for(var key in opt) {
				if(user_options[key]) {//If the user given options contain any valid option, ...
					opt[key] = user_options[key];// ..that option will be put in the opt array.
				}
			}
			this.opt = opt;
			opt.url = this.url;
			if(opt.onError) this.error = opt.onError;
	
			var div = false;
			if(opt.loadingText) { //Show a loading indicator from the given HTML
				div = document.createElement("div");
				div.setAttribute("style","position:absolute;top:0px;left:0px;");
				div.setAttribute("class","loading-indicator");
				div.innerHTML = opt.loadingText;
				document.getElementsByTagName("body")[0].appendChild(div);
				opt.loadingText=div;
			}
			if(opt.loadingIndicator) document.getElementById(opt.loadingIndicator).style.display="block"; //Show the given loading indicator.
			
			this.load(function(data){
				if(opt.update) document.getElementById(opt.update).innerHTML = data;
				
				if(div) document.getElementsByTagName("body")[0].removeChild(div); //Remove the loading indicator
				if(opt.loadingIndicator) document.getElementById(opt.loadingIndicator).style.display="none"; //Hide the given loading indicator.
				
				if(opt.onSuccess) opt.onSuccess(data);// Call the onSuccess function
			},opt.format,opt.method);
		},
		init : function() {this.http = this.getHTTPObject();}
	}
	
	/**
	 * Arguments: URL - the url of the page to be loaded
	 */
	window.JSL["ajax"] = function(url) {
		return new _ajax_init(url);
	}
})();
	
