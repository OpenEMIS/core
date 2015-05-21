<div id="canvas-holder">
	<canvas id="chart-area"/>
</div>

<script>

		var randomScalingFactor = function(){ return Math.round(Math.random()*130)};
		var randomColorFactor = function(){ return Math.round(Math.random()*50)};

		var doughnutData = [
				{
					value: randomScalingFactor(),
					color:"#5C82CC",
					highlight: "#3366CC",
					label: "Male"
				},
				{
					value: randomScalingFactor(),
					color: "#CC5C5C",
					highlight: "#CC3333",
					label: "Female"
				},
			];

			window.onload = function(){
				var ctx = document.getElementById("chart-area").getContext("2d");
				window.myDoughnut = new Chart(ctx).Doughnut(doughnutData, {responsive : true});
			};

			$('#randomizeData').click(function(){
				$.each(doughnutData, function(i, piece){
					doughnutData[i].value = randomScalingFactor();
			    	doughnutData[i].color = 'rgba(' + randomColorFactor() + ',' + randomColorFactor() + ',' + randomColorFactor() + ',.7)';
				});
		    	window.myDoughnut.update();
		    });



</script>