Intra.Print = function(context) {
	var title = Drupal.t('Print');
	var icon = $('<a class="intra-print ui-state-default ui-corner-all" unselectable="on" title="'+title+'"><span class=" ui-icon ui-icon-print" /></a>')
		.hover(function() {
			$(this).addClass('ui-state-hover');
		}, function() {
			$(this).removeClass('ui-state-hover');
		});

	// Print active tab
	$('.tabs .nav').each(function()	{
		icon.clone(true).appendTo(this).click(function() {
			var tab = $(this).parents('.tabs:first').find('.ui-tabs-panel:not(.ui-tabs-hide)');
			Intra.Print.print(tab);
		});
	});

	/**
	 * Print only one-type of invoices.
	 */
	$('#invoice-list > h4').each(function() {
		var print = $(this).next('div');
		icon.clone(true).appendTo(this).click(function() {
			console.log('print');
			Intra.Print.print(print);
		});
	});
}

Intra.Print.print = function(section) {
    var win = window.open();
    self.focus();

    win.document.open();
	win.document.write('<html><head><title>Print</title></head><body/></html>');

	$('head title', win.document).html($('head title').html());


	$('head', win.document)
		.append($('style').clone())
		.append($('link[rel=stylesheet]').clone());

	// Build  dom-tree reversed.
	// New jquery features parentsUntil(), but until that...
	var tree = $(section).clone().show();
	var parent = $(section).parent();
	while(parent.attr('tagName') != 'BODY') {
		tree = parent.clone(false).empty().show().append(tree);
		parent = parent.parent();
	}

	// TODO: Copy whole parents-tree
	$('body', win.document).append(tree);

    win.document.close();
    win.print();
    win.close();

}

Drupal.behaviors.intraPrint = Intra.Print;
