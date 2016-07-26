Intra.Microformat.processCompanys = function(context) {
	$('.vcard.company:parent', context).each(function() {

		var iter = this;
		var hCard = $(this).hCard();

		// Add info-box for companys
		var org = $('.org', this);

		org = org.filter(function() {
			return ($(this).parents('.vcard:first')[0] == iter) ? true : false;
		});

		if(org.parents('.vcard:first')[0] == this) {
			var url = hCard.url();

			if(!url) return;
			else if(url.search(Drupal.settings.basePath)) return;

			new Intra.Microformat.DragDrop(org[0]);
			hCard.assignInfobox(org[0]);
		}

		if(Drupal.settings.openlayers) {

			$('.location', this).each(function() {
				$(this).qtip({
					content: 'MAP comes here',
					style: {
						tip: true,
					},
					position: {
						corner: {
							target: 'rightMiddle',
							tooltip: 'leftMiddle'
						}
					}
				});
			});
		}
	});
}


if (Drupal.jsEnabled) {
	Drupal.behaviors.processCompanyVcards = Intra.Microformat.processCompanys;
}