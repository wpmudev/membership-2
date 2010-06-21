function memSetWidth() {
	var width = jQuery('#memchartone').parents('div.inner').width();
	jQuery('#memchartone').width((width - 10) + 'px');

	//affvisitgraph
	var colwidth = jQuery('#memcharttwo').parents('div.inner').width();
	jQuery('#memcharttwo').width((colwidth - 10) + 'px');

	var colwidth = jQuery('#memchartthree').parents('div.inner').width();
	jQuery('#memchartthree').width((colwidth - 10) + 'px');
}

function memReBuildCharts() {
	memReBuildChartOne();
	memReBuildChartTwo();
	memReBuildChartThree();
}

function memReBuildChartOne() {
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		points: { show: true, barWidth: 1.0 },
		lines: { show: true, barWidth: 1.0 },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.chartoneticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "nw" }
	  };

	memplot = jQuery.plot(jQuery('#memchartone'), [ {
		data: membershipdata.chartonestats,
		label: membership.signups
	} ], options
	);


}

function memReBuildChartTwo() {
	// Chart two
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.charttwoticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "ne" }
	  };

	memplot = jQuery.plot(jQuery('#memcharttwo'), [ {
		color: 1,
		data: membershipdata.charttwostats,
		label: membership.members
	} ], options
	);
}

function memReBuildChartThree() {
	// Chart three
	var options = {
	    bars: { show: true, barWidth: 1.0, align: "center" },
		grid: { hoverable: true, backgroundColor: { colors: ["#fff", "#eee"] } },
		xaxis: { ticks: membershipdata.chartthreeticks },
		yaxis: { tickDecimals: 0, min: 0},
		legend: {
		    show: true,
		    position: "ne" }
	  };

	memplot = jQuery.plot(jQuery('#memchartthree'), [ {
		color: 3,
		data: membershipdata.chartthreestats,
		label: membership.members
	} ], options
	);
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