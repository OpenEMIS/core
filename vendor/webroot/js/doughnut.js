$(document).ready(function() {
	Doughnut.init();
});

var randomScalingFactor = function(){ return Math.round(Math.random()*130)};
var randomColorFactor = function(){ return Math.round(Math.random()*50)};

var doughnutData = [];

// Test data
var data = [];

var Doughnut = {
	init: function(){
		$('.canvas-holder').each( function(i, obj){
			var arr = [];
			$(obj).next().closest('div').find('div.data').each(function(j, objData){
				var dataKey = $(objData).attr('data-key');
				var dataValue = $(objData).attr('data-value');
				arr.push([dataKey, dataValue]);
			});
			data.push(arr);
		});
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
	getData: function(obj){

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