terra.include("element");
terra.include("overlay");

// Generate new listview from select tag
var listView =  function(select) {

	var self = this;
	this.groups = {};
	this.width = "450px";
	this.height = "500px";

	this.dom = new terra.element("div", {
		"class": "listview listview-bg "+select.getAttribute("class")
	}).hide();

	// Set title, if appropriate
	var label = select.getAttribute("title");
	if(label)
		this.setTitle(label);

	// Build searchbox
	var searchBar = new terra.element("div", {
		"class":"searchBar"
	});
	this.dom.appendChild(searchBar);

	var searchBox = new terra.element("div", {
		"class":"searchBox"
	});
	searchBar.appendChild(searchBox);

	var searchInp = new terra.element("input", {
		autocomplete: "off",
		type: "text",
		name: "searchField",
		value: "Enter search phrase here",
		onfocus: function() {
			if(this.value=="Enter search phrase here") {
				this.css({color:"#000"});
				this.value = "";
			}
		},
		onkeyup: function(event) {
			self.listViewSeek(event);
		}
	});
	searchBox.appendChild(searchInp);
	this.searchField = searchInp;

	var clearImg = new terra.element("img", {
		src: "/modules/teemu/images/clear-left.png",
		"class": "clear",
		onclick: function() {
			self.searchField.value="";
			// RESET
			self.itemSearchReset();
			self.searchField.focus();
		}
	});
	searchBox.appendChild(clearImg);

	// viewport
	this.viewport = new terra.element("div");
	terra.addClass(this.viewport, "listViewContainer");
	terra.css(this.viewport, {
		display: "none"
	});
	this.dom.appendChild(this.viewport);

	// Build groups
	var optGroups = select.getElementsByTagName("optgroup");
	var options;
	var li;
	var group = "";
	for(var i=0; i<optGroups.length; i++) {
		group = this.listGroup(optGroups[i].getAttribute("label"));

		options = optGroups[i].getElementsByTagName("option");
		for(var j=0; j<options.length; j++) {
			li = this.buildListItem(options[j].innerHTML, {
				name: select.getAttribute("name"),
				value: options[j].value,
				index: options[j].index
			}, options[j].selected);
			this.addToGroup(li, group);
		}
	}

	document.body.appendChild(this.dom);
};

/**
 * Hook for OK function
 */
listView.prototype.onChange = function() {
	
}

listView.prototype.addChangeButton = function( callback ) {

	var button = new terra.element("button");
	if(typeof callback == "function")
		this.onChange = callback;

	button.onclick = this.onChange;
	button.innerHTML = "Change";

	this.addButton(button);
}

listView.prototype.addButton = function(button) {
	if(!this.buttons) {
		this.buttons = new terra.element("div");
		terra.addClass(this.buttons, "buttons");
		this.dom.appendChild(this.buttons);
	}
	this.buttons.appendChild(button);
}

listView.prototype.show = function() {
	var h = Math.min(parseInt(this.height), terra.windowSize().height-20);
	var w = Math.min(parseInt(this.width), terra.windowSize().width-20);

	this.dom.css({
		width: w+"px",
		height: h+"px",
		left: "50%",
		top: "50%",
		marginLeft: -(w/2)+terra.viewportScroll().x+"px",
		marginTop: -(h/2)+terra.viewportScroll().y+"px",
		zIndex: 950,
		position: "absolute",
		display: "block"
	});

	var vpH = h-42;
	for(var i = 0; i < this.dom.childNodes.length; i++) {
		if(this.dom.childNodes[i] == this.viewport) continue;
		vpH -= this.dom.childNodes[i].height();
	}

	this.viewport.css({
		height: vpH+"px",
		display: "block"
	});
	//this.selected.scrollIntoView();
}

listView.prototype.hide = function() {
	terra.css(this.dom, {
		display: "none"
	});
}

listView.prototype.setTitle = function(text) {
	if(typeof(this.titleBar) == "undefined") {
		this.titleBar = new terra.element("h4");
		terra.addClass(this.titleBar, "titleBar");
		this.dom.insertBefore(this.titleBar, this.dom.firstChild);
	}

	this.titleBar.innerHTML = text;
}

listView.prototype.listGroup = function(name) {
	if(typeof(this.groups[name]) == "undefined") {
		var set = new terra.element("fieldset");
		var legend = new terra.element("legend").html(name);
		var ul = new terra.element("ul", {
			"class": "listViewRow"
		});

		set.appendChild(legend);
		set.appendChild(ul);

		this.groups[name] = set;

		this.viewport.appendChild(set);
	}

	return this.groups[name];
}

