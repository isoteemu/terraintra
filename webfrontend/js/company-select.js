// Transform company selection dialog into listview

terra.intra.companySelect = function(select,wholeForm) {

	var self = this;
	this.selectItem = terra.element(select);

	this.linkNode = new terra.element("input", {
		type: "button",
		onclick: function() {
			self.show();
			return false;
		},
		//href: "",
		title: this.selectItem.getAttribute("title"),
		value: this.selectItem.getAttribute("title"),
		"class": "change-company select-listview org"
	}).css({
		overflow:"hidden",
		margin: this.selectItem.css("margin"),
		padding: this.selectItem.css("padding"),
		//display:"block",
		//width: this.selectItem.width()-6+"px",
		//height: this.selectItem.height()-6+"px"
	});

	this.linkNode.html(this.selectItem.options[this.selectItem.selectedIndex].innerHTML);

	if(wholeForm) {
		var frm = terra.element(this.selectItem.form);
		frm.parentNode.appendChild(this.linkNode);
		frm.hide();
	} else {
		//this.selectItem.hide();
		this.selectItem.parentNode.appendChild(this.linkNode);
	}
}

terra.intra.companySelect.prototype = {
	selectItem: {},
	linkNode: {},

	show: function() {
		var self = this;

		var cons = function() {
			overlay.onclick = function() {
				self.hide(true);
				return false;
			};

			if(!self.listView) {
				self.listView = new listView(self.selectItem);
				self.listView.addChangeButton(function() {
					self.hide(false);
					return false;
				});
			}
			if(loader) removeNode(loader);
			self.listView.show();
			self.listView.searchField.focus();
		}

		if(this.listView) {
			var overlay = terra.overlay.show();
			cons.call();
		} else {

			var loader = new terra.element("img", {
				src: "/modules/teemu/images/rel_interstitial_loading.gif",
				alt: "Loading"
			}).css({
				position:"absolute",
				left:"50%",
				top:"50%",
				marginTop:-10+terra.viewportScroll().y+"px",
				marginLeft:-110+terra.viewportScroll().x+"px"
			});
			terra.element("body").appendChild(loader);

			var overlay = terra.overlay.show(cons);
		}

	},

	hide: function(cancel) {
		if(this.listView.selected && (this.selectItem.selectedIndex != this.listView.selected.value) && ! cancel) {
			this.selectItem.options[this.listView.selected.value].selected = true;
			this.listView.hide();
			this.submit();
		} else {
			this.listView.hide();
			terra.overlay.onclick = function() {return true;};
			terra.overlay.hide();
		}

		return true;
	},

	submit: function() {
		this.linkNode.innerHTML = this.selectItem.options[this.selectItem.selectedIndex].innerHTML;
		this.selectItem.form.submit();
	}
}

terra.addOnLoad(function() {
	var selects = document.getElementsByClassName("company-select");
	for(var i=0; i< selects.length; i++) {
		new terra.intra.companySelect(selects[i], false);
	}

	new Image().src="/modules/teemu/images/rel_interstitial_loading.gif";
});
