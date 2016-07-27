terra.include("element");
terra.include("transition");

terra.overlay = {
	show: function(onLoaded) {
		if(typeof(this.elem) == "undefined") {
			var tabSize = 0;
			var from = terra.element(".tabs");
			if(from.tagName)  {
				tabSize = from.offset().y + from.height()+1;
			} else {
				tabSize = terra.element("#mainContent").offset().y;
			}

			h = Math.max(
				terra.element("body").height(),
				terra.windowSize().height
			)-tabSize;

			this.elem = new terra.element("div", {
				id: "overlay"
			}).css({
				position: "absolute",
				top: 0,
				left: 0,
				right: 0,
				bottom: 0,
				width: "100%",
				opacity: 0,
				marginTop: tabSize+"px",
				height: h + "px",
				backgroundColor:"#000",
				display:"none"
			});

			terra.element("body").appendChild(this.elem);
		}

		var elem = this.elem;

		this.elem.css("display", "block");

		terra.animate(this.elem, {opacity:0.5}, 250, function() {
			if(elem.onclick == null) {
				elem.onclick = function() {
					terra.overlay.hide();
				};
			}
			if(onLoaded) onLoaded.call();
		});
		return this;
	},

	hide: function() {
		var elem = this.elem;
		if(this.onclick.call() != false) {
			terra.animate(this.elem, {opacity:0}, 250, function() {
				elem.hide();
				terra.overlay.onclick = function() {return true;};
			});
		}
	},

	/**
	 * Onclick event hook
	 */
	onclick: function() {
		return true;
	}
}