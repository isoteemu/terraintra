/**
 * A _really_ dummy microformat class
 */

jQuery.fn.hCard = function() {
	return new Intra.Microformat.Card(this);
}

/**
 * Replace form return destination.
 */
Intra.EditDestination = function(form) {
	var element = $(':input[name=destination]', form);

	if(element) {
		var reg = new RegExp('^'+Drupal.settings.basePath+'(.*)$');
		element.val(reg.exec(window.location.pathname)[1]);
	}

	return true;
}

Intra.Microformat = {
	// Storage for hCards
	hCards: {},

	/**
	 * Create new card element
	 */
	Card: function(element) {
		if(element[0].hCard) return element[0].hCard;

		this[0] = element;

		for(fn in Intra.Microformat.fn ) {
			this[fn] = Intra.Microformat.fn[fn];
		}

		var uid = this.uid();

		if(Intra.Microformat.hCards[uid]) {
			element[0].hCard = Intra.Microformat.hCards[uid];
			Intra.Microformat.hCards[uid].push(element);
			//Intra.Microformat.hCards[uid].length++;
		} else {
			this.length = 1;
			Intra.Microformat.hCards[uid] = this;
			this[0][0].hCard = Intra.Microformat.hCards[uid];
		}

		return Intra.Microformat.hCards[uid];
	},

	/**
	 * Get hcard by uid
	 */
	getCard: function(uid) {
		return Intra.Microformat.hCards[uid];
	},

	/**
	 * Microformat functions
	 */
	fn: {
		/**
		* Assign infobox to be shown on element
		*/
		assignInfobox: function(element) {
			if(!element) element = this[0];

			var url = this.intraUrl();
			if(!url) return;
			url += '/nutshell';
			return Intra.Ui.Tooltip(element, {
				content: {
					text: '<div class="tooltip-loader">'+Drupal.t('Loading')+'</div>',
					url: url
				},
			});
		}
	},

};

Intra.Microformat.Card.prototype = {
	// Save a reference to some core methods
	toString: Object.prototype.toString,
	push: Array.prototype.push,
	slice: Array.prototype.slice,
	indexOf: Array.prototype.indexOf,

	hcard: {},

	hCardElement: function(type) {
		var node;

		for(var i=0, ii = this.length; i < ii; i++) {

			// HACK, no nested elements
			var vcard = this[i][0];

			node = $(type+':first', this[i]).filter(function() {
				if($(this).parents('.vcard:first')[0] != vcard) {
					return false;
				}
				return true;
			});

			if(node.length) {
				return node;
			}
		}
		return false;
	},

	/**
	 * Create url based on UID
	 */
	intraUrl: function() {

		var url = Drupal.settings.basePath;
		var uid = this.uid().match(/^(.*)-(\d+)$/);

		if(!uid) return false;

		switch(uid[1]) {
			case 'Person' :
				url += 'intra/contact/'+uid[2];
				break;
			case 'Company' :
				url += 'intra/company/'+uid[2];
				break;
			case 'Invoice' :
				url += 'intra/invoice/'+uid[2];
				break;
			default:
				throw "Fuck'em";
				return;
		}
		return url;
	},

	// TODO: Add json dialback for missing attributes
	hCardElementText: function(type, txt) {

		var node;
		switch(type) {
			case '.n' :
			case '.given-name' :
			case '.family-name' :
			case '.org' :
				// Try to use FN types first
				if(!(node = this.hCardElement('.fn '+type)))
					node = this.hCardElement(type);
					break;
			default:
				node = this.hCardElement(type);
		}	

		var r = '';

		// Stupid rules...
		if(node) {
			r = this._hCardElementText(node, txt);

			var tagName = node.attr('tagName');
			if(tagName == "A" && ( type == ".url" || type == ".email")) {
				if(r = node.attr('href')) {
					if(type == '.email') {
						r = r.replace(/^(mailto:)/, '');
					}
					if(txt) {
						node.attr('href', txt);
						r = node.attr('href');
					}
				}
			} else if(tagName == "ABBR") {
				if(r = node.attr('title')) {
					if(txt) {
						node.attr('title', txt);
						r = node.attr('title');
					}
				}
			}
		}

		return r;
	},

	_hCardElementText: function(node, txt) {
		if(txt)
			node.text(txt);
		return node.text();
	},

	fn: function(txt) {
		return this.hCardElementText('.fn', txt);
	},

	givenName: function(txt) {
		return this.hCardElementText('.given-name', txt);
	},

	familyName: function(txt) {
		return this.hCardElementText('.family-name', txt);
	},

	// Tricky...
	n: function(txt) {
		var node = this.hCardElementText('.n', txt);
		if(node) 
			return node;

		if(node = this.fn(txt)) {
			var names = node.split(' ');
			if(names.length == 2 && names[0][names[0].length-1] == ',') {
				// Lastname, firstname
				return names[1]+" "+names[0].slice(0, -1);
			} else if(names.length == 2) {
				// Firstname lastname
				return _names;
			}
		}
	},

	org: function(txt) {
		return this.hCardElementText('.org', txt);
	},

	url: function(txt) {
		return this.hCardElementText('.url', txt);
	},

	email: function(txt) {
		var email = this.hCardElementText('.email', txt);
		return email;
	},

	/**
	 * Get UID. Is a bit tricky, as we store UID in vcard HTML5 data attribute.
	 */
	uid: function() {
		var methods = [
			'byMicroformat',
			'byData'
		];
		var uid;

		uid_search:
		for(var i=0; i<methods.length; i++) {
			switch(methods[i]) {
				case 'byMicroformat' :
					if(uid = this.hCardElementText('.uid'))
						break uid_search;
				case 'byData' :
					if(uid = $(this[0]).attr('data-uid'))
						break uid_search;
			}
		}

		if(!uid)
			uid = new Date().getTime()+'-'+Math.random();

		return uid;
	}
}


Intra.Microformat.DragDrop = function(context) {

	$(context)
		.attr('draggable', true)
		.addClass('ui-draggable')
		.bind('dragstart', this.start)
		.bind('dragend', this.stop);

}

Intra.Microformat.DragDrop.prototype = {

	start: function(e) {

		var el		= $(e.target).parents('.vcard:first');
		var hCard	= el.hCard();
		var dt		= e.originalEvent.dataTransfer;

		$(e.target).addClass('ui-draggable-dragging');

		dt.setData('text/plain', el.text());
		dt.setData('x-intra/uid', hCard.uid());

		var url = hCard.intraUrl();
		if(url) {
			url += '/dnd';

			$.ajax({
				'async': false,
				'dataType': 'html',
				'url': url,
				'success': function(data) {
					dt.setData('text/html', data);
					dt.setData('text/plain', $(data).text());
			}});
		}
	},

	stop: function(e) {
		$(e.target).removeClass('ui-draggable-dragging');
	}
};

Intra.Helper = {
	url: function() {

		var url = new String(window.location.pathname);
		var pos = url.search(Drupal.settings.basePath);
		if(pos >= 0) {
			url = url.slice(pos+Drupal.settings.basePath.length);
		}

		window.location=$(el).attr('href')+'?destination='+escape(url);
		return false;
	}
}
