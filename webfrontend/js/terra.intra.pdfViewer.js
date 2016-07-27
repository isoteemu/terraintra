/**
 * Create PDF viewer window in modal dialog
 * @TODO: Center on visible screen
 */

terra.include("intra");
terra.include("element");
terra.include("overlay");

terra.intra.pdfViewer = function(element) {
	var self = this;
	this.el = element;

	element.onclick = function() {
		return self.show();
	}
}

terra.intra.pdfViewer.prototype = {
	iFrame: {},

	show: function() {
		var pdfViewer = this;
		var overlay = terra.overlay.show();
		var iFrame = this.createIframe();

		if(this.el.tagName == "INPUT") {

			var oldTarget = this.el.form.target;

			this.el.form.target = iFrame.getAttribute("name");
			iFrame.onload = function() {
				pdfViewer.el.form.target = oldTarget;

				overlay.onclick = function() {
					return pdfViewer.hide();
				}
			}

			iFrame.show();
			return true;

		} else if(this.el.tagName == "A") {
			//iframe.location.pathname = this.el.getAttribute("href");
			iFrame.setAttribute("src", this.el.getAttribute("href"));
			iFrame.onload = function() {
				overlay.onclick = function() {
					return pdfViewer.hide();
				}
			}

			iFrame.show();
			return false;
		}
	},

	hide: function() {
		this.iFrame.hide();
		terra.overlay.onclick = function() {return true;};
		terra.overlay.hide();
		return false;
	},

	createIframe: function() {
		if(!this.iFrame.nodeType) {
			this.iFrame = new terra.element("iframe");

			with(this.iFrame) {
				name =  "pdfViewer-iframe";
				setAttribute("name", "pdfViewer-iframe");
				id = "pdfViewer-iframe";
			}

			terra.css(this.iFrame, {
				display: "none"
			});

			this.iFrame.show = function() {
				// Allways re-position
				var h = (terra.windowSize().height-20) * 0.9;
				var w = (terra.windowSize().width-20) * 0.9;
				terra.css(this, {
					width: w+"px",
					height: h+"px",
					left: "50%",
					top: "50%",
					marginLeft: -(w/2)+window.scrollX+"px",
					marginTop: -(h/2)+window.scrollY+"px",
					zIndex: 950,
					position: "absolute",
					display: "block"
				});
			};

			this.iFrame.hide = function() {
				terra.css(this, {
					display: "none"
				});
			};

			document.body.appendChild(this.iFrame);

		}

		return this.iFrame;
	}
}

terra.addOnLoad(function() {

	if(navigator && navigator.mimeTypes["application/pdf"] && navigator.mimeTypes["application/pdf"].enabledPlugin != null) {
		var selects = document.getElementsByClassName("pdfViewer");
		for(var i=0; i< selects.length; i++) {
			new terra.intra.pdfViewer(selects[i]);
		}


		var as = document.getElementsByTagName("a");
		var reg = /^.*\.pdf$/;
		for(var i=0; i< as.length; i++) {
			if(reg.exec(as[i].getAttribute("href")) && ! as[i].getAttribute('onclick')) {
				new terra.intra.pdfViewer(as[i]);
			}
		}
	}
});
