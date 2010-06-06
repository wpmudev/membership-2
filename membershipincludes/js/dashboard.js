function showTooltip(x, y, contents) {
   jQuery('<div id="afftooltip">' + contents + '</div>').css( {
		position: 'absolute',
        display: 'none',
        top: y + 5,
        left: x + 5,
        border: '1px solid #fdd',
        padding: '2px',
        'background-color': '#fee',
        opacity: 0.80
   }).appendTo("body").fadeIn(200);
}

function memSetWidth() {
	var width = jQuery('#memchartone').parents('div.inner').width();
	jQuery('#memchartone').width((width - 10) + 'px');

	//affvisitgraph
	var colwidth = jQuery('#memcharttwo').parents('div.inner').width();
	jQuery('#memcharttwo').width((colwidth - 10) + 'px');
}

function memReBuildCharts() {
	memReBuildChartOne();
	//memReBuildChartTwo();
}

function memReBuildChartOne() {
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		points: { show: true, barWidth: 1.0 },
		lines: { show: true, barWidth: 1.0 },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.chartoneticks },
		yaxis: { tickDecimals: 0},
		legend: {
		    show: true,
		    position: "nw" }
	  };

	memplot = jQuery.plot(jQuery('#memchartone'), [ {
		data: membershipdata.chartonestats,
		label: "Signups"
	} ], options
	);

	// Chart two
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.charttwoticks },
		yaxis: { tickDecimals: 0},
		legend: {
		    show: true,
		    position: "ne" }
	  };

	memplot = jQuery.plot(jQuery('#memcharttwo'), [ {
		color: 1,
		data: membershipdata.charttwostats,
		label: "Users"
	} ], options
	);

	// Chart three
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.chartthreeticks },
		yaxis: { tickDecimals: 0},
		legend: {
		    show: true,
		    position: "ne" }
	  };

	memplot = jQuery.plot(jQuery('#memchartthree'), [ {
		color: 3,
		data: membershipdata.chartthreestats,
		label: "Users"
	} ], options
	);


}

function memReBuildChartTwo() {
	var options = {
	    lines: { show: true },
	    points: { show: true },
		grid: { hoverable: true },
		xaxis: { tickDecimals: 0, ticks: ticks},
		legend: {
		    show: true,
		    position: "nw" }
	  };

	affplot = jQuery.plot(jQuery('#affvisitgraph'), chart, options);

	var previousPoint = null;
	jQuery("#affvisitgraph").bind("plothover", function (event, pos, item) {
	    if (item) {
	    	if (previousPoint != item.datapoint) {
	        	previousPoint = item.datapoint;

	            jQuery("#afftooltip").remove();
	            var x = item.datapoint[0].toFixed(0),
	            	y = item.datapoint[1].toFixed(0);

	                showTooltip(item.pageX, item.pageY,
	                            y + ' visits');
	        }
		} else {
	    	jQuery("#afftooltip").remove();
			previousPoint = null;
		}
	});
}

function memReportReady() {

	memSetWidth();
	memReBuildCharts();

	jQuery(window).resize( function() {
		memSetWidth();
		memReBuildCharts();
	});

}


jQuery(document).ready(memReportReady);