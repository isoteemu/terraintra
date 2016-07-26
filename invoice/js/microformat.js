Intra.Microformat.processInvoices = function(context) {
	$('.invoice[data-uid]', context).each(function() {

		var hCard = $(this).hCard();
		var iter = this;
		var nr = $('.in_nr', this);

		nr = nr.filter(function() {
			return ($(this).parents('.invoice:first')[0] == iter) ? true : false;
		});

		if(nr.parents('.invoice:first')[0] == this) {
			hCard.assignInfobox(nr);
		}
	});
}

if (Drupal.jsEnabled) {
	Drupal.behaviors.processIntraInvoices = Intra.Microformat.processInvoices;
}
