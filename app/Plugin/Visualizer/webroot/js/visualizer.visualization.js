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
				url: getRootURL() + 'Visualizer/VisualizeHighChart/' + container.attr('type') + '/' + container.attr('ref'),
				//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
				success: function(data) {
					$('#highchart-container').highcharts(data);
				}
			});
		}
		else {
			$.ajax({
				type: "POST",
				dataType: 'json',
				url: getRootURL() + 'Visualizer/VisualizeHighChart/' + container.attr('type') + '/' + container.attr('ref'),
				//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
				success: function(data) {
					$.ajax({
						type: "POST",
						dataType: 'json',
						url: getRootURL() + data['mapURL'], //'HighCharts/map/jor/jor_l02_2014.json',
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
											if (!e.seriesOptions) {
												var chart = this;
												$.ajax({
													type: "POST",
													dataType: 'json',
													url: getRootURL() + e.point.mapURL, //'HighCharts/map/jor/jor_l04_2014.json',
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
							//console.log(processFinalData);
							Highcharts.setOptions({
								lang: {
									drillUpText: '◁ Back'
								}
							});

							$('#highchart-container').highcharts('Map', processFinalData);
						}
					});
				}
			});
			
			
			/*$.ajax({
				type: "POST",
				dataType: 'json',
				url: getRootURL() + 'HighCharts/map/jor/jor_l03_2014.json',
				//data: {searchStr: $(this).val(), areaLvl: $('#areaLevel').val()},
				success: function(mapData) {
					var data = Highcharts.geojson(mapData);

					$.each(data, function(i) {
						this.name = this.properties['ID_'];
						this.value = i;
						this.drilldown = 'test-' + this.properties['ID_'];
					});

					$('#highchart-container').highcharts('Map', {
						chart: {
							events: {
								drilldown: function(e) {
									if (!e.seriesOptions) {
										var chart = this;
										$.ajax({
											type: "POST",
											dataType: 'json',
											url: getRootURL() + 'HighCharts/map/jor/jor_l04_2014.json',
											success: function(mapDrillData) {
												var data = Highcharts.geojson(mapDrillData);
												var finalData = [];
												// Set a non-random bogus value
												$.each(data, function(i) {
													if (this.properties['ID_'].indexOf(e.point.name) >= 0)  {
														this.name = this.properties['ID_'];
														this.value = (Math.random() * 100);
														//this.drilldown = 'part2-test-' + this.properties['ID_'];
														finalData.push( this );
													}
												});

												chart.addSeriesAsDrilldown(e.point, {
													name: 'asdasdasd',
													data: finalData,
													//mapData: data,
													//joinBy: 'ID_',
													dataLabels: {
														enabled: true,
														format: '{point.name}'
													}
												});
												
												console.log(e);
											}
										});

									}
									this.setTitle(null, {text: e.point.drilldown});
								},
								drillup: function(e) {
									this.setTitle(null, {text: 'Jordan - Test'});
								}
							}
						},
						title: {
							text: 'Highmaps basic demo'
						},
						subtitle: {
							text: 'Jordan - Main'
						},
						mapNavigation: {
							enabled: true,
							buttonOptions: {
								verticalAlign: 'bottom'
							}
						},
						legend: {
							layout: 'vertical',
							align: 'right',
							verticalAlign: 'middle'
						},
						colorAxis: {
							min: 0
						},
						series: [{
								data: data,
								//	mapData: mapData, //Highcharts.maps['countries/jo/jo-all'],

								name: '{point.name}',
								states: {
									hover: {
										color: '#BADA55'
									}
								},
								dataLabels: {
									enabled: true,
									//format: '{point.name}'
								}
							}],
						drilldown: {
							//series: drilldownSeries,
							//animation: false,
							activeDataLabelStyle: {
								color: 'white',
								textDecoration: 'none'
							},
							drillUpButton: {
								relativeTo: 'spacingBox',
								position: {
									x: 0,
									y: 60
								}
							}
						}
					});
				}
			});
			// Initiate the chart
*/
		}
	}
});