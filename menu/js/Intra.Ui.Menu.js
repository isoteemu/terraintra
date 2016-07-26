Intra.Ui.Menu = function(context) {
	$('.vcard a.url', context).click(function() {
		if(typeof(parent.frames.home) != 'object') {
			var url = this.getAttribute('href');

			if(!url.match(/(\?|&)intra_menu_init(&|=[^&]*|$)/)) {
				if(url.match(/\?/)) url += "&";
				else url += "?";
				this.setAttribute('href', url+"intra_menu_init");
			}
		}
	});
}

Drupal.behaviors.intraUiButtons = Intra.Ui.Menu;
