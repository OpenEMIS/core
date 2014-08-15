/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */


$(document).ready(function() {

	if ($('#highchart-container').length > 0) {
		var container = $('#highchart-container');
		if (container.attr('type') != 'map') {
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: getRootURL() + container.attr('url'),
				//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
				success: function(data) {
					if (typeof data['errorMsg'] !== 'undefined') {
						$(data['errorMsg']).insertBefore('#visualizer .navbar');
					}
					else {
						$('#highchart-container').highcharts(data);
					}

				}
			});
		}
		else {
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: getRootURL() + container.attr('url'),
				//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
				success: function(data) {
					if (typeof data['errorMsg'] !== 'undefined') {
						$(data['errorMsg']).insertBefore('#visualizer .navbar');
					}
					else {
						$.ajax({
							type: "POST",
							dataType: 'json',
							url: getRootURL() + data['mapURL'],
							//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
							success: function(mapData) {
								var mData = Highcharts.geojson(mapData);

								var finalMapData = [];
								$.each(data['dbData'], function(i) {
									var selectedObj = this;
									$.each(mData, function(s) {
										if (this.properties['ID_'] == selectedObj.ID_) {
											var singleData = $.extend(this, selectedObj);

											finalMapData.push(singleData);
											return false;
										}
									});
								});

								var processFinalData = data['mapChartInfo'];
								processFinalData['series'][0]['data'] = finalMapData;
								processFinalData['series'][0]['mapData'] = mapData;
								var chartSetting = {
									chart: {
										events: {
											drilldown: function(e) {
												console.log(e);
												if (!e.seriesOptions) {
													console.log(e.point.loadSchool);
													var chart = this;
													$.ajax({
														type: "POST",
														dataType: 'json',
														url: getRootURL() + e.point.mapURL, 
														success: function(mapDrillData) {
															var mpData = Highcharts.geojson(mapDrillData);
															var finalData = [];
															// Set a non-random bogus value
															$.each(data['dbData'], function(i) {
																var selectedObj = this;
																$.each(mpData, function(s) {
																	if (this.properties['ID_'] == selectedObj.ID_) {
																		$.extend(this, selectedObj);

																		finalData.push(this);
																		return false;
																	}
																});
															});
															chart.addSeriesAsDrilldown(e.point, {
																name: e.point.name,
																data: finalData,
																mapData: mpData,
																joinBy: 'ID_',
																dataLabels: {
																	enabled: true,
																	format: '{point.Area_Name}'
																}
															});

														}
													});

												}
												//	this.setTitle(null, {text: e.point.drilldown});
											},
											//drillup: function(e) {
											//	this.setTitle(null, {text: 'Jordan - Test'});
											//}
										}
									}
								};

								$.extend(processFinalData, chartSetting);
								Highcharts.setOptions({
									lang: {
										drillUpText: '◁ Back'
									}
								});

								$('#highchart-container').highcharts('Map', processFinalData);
							}
						});
					}

				}
			});
		}
	}
});