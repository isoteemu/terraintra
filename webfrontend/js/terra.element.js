/**
 * Internet explorer doesn't support HTMLElement -
 * only god knows why. My quess; IE coders are bunch
 * of fags, who like something stuffed in their shit
 * hole - also what some call browser, and some IE
 * 
 * Influenced by prototype
 */

terra.element = function(selector, attr) {
	if(this == terra) {
		return new terra.element.methods.select(selector);
	} else {
		// Create new element
		attr = attr || { };

		if (!terra.element.cache[selector]) {
			terra.element.cache[selector] = terra.element(document.createElement(selector));
		}
		var el = terra.element(terra.element.cache[selector].cloneNode(false));
		el.attr(attr);

		return el;
	}
}

terra.element.cache = {};

// Compile regexes
terra.element.elIdExpr = /^#([\w-]+)$/;
terra.element.elClassExpr = /^\.([\w-]+)$/;

terra.element.methods = terra.element.prototype = {

	terraElementExtended: true,

	// Selector, similar syntax as jquery
	select: function(selector) {
		if ( selector == null ) {
			return false;
		}
		if ( selector.nodeType ) {
			if(! selector.terraElementExtended) {
				terra.element.methods.extend(selector);
			}
			return selector;
		}
		if ( typeof selector == "string" ) {
			var el = {};
			var match = terra.element.elIdExpr.exec( selector );

			if ( match && match[1] ) {
				el = document.getElementById(match[1]);
			} else {
				// Try class - but return only one
				match = terra.element.elClassExpr.exec( selector );

				if ( match && match[1] ) {
					if(this.nodeType) {
						el = this.getElementsByClassName(match[1])[0];
					} else {
						el = document.getElementsByClassName(match[1])[0];
					}
				} else {
					// HTML Tag
					if(this.nodeType) {
						var ar = this.getElementsByTagName(selector);
						if(ar.length) el = ar[0];
					} else {
						var ar = document.getElementsByTagName(selector);
						if(ar.length) el = ar[0];
					}
				}
			}
			return (el) ? terra.element(el) : false;
		}
		return this;
	},

	extend: function(el) {
		if(!el) el = this;
		if(!el.terraElementExtended) {
			// Duck-tape for MSIE. Easy to make better later thou
			if(!document.defaultView || !document.defaultView.getComputedStyle) {
				for (var property in terra.element.methodsMSIE) {
					el[property] = terra.element.methodsMSIE[property];
				}
			}

			for (var property in terra.element.methods) {
				if(typeof el[property] == "undefined")
					el[property] = terra.element.methods[property];
			}
			//el.terraElementExtended = true;
		}
	},

	getElementsByClassName: function(cl) {
		var retnode = [];
		var el;
		if( document.evaluate ) {
			// Use XPATH - should be _way_ faster
			var elem = document.evaluate(".//*[contains(concat(' ', @class, ' '), ' " +cl+ " ')]", this, null, XPathResult.ORDERED_NODE_SNAPSHOT_TYPE, null);
			for (var i = 0; i < elem.length; i++) {
				el = terra.element(elem[i]);
				retnode.push(el);
			}
		} else {
			var elem = this.getElementsByTagName('*');
			for (var i = 0; i < elem.length; i++) {
				el = terra.element(elem[i]);
				if(el.hasClass(cl)) {
					retnode.push(el);
				}
			}
		}
		return retnode;
	},

	getChildren: function() {
		var retnode = [];
		for(var i = 0; i < this.childNodes.length; i++) {
			switch(this.childNodes[i].nodeType) {
				case 1: // ELEMENT NODE
					retnode.push(terra.element( this.childNodes[i] ));
					break;
			}
		}
		return retnode;
	},

	/**
	 * Attribute set/get. Taken from jQuery
	 */
	attr: function(name, value) {
		var options = name;
		if ( name.constructor == String ) {
			if ( value === undefined ) {
				// Return current value
				return this.getAttribute(name);
			} else {
				options = {};
				options[name] = value;
			}
		}

		for ( name in options ) {
			if(options[name].constructor == String) {
				this.setAttribute(name, options[name]);
			} else {
				this[name] = options[name];
			}
		}
		return this;
	},

	/**
	 * Get/Set CSS value
	 */
	css: function(name, value) {
		var options = name;
		if ( name.constructor == String ) {
			if ( value === undefined ) {
				// Return current value
				return this.getStyle(name);
			} else {
				options = {};
				options[ name ] = value;
			}
		}

		for ( name in options ) {
			this.setStyle(name, options[name]);
		}
		return this;
	},

	/**
	 * Get current CSS Value
	 */
	getStyle: function(name) {
		var ret, val;
		val = this.style[name];
		if (!val || val == 'auto') {
			if(document.defaultView && document.defaultView.getComputedStyle) {
				var css = document.defaultView.getComputedStyle(this, null);
				val = css ? css[name] : null;
			}
		}
		if(name == 'opacity') return val ? parseFloat(val) : 1.0;
    	return val == 'auto' ? null : val;
	},

	/**
	 * Set _one_ css value
	 */
	setStyle: function(key, value) {
		// ignore negative width and height values
		if ( (key == 'width' || key == 'height') && parseFloat(value) < 0 ) {
			value = undefined;
		} else if(key == 'opacity') {
			value = (value == 1 || value === '') ? '' : 
      			(value < 0.00001) ? 0 : value;
		}

		this.style[key] = value;
		return this;
	},

	addClass: function(className) {
	    if (!this.hasClass(className))
    	  this.className += (this.className ? " " : "") + className;
		return this;
	},
	hasClass: function(className) {
		return (this.className.length > 0 && (this.className == className || 
			new RegExp("(^|\\s)" + className + "(\\s|$)").test(this.className)));
	},
	removeClass: function(className) {
		this.className = this.className.replace(
			new RegExp("(^|\\s+)" + className + "(\\s+|$)"), " ").replace(/^\s+/, "").replace(/\s+$/, "");
		return this;
	},

	show: function() {
		this.css("display", "");
		return this;
	},
	hide: function() {
		this.css("display", "none");
		return this;
	},

	// TODO set possibility
	height: function() {
		return this.getDimensions().height;
	},
	width: function() {
		return this.getDimensions().width;
	},

	/**
	 * C'n'P from drupal 4.7 absolutePosition()
	 */
	offset: function() {
		var sLeft = 0, sTop = 0;
		var isDiv = /^div$/i.test(this.tagName);
		if (isDiv && this.scrollLeft) {
			sLeft = this.scrollLeft;
		}
		if (isDiv && this.scrollTop) {
			sTop = this.scrollTop;
		}
		var r = { x: this.offsetLeft - sLeft, y: this.offsetTop - sTop };
		if (this.offsetParent) {
			var tmp = terra.element(this.offsetParent).offset();
			r.x += tmp.x;
			r.y += tmp.y;
		}
		return r;
	},

	/**
	 * Get element dimensions - from prototype
	 */
	getDimensions: function() {
		var display = this.css("display");
		if (display != "none" && display != null) // Safari bug
			return {width: this.offsetWidth, height: this.offsetHeight};

		// All *Width and *Height properties give 0 on elements with
		// display none, so enable the element temporarily
		var originalVisibility = this.css("visibility");
		var originalPosition = this.css("position");
		var originalDisplay = this.css("display");
		this.css({
			visibility: "hidden",
			position: "absolute",
			display: "block"
		});
		var originalWidth = this.clientWidth;
		var originalHeight = this.clientHeight;
		this.css({
			display: originalDisplay,
			position: originalPosition,
			visibility: originalVisibility
		});

		return {width: this.originalWidth, height: this.originalHeight};
	},

	html: function(text) {
		if ( typeof text != "object" && text != null ) {
			this.innerHTML = text;
			return this;
		}
		return this.innerHTML;
	},

	getText: function() {
		if(typeof(this.childNodes) == "undefined")
			return "";

		var r = "";

		terra.each(this.childNodes, function() {
			if ( this.nodeType != 8 )
				r += this.nodeType != 1 ?
					this.nodeValue :
					terra.element(this).getText();
		});
		return r;
	}
}

terra.element.methodsMSIE = {
	getStyle: function(name) {
		var val = this.style[name];
		if (!val)
			val = this.currentStyle[name];

		if (name == "opacity") {
			if (val = (this.getStyle("filter") || "").match(/alpha\(opacity=(.*)\)/)) {
				if (val[1]) return parseFloat(val[1]) / 100;
			}
			return 1.0;
		}

		return val == "auto" ? null : val;
	},
	setStyle: function(key, value) {
		if ( (key == 'width' || key == 'height') && parseFloat(value) < 0 ) {
			value = undefined;
		} else if(key == "opacity") {
			value = (value == 1 || value === "") ? "" : 
      			(value < 0.00001) ? 0 : value;
			key = "filter";
			value = "alpha(opacity=" + (value * 100) + ")";
		}

		this.style[key] = value;
		return this;
	}
}

// Decent browser. Needs extending only once
if(typeof HTMLElement != "undefined") {
	for(var func in terra.element.methods) {
		HTMLElement.prototype[func] = terra.element.methods[func];
	}
	HTMLElement.terraElementExtended = true;
}

if(typeof document.getElementsByClassName == "undefined") {
	document.getElementsByClassName = terra.element.methods.getElementsByClassName;
}
