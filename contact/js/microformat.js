Intra.Microformat.processPersons = function(context) {
	$('.vcard.person', context).each(function() {
		var iter = this;
		var hCard = $(this).hCard();

		var fn = $('.fn', this);

		fn = fn.filter(function() {
			return ($(this).parents('.vcard:first')[0] == iter) ? true : false;
		});

		if(fn.parents('.vcard:first')[0] == this) {
			new Intra.Microformat.DragDrop(fn);
			hCard.assignInfobox(fn);
		}
	});
};

if (Drupal.jsEnabled) {
	Drupal.behaviors.processPersonVcards = Intra.Microformat.processPersons;
}
