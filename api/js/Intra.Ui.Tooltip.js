$.fn.qtip.styles.intra = {
	tip: true,
	padding: 0,
	width: '16em',
	border: {
		width: 7,
		radius: 5,
		color: "rgba(227, 228, 228, 0.8)",
	},
	tip: {
		corner: 'topLeft',
		size: { y: 8 }
	}
};

/**
 * Tooltip wrapper.
 * @param element HTMLElement
 * @param params Object
 */
Intra.Ui.Tooltip = function(element, params) {
	var settings = {
		show: {
			delay: 750
		},
		hide: {
			fixed: true,
//			when: {event:"click"}
		},
		position: {
			adjust: {
				x: 5,
				y: -6
			},
			corner: {
				target: 'bottomLeft'
			}
		},
		style: {
			name: 'intra'
		},
		api: {
			// Attach drupal behaviours
			/*
			onShow: function() {
				this.onShow = function() {};
				console.log("onRender");
				Drupal.attachBehaviors(this.elements.content[0]);
			},
			*/
		}

	};

	jQuery.extend(true, settings, params)

	return $(element).addClass('has-tooltip').qtip(settings);
};
