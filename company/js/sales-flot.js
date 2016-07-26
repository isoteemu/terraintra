function plotSales(dataset, el) {

	this.el = el;

	this.tooltip = {}

	var i = 0;
	$.each(dataset, function(key, val) {
		val.color = i;
		++i;
	});

	var self = this;

	var idx=0;
	$.plot($(this.el), dataset, {
		xaxis: {
			mode: "time",
			minTickSize: [3, "month"],
			tickFormatter: self.formatQuarter
		},
		yaxis: {
			tickFormatter: self.formatMoney
		},
		legend: {
		},
		lines: { show: true },
		points: { show: true },
		grid: {
			hoverable: true
		},
	});

	this.previousPoint = null;

	$(el).bind("plothover", function(event, pos, item) {
		if(item) {
			if(self.previousPoint != item.datapoint) {
				self.previousPoint = item.datapoint;
				self.showTooltip(pos, item);
			}
		} else if(self.previousPoint) {
			self.previousPoint = null;
			self.hideTooltip();
		}
	}).bind("mouseout", this.hideTooltip);

}

plotSales.prototype.formatMoney = function(int) {
	return int.toLocaleString();
}

plotSales.prototype.formatQuarter = function(val, axis) {
	var d = new Date(val);
	var q = Math.ceil((d.getUTCMonth()+1)/3);
	var y = d.getFullYear();
	return "Q"+q+"/"+y;
}

plotSales.prototype.showTooltip = function(position, item) {
	var txt = this.formatQuarter(item.datapoint[0])+": "
		+this.formatMoney(item.datapoint[1])+"€";

	var offset = $(this.el).offset();

	this.tooltip = $(this.el).qtip({
		content: txt,
		style: {
			tip: true,
		},
		position: {
			target: "mouse",
			adjust: {
				x: position.pageX-offset.left,
				y: position.pageY-offset.top,
				mouse: false
			},
			corner: {
				target: 'rightMiddle',
				tooltip: 'leftMiddle'
			}
		},
		show: {
			ready: true,
			solo: true
		},
		hide: {
			when: {
				event: "hideTooltip"
			}
		}
	});
}



plotSales.prototype.hideTooltip = function() {
	if(this.tooltip)
		this.tooltip.qtip('destroy');
}
