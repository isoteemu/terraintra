Drupal.behaviors.intraCompanySelect = function(context) {

	$('select.intra-companyselect:not(.intra-companyselect-processed)').each(function() {
		$(this).addClass('intra-companyselect-processed');

		var wrapper = $('<div class="inline-container" style="display:inline-block" />');

		var searchButton = Intra.Ui.Companyselect.createIcon('search');

		new Intra.Ui.Companyselect.searchCompany(this, searchButton);
		wrapper.append(searchButton);

		if($('option[value=-1]', this)[0]) {

			var addButton = Intra.Ui.Companyselect.createIcon('plus');
			new Intra.Ui.Companyselect.addCompany(this, addButton);

			wrapper.append(addButton);
		}
		wrapper.find(':first-child').addClass('ui-corner-left');
		wrapper.find(':last-child').addClass('ui-corner-right');

		$(this).after(wrapper);

	});
}

Intra.Ui.Companyselect = {};

Intra.Ui.Companyselect.createIcon = function(icontype) {
	return $('<div style="display:inline-block;" class="ui-state-default"><span class="ui-icon ui-icon-'+icontype+'"></span></div>').hover(function() {
		$(this).addClass('ui-state-hover');
	}, function() {
		$(this).removeClass('ui-state-hover');
	}).focus(function() {
		$(this).addClass('ui-state-focus');
	}).blur(function() {
		$(this).removeClass('ui-state-focus');
	});
};

Intra.Ui.Companyselect.dialog = function(trigger, content) {
	return new Intra.Ui.Tooltip(trigger, {
		content: content,
		style: { 
			tip: {
				corner: 'topRight',
			},
		},
		show: {
			delay: 0,
			when: {
				event: 'click'
			}
		},
		hide: {
			delay: 250,
			when: {
//				event: 'click'
			}
		},
		
		position: {
			adjust: {
				y: 16,
				x: -6
			},
			corner: 'topRight'
		},
		api: {
		}
	});
}

Intra.Ui.Companyselect.addCompany = function(selectlist, trigger) {
	var self = this;
	this.selectList = selectlist;

	var inputId = selectlist.id+'-addCompany';

	var input = $('<input type="text" id="'+inputId+'" />');

	input.attr('title', Drupal.t('Company name'))
		.val(input.attr('title'))
		.focus(function() {
			if(this.value == this.title)
				this.value = '';
		}).blur(function() {
			if(this.value == '')
				this.value = this.title;
		}).keyup(function(event) {
			self.onKeyUp(event);
		});

	var content = $('<div/>').append(input);

	this.dialog = Intra.Ui.Companyselect.dialog(trigger, content);

	this.dialog.qtip('api').onRender = function() {
		Drupal.attachBehaviors(this.elements.content[0]);
	};
	this.dialog.qtip('api').onShow = function() {
		$('#'+inputId).focus();
	};

	input.css('width', $(selectlist).width()+36);
}

Intra.Ui.Companyselect.addCompany.prototype.onKeyUp = function(event) {

	var self = this;
	if (!event) {
		event = window.event;
	}
	switch (event.keyCode) {

		case 13: // enter
			self.selectValue(event.target.value);
			this.hideDialog();
			break;
		case 9:  // tab
		case 27: // esc
			event.target.value = '';
			this.hideDialog();
			break;
		default :
			break;
	}
	return true;
};

Intra.Ui.Companyselect.addCompany.prototype.selectValue = function(value) {

	var opt = $('option[value=-1]', this.selectList);

	opt.text(value);
	this.selectList.selectedIndex = opt.attr('index');

	$('input[type=hidden][name='+this.selectList.name+'-typed]').val(value);

	$(this.selectList).parents('.form-item:first').effect('highlight', 'slow');

	return;
}

Intra.Ui.Companyselect.addCompany.prototype.hideDialog = function() {
	this.dialog.qtip('hide');
}

Intra.Ui.Companyselect.searchCompany = function(selectlist, trigger) {
	var self = this;
	this.selectList = selectlist;

	var inputId = selectlist.id+'-search';

	var input = $('<input type="search" class="form-autocomplete" id="'+inputId+'" />');

	input.attr('title', Drupal.t('Search...'))
		.val(input.attr('title'))
		.focus(function() {
			if(this.value == this.title)
				this.value = '';
		}).blur(function() {
			if(this.value == '')
				this.value = this.title;
		});

	var content = $('<div></div>').append(input);

	var ac = $('<input type="hidden" disabled="disabled" />')
		.val(Drupal.settings.basePath+"search/suggest/intra_search_company")
		.attr('id', inputId+'-autocomplete')
		.addClass('autocomplete');

	content.append(ac);

	this.dialog = Intra.Ui.Companyselect.dialog(trigger, content);
	this.dialog.qtip('api').onRender = function() {
		Drupal.attachBehaviors(this.elements.content[0]);
		// This needs to be later than autocomplete
		$('#'+inputId, this.elements.content[0]).keyup(function(event) { self.onKeyUp(event);});
		$('#'+inputId, this.elements.content[0]).change(function(event) {
			self.selectValue(this.value);
			self.hideDialog();
		});
	};

	input.css('width', $(selectlist).width()+22);

	this.dialog.qtip('api').onShow = function() {
		$('#'+inputId).focus();
	};
}

Intra.Ui.Companyselect.searchCompany.prototype.onKeyUp = function(event) {

	var self = this;
	if (!event) {
		event = window.event;
	}
	switch (event.keyCode) {
		
		case 13: // enter
			self.selectValue(event.target.value);
			break;
		case 9:  // tab
		case 27: // esc
			event.target.value = '';
			this.hideDialog();
			break;
		default :
			break;
	}
	return true;
};



Intra.Ui.Companyselect.searchCompany.prototype.hideDialog = function() {
	this.dialog.qtip('hide');
}

Intra.Ui.Companyselect.searchCompany.prototype.selectValue = function(value) {
	var l = this.selectList.options.length;
	for(var i = 0; i<l; i++) {
		if(value == this.selectList.options[i].text) {
			this.selectList.selectedIndex = i;
			$(this.selectList).parents('.form-item:first').effect('highlight', 'slow');
			break;
		}
	}
	return;
}
