/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

$(document).ready(function() {
	
	if($('#highchart-container').length > 0){
		
		
		var container = $('#highchart-container');
		
		$.ajax({
			type: "POST",
			dataType: 'json',
			url: getRootURL() + 'Visualizer/VisualizeHighChart/' + container.attr('type') + '/' + container.attr('ref'),
			//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
			success: function(data) {
				$('#highchart-container').highcharts(data);
				
			}
		});
	}
});