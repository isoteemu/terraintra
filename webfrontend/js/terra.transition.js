/**
 * Simple transition effect animator
 */

terra.transition = function(elem, prop, duration) {
		this.duration = duration || 500; // 500ms for transition

		this.onComplete = [];
		this.elem = elem;
		this.prop = prop;
}

terra.transition.prototype = {

	onComplete: [],

	now: function() {
		return +new Date;
	},

	run: function() {
		this.startTime = this.now();

		this.start = {
			opacity: 1 // Opacity isn't supported old browsers
		}

		for(i in this.prop) {
			this.start[i] = parseInt(this.elem.css(i));
		}
		this.step();
	},

	update: function() {
		var self = this;
		if(this.timerId) clearTimeout(this.timerId);
		this.timerId = setTimeout(function() {
			self.step();
		}, 13);
	},

	step: function() {
		var t = this.now();

		if ( t > this.duration + this.startTime ) {
			for(i in this.prop) {
				this.elem.css(i, this.prop[i]);
			}

			if(this.onComplete.length > 0) {
				for(var i = 0; i < this.onComplete.length; i++) {
					this.onComplete[i].call();
				}
			}

			return true;
		} else {
			var n = t - this.startTime;
			this.state = n / this.duration;

			for(i in this.prop) {
				this.elem.css(i, this.start[i]+((this.prop[i] - this.start[i]) * this.state));
			}
			this.update();
		}
	}
}
