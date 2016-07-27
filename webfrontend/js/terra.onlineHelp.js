terra.include("intra");
terra.include("element");
terra.include("overlay");

terra.onlineHelp = function() {
	this.canvas = new terra.element("div").css({
		zIndex:950,
		display:"none",
		opacity:0
	});
	terra.element("body").appendChild(this.canvas);
}

terra.onlineHelp.init = function() {

	// Add help item into menu
	var menu = terra.element("#secondary-links");
	if(!menu.tagName) {
		menu = terra.element(".tabs").select("ul");
	}

	if(!menu.nodeType) return false;

	var a = new terra.element("a", {
		"class": "onlineHelp",
		"href":"javascript:void(0);"
	}).attr("onclick", function() {
			if(!this.onlineHelp)
				this.onlineHelp = new terra.onlineHelp();
			this.onlineHelp.show();
	}).html("Help");

	this.helpButton = a.onclick;

	var li = new terra.element("li");
	li.appendChild(a);
	menu.appendChild(li);
}

terra.onlineHelp.prototype = {
	canvas: null,
	helpButton: null,

	itemCount: 0,
	topItemPos: {
		x:0,
		y:-1
	},

	imgRight: "/modules/teemu/images/pointer-right.gif",
	imgLeft: "/modules/teemu/images/pointer-left.gif",

	show: function() {
		var self = this;
		var overlay = terra.overlay.show();

		this.itemCount = 0;

		this.formItems(document.getElementsByClassName("form-item"));
		this.formItems(document.getElementsByTagName("fieldset"));

		overlay.onclick = function() {
			return self.hide();
		}

		this.canvas.css({
			opacity:0,
			display:"block"
		});

		var pos = this.topItemPos;
		if(pos.y > 0 && terra.viewportScroll().y + terra.windowSize().height < pos.y) {
			window.scrollTo(pos.x, pos.y);
		}

		terra.animate(this.canvas, {opacity:1});
		
	},

	hide: function() {
		this.helpButton = function() { self.show(); };

		terra.overlay.onclick = function() {return true;};
		terra.overlay.hide();

		var canvas = this.canvas;
		terra.animate(canvas, {opacity:0}, 250, function() {

			while(canvas.childNodes.length > 0) {
				removeNode(canvas.childNodes[0]);
			}

			terra.css(canvas, {
				display:"none"
			});
		});
	},

	/**
	 * Read HTMLCollection as form items
	 */
	formItems: function(formItems) {
		for(var i = 0; i< formItems.length; i++) {
			var desc = formItems[i].getElementsByClassName("description");
			if(desc.length > 0 && desc[0].parentNode == formItems[i]) {
				this.addHelpItem(formItems[i], desc[0].innerHTML);
			}
		}
	},

	/**
	 * Create help item for element
	 */
	addHelpItem: function(el, helpText) {
		el = terra.element(el);

		this.itemCount++;

		var pos = {
			x:0,
			y:0
		};
		var dim = el.getDimensions();

		if(el.tagName == "FIELDSET") {
			var legends = el.getElementsByTagName("legend");
			for(var i = 0; i < legends.length; i++) {
				if(legends[i].parentNode == el) {
					pos = terra.element(legends[i]).offset();
					dim = terra.element(legends[i]).getDimensions();
					break;
				}
			}
		} else {
			var curElem = {};
			var childNodes = el.children();
			for(var i = 0; i < childNodes.length; i++) {
				curElem = terra.element(childNodes[i]);
				switch(curElem.tagName) {
					case "LABEL" :
						var forid = curElem.attr("for");
						var checkbox = {};
						if(forid) {
							checkbox = curElem.select("#"+forid);
						} else {
							checkbox = curElem.select("input");
						}
						if(checkbox.tagName && checkbox.attr("type") == "checkbox") {
							pos = {
								x: Math.min(curElem.offset().x, checkbox.offset().x),
								y: Math.min(curElem.offset().y, checkbox.offset().y)
							};
							dim = {
								width: Math.max(curElem.width(), checkbox.width()),
								height: Math.max(curElem.height(), checkbox.height())
							}
						}
						break;
					case "INPUT" :
					case "SELECT" :
						pos = curElem.offset();
						dim = curElem.getDimensions();
						break;
				}
				if(pos.x != 0 || pos.y != 0)
					break;
			}
		}

		if(pos.x == 0 && pos.y == 0)
			pos = el.offset();

		if(this.topItemPos.y < 0 || pos.y < this.topItemPos.y)
			this.topItemPos = pos;

		var div = new terra.element("div", {
			"class": "helpItem"
		}).css({
			width: "250px",
			position:"absolute",
			top: (pos.y-16+dim.height/2)+"px",
			border: "1px solid #fff",
			backgroundColor: "#000",
			padding:"5px",
			zIndex: 990
		}).html(helpText);

		// Left right left...
		if(this.itemCount % 2 && pos.x > 266) {
			var img = new terra.element("img", {
				src: this.imgRight
			}).css({
				right:"-11px"
			});
			div.css({
				left: (pos.x-266-11)+"px"
			});
		} else {
			var img = new terra.element("img", {
				src: this.imgLeft
			}).css({
				left:"-11px"
			});
			div.css({
				left: (pos.x+dim.width+11)+"px"
			});
		}

		img.css({
			position:"absolute",
			top: "4px",
			display:"inline"
		});

		div.appendChild(img);

		// Move to front of others when has focus
		div.onmouseover = div.onfocus = function() {
			this.css({
				zIndex: 995
			});
		}
		div.onmouseout = div.onblur = function() {
			this.css({
				zIndex: 990
			});
		}

		this.canvas.appendChild(div);
	}
}

terra.addOnLoad(function() {
	terra.onlineHelp.init();
});
