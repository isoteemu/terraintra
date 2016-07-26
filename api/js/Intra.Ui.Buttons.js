/**
 * @file
 *   Converts Buttons to Javascript-exchanged ones
 */
Intra.Ui.Buttons = function(context) {
	$('input[type=submit]', context).addClass('ui-state-default').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	}).focus(function() {
		$(this).addClass('ui-state-focus');
	}).blur(function() {
		$(this).removeClass('ui-state-focus');
	});
}

Drupal.behaviors.intraUiButtons = Intra.Ui.Buttons;