listView.prototype.buildListItem = function(title, options, selected) {
	var self = this;
	var id = "listview-"+options.name+"-"+options.value;

	var li = new terra.element("li", {
		"class": "listViewRow selectable"
	}).attr("value", options.index);

	var ops = new terra.element("div", {
		"class": "operations"
	});

	var check = new terra.element("input", {
		type: "checkbox",
		name: options.name,
		id: id,
		return_value: options.value,
		onchange: function() {
			if(this.checked == true) {
				self.selectItem(li);
			} else {
				self.unSelectItem(li);
			}
		}
	});
	ops.appendChild(check);

	var name = new terra.element("label", {
		"for": id,
		"class": "itemName"
	}).html(title);

	li.appendChild(ops);
	li.appendChild(name);

	if(selected) {
		this.selectItem(li);
	}

	return li;

}

listView.prototype.addToGroup = function(li, group) {

	var className = (group.getElementsByTagName("li").length % 2) ? "odd" : "even";
	li.addClass(className);
	group.select("ul").appendChild(li);
}

listView.prototype.itemSearchReset = function() {
	if(this.searchTimer != "null") {
		clearTimeout(this.searchTimer);
	}
	this.itemSearch(this.searchField.value);
}

listView.prototype.listViewSeek = function(event) {
	if (!event) {
		event = window.event;
	}

	var self = this;
	var str = this.searchField.value;

	switch(event.keyCode) {

		case 38: // up arrow
			this.selectItemUp();
			break;

		case 40: // down arrow
			this.selectItemDown();
			break;

    	case 13: // enter
			if(this.searchTimer != "null") {
				clearTimeout(this.searchTimer);
				self.itemSearch(str);
			}
			if(this.selected)
				this.onChange.call();
			else
				console.log("No item selected");
			break;
		default:
			if(this.searchTimer != "null") {
				clearTimeout(this.searchTimer);
			}

			this.searchTimer = setTimeout(function() {
				self.itemSearch(str);
			}, 400);
			break;
	}
}

listView.prototype.itemSearch = function(str) {

	var listItems = [];
	var fieldsetHide = true;

	var text = "";
	var search = true;

	var firstItem;

	var reg = new RegExp(terra.preg_quote(str), "i");

	for(group in this.groups) {

		text = this.groups[group].getElementsByTagName("legend")[0].getText();
		search = !reg.test(text);

		listItems = this.groups[group].getElementsByTagName("li");
		fieldsetHide = true;
	
		terra.each(listItems, function() {
			show = true;
			if(search) {
				text = this.getText();
				show = reg.test(text);
			}
			if(show) {
				if(this.hasClass("seekHide"))
					this.removeClass("seekHide");
				if(!firstItem)
					firstItem = this;
				fieldsetHide = false;
			} else {
				this.addClass("seekHide");
			}
		});

		if(fieldsetHide == true) {
			this.groups[group].addClass("seekHide");
		} else {
			if(this.groups[group].hasClass("seekHide"))
				this.groups[group].removeClass("seekHide");
		}
	};
	if(this.selected.hasClass("seekHide")) {
		this.selectItem(firstItem);
		//this.selected.scrollIntoView();
	}
}

listView.prototype.selectItemDown = function() {
	// Loop 'till found next visible
	var next = this.selected.nextSibling;
	while(next.tagName == "LI") {
		if(!next.hasClass("seekHide")) {
			this.selectItem(next);
			//this.selected.scrollIntoView();
			break;
		}
		if(next.nextSibling) {
			next = next.nextSibling;
		} else {
			var grp = next.parentNode.parentNode.nextSibling;
			while(grp && grp.hasClass("seekHide") && grp.tagName == "FIELDSET") {
				grp.nextSibling;
			}
			if(!grp || grp.hasClass("seekHide") || grp.tagName != "FIELDSET") {
				break; // Bottom reached
			}
			next = grp.getElementsByClassName("selectable")[0];
			if(!next) break;
		}
	}
}

listView.prototype.selectItemUp = function() {
	var next = this.selected.previousSibling;
	while(next.tagName == "LI") {
		if(!next.hasClass("seekHide")) {
			this.selectItem(next);
			//this.selected.scrollIntoView();
			break;
		}
		if(next.previousSibling) {
			next = next.previousSibling;
		} else {
			var grp = next.parentNode.parentNode.previousSibling;
			while(grp && grp.hasClass("seekHide") && grp.tagName == 'FIELDSET') {
				grp.previousSibling;
			}
			if(!grp || grp.hasClass("seekHide") || grp.tagName != 'FIELDSET') {
				break; // Bottom reached
			}
			var li = grp.getElementsByClassName('selectable');
			if(!li.length) break;
				next = li[li.length-1];

			if(!next) break;
		}
	}
}

/// TODO: Replace scrollIntoView with smarter one
listView.prototype.selectItem = function(item) {
	if(this.selected) {
		this.unSelectItem(this.selected);
	}
	this.selected = item;

	item.addClass("selected");
	item.getElementsByTagName("input")[0].checked = true;
}

listView.prototype.unSelectItem = function(item) {
	this.selected = false;
	item.removeClass("selected");
	item.getElementsByTagName("input")[0].checked = false;
}
