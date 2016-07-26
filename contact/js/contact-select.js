Intra.Ui.ContactSelect = function(element) {
	this.dom = $(element);
	this.visibleCount = 0;

	var self = this;

	// Assign select buttons
	$('.contact-select-tool .select-all', this.dom).click(function() { self.buttons.actionSelectAll(self) });
	$('.contact-select-tool .select-none', this.dom).click(function() { self.buttons.actionSelectNone(self) });

	// Assign contact action buttons
	$('.toolbar .action-add', this.dom).click(function() { self.buttons.addContact(self) });
	$('.toolbar .action-mailto', this.dom).click(function() { self.buttons.actionMailTo(self) });
	$('.toolbar .action-history', this.dom).click(function() { self.buttons.actionViewHistory(self) });

	// Assign trigger for selectiong contact
	$('.contact-contacts :checkbox', this.dom).change(function(event) { self.selectContactToggle(event); });

	this.initContactGroups();
	this.initContactSelection();
	this.initRolodex();
}

Intra.Ui.ContactSelect.prototype = {

	initContactGroups: function() {
		var dom = this.dom;
		$('.contact-groups li', dom).hover(function() {
			$(this).addClass('ui-state-hover');
		}, function() {
			$(this).removeClass('ui-state-hover');
		}).click(function() {
			$('.contact-groups li', dom).removeClass('ui-state-active');
			$(this).addClass('ui-state-active');

			var myClass = $(this).attr('rel');
			$(this).parents('.contact-select').find('.contact-contacts li').each(function() {
				if(myClass == "" || $(this).hasClass(myClass)) {
					$(this).slideDown();
				} else {
					$(this).slideUp();
				}
			});
		});
	},

	initContactSelection: function() {

		$('.contact-contacts li', this.dom).hover(function() {
			$(this).addClass('ui-state-hover');
		}, function() {
			$(this).removeClass('ui-state-hover');
		});

	},

	// Init rolodex
	initRolodex: function() {
		var rolodex = $('.rolodex', this.dom);
		var self = this;
		// Show/hide by selection
		$('.contact-contacts :checkbox', this.dom).each(function() {

			if(this.checked == true) {
				$('> .'+this.value, rolodex).show();
				$(this).parents('li').addClass('ui-state-active');
				self.visibleCount++;
			} else {
				$('> .'+this.value, rolodex).hide();
				$(this).parents('li').removeClass('ui-state-active');
			}

		});
	},

	/**
	 * Return selected contacts
	 */
	getSelected: function() {
		return $('.contact-contacts :checkbox[checked=true] ~ .vcard', this.dom);
	},

	selectContactToggle: function(event) {

		var status = $(event.target).attr('checked');

		if(status) {
			$('.rolodex li.'+event.target.value, this.dom).slideDown();
			$(event.target).parents('li').addClass('ui-state-active');
			this.visibleCount++;
		} else {
			$('.rolodex li.'+event.target.value, this.dom).slideUp();
			$(event.target).parents('li').removeClass('ui-state-active');
			this.visibleCount--;
		}

		$('.toolbar button.contact-action', this.dom).attr('disabled', (this.visibleCount) ? false : true);
	},

	buttons: {

		/**
		* Select all contacts.
		*/
		actionSelectAll: function(ContactSelect) {
			$('.contact-contacts li:visible :checkbox', ContactSelect.dom).attr('checked', true).change();
			return false;
		},
		
		/**
		* De-select contacts.
		*/
		actionSelectNone: function(ContactSelect) {
			$('.contact-contacts li:visible :checked', ContactSelect.dom).attr('checked', false).change();
			return false;
		},

		addContact: function(ContactSelect) {
			//return confirm('Not implemented');
		},

		actionViewHistory: function(ContactSelect) {
			var contacts = [];
			$('form[name=contacts] :checked', ContactSelect.dom).each(function() {
				contacts.push($(this).val());
			});
			console.log(contacts);

			return false;
		},

		/**
		 * Trigger mailto handler in browser.
		 */
		actionMailTo: function(ContactSelect) {
			// Collect email addresses
			var emails = [];

			ContactSelect.getSelected().each(function() {
				var email = this.hCard.email();
				if(email.match(/.+@.+/)) {
					var fn = this.hCard.fn();
					if(fn) {
						//
						email = '"'+fn+'" <'+email+'>';
						//email = '"=?UTF-8?B?'+Base64.encode(fn)+'?=" <'+email+'>';
					}
					emails.push(email);
				}
			});

			// Create temporary tag, click it to trigger email and remove it.
			var tag = $('<a href="mailto:'+escape(emails.join(', '))+'">Mail</a>').css('display', 'none');
			tag.click(function() {
				// Uses popup window to short cirquit preventions
				var mailto_trigger = window.open('mailto:'+emails.join(','), 'mailto_trigger', 'dependent=yes,dialog=yes');
				if(mailto_trigger && mailto_trigger.open)
					mailto_trigger.close();
			});

			tag.click().remove();

			return false;
		},

	}
}
$(document).ready(function() {
	$('.contact-select').each(function() {
		new Intra.Ui.ContactSelect(this)
	});
});
