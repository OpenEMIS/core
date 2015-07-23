$(document).ready(function() {
	Doughnut.init();
});

var randomScalingFactor = function(){ return Math.round(Math.random()*130)};
var randomColorFactor = function(){ return Math.round(Math.random()*50)};

var doughnutData = [];

// Test data
var data = [
	[
		['Male', 10],
		['Female', 20],
		['Mix', 5]
	],
	[
		['North', 10],
		['South', 20],
		['East', 5],
		['West', 9]
	],
	[
		['N', 10],
		['S', 20],
		['E', 5],
		['W', 9]
	]
];

var Doughnut = {
	init: function(){
		var allDoughnuts = document.getElementsByClassName('chart-area');
		for ( i = 0; i < allDoughnuts.length ; i++ ) {
			Doughnut.populate(allDoughnuts[i]);
		}	
	},
	populate: function(doughnutObj){
		var ctx = doughnutObj.getContext("2d");
		window.myDoughnut = new Chart(ctx).Doughnut(doughnutData, {responsive : true});
		$.each(data[i], function (d, dataValue){
			doughnutData.push({
				value: dataValue[1],
	   			color: 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',.7)',
	   			highlight : 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',.7)',
	   			label : dataValue[0]
	    	});
		});
		doughnutData = [];
		window.myDoughnut.update();
	},
	randomize: function(){
		$('#randomizeData').click(function(){
			$.each(doughnutData, function(i, piece){
				doughnutData[i].value = randomScalingFactor();
		    	doughnutData[i].color = 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',.7)';
			});
			window.myDoughnut.update();
		});
	}
}