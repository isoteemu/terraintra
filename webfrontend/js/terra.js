/**
 * helper script
 */
var terra = window.terra = {
	include: function(script) {
		var classList = script.split(".");
		if(!terra._TerraHasChildClass(classList)) {

			if(!this.includeUri) {
				this.includeUri = "/";
				var scripts = document.getElementsByTagName("script");
				var reg = /(^|\/)(terra\.js)($|\?)/;
				for(var i = 0; i < scripts.length; i++) {
					var src = scripts[i].getAttribute("src");
					if(!src) continue;
					var match = src.match(reg);
					if(match) {
						this.includeUri = src.substring(0, match.index)+"/";
						break;
					}
				}
			}

			var res = HTTPGet(this.includeUri+"terra."+script+".js");
			if(res) eval(res)
			else throw new Error("Could not load requested javascript file "+script);
		}
	},

	/**
	 * Helper for terra.include, to check recursively
	 * if class exists.
	 */
	_TerraHasChildClass: function(classList) {
		var seek = classList.shift();
		if(typeof this[seek] != "undefined") {
			if(classList.length > 0) {
				if(typeof this[seek]["_TerraHasChildClass"] == "undefined")
					this[seek]["_TerraHasChildClass"] = this._TerraHasChildClass;

				return this[seek]._TerraHasChildClass(classList);
			} else {
				return true;
			}
		} else {
			return false;
		}
	},

	css: function(elem, attr) {
		for( i in attr ) {
			elem.style[i] = attr[i];
		}
	},

	animate: function(elem, prop, duration, callback) {
		terra.include("transition");
		var anim = new this.transition(elem, prop, duration);
		if(typeof(callback) != "undefined") {
			anim.onComplete.push(callback);
		}
		anim.run();
		return anim;
	},

	/**
	 * Get window scroll position
	 */
	viewportScroll: function() {
		var r = {
			x: 0,
			y: 0
		};
		if(window.scrollX || window.scrollY) {
			r.x = parseInt(window.scrollX);
			r.y = parseInt(window.scrollY);
		} else if( window.pageXOffset || window.pageYOffset ) {
			r.x = parseInt(window.pageXOffset);
			r.y = parseInt(window.pageYOffset);
		}
		return r;
	},

	/**
    * Run code on window.onload
	 */
	addOnLoad: function(code) {
		return addLoadEvent(code);
	},
	addClass: function(elem, cssClass) {
		return addClass(elem, cssClass);
	},
	hasClass: function(elem, cssClass) {
		return hasClass(elem, cssClass);
	},
	removeClass: function(elem, cssClass) {
		return removeClass(elem, cssClass);
	},
	removeNode: function(node) {
		return removeNode(node);
	},

	/**
	 * @deprecated
	 */ 
	getText: function(el) {
		return el.getText();
	},

	each: function(object, callback) {
		for ( var i = 0, length = object.length, value = object[0]; 
			i < length && callback.call( value, i, value ) !== false; value = object[++i] ){}
	
		return object
	},

	preg_quote: function( str ) {
    	// http://kevin.vanzonneveld.net
    	return str.replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1");
	},

	windowSize: function() {
		var size = {};
		if( typeof ( window.innerWidth ) == "number" ) {
			size.width = window.innerWidth;
			size.height = window.innerHeight;
		} else {
			size.width = document.body.offsetWidth;
			size.height = document.body.offsetHeight;
		}
		return size;
	}
}
