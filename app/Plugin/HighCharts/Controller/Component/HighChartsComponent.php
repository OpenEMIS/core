<?php

/*
  @OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

  OpenEMIS
  Open Education Management Information System

  Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by the Free Software Foundation
  , either version 3 of the License, or any later version.  This program is distributed in the hope
  that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
  or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
  have received a copy of the GNU General Public License along with this program.  If not, see
  <http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
 */

App::uses('Component', 'Controller');

class HighChartsComponent extends Component {

	private $controller;
	private $chartHeaderData = null;
	private $labelRotationValue = 270;
	public $selectedDimensions;
	public $selectedIndicator;
	public $selectedAreas;
	public $selectedAreaID;
	public $selectedUnits;
	public $selectedTimeperiods;
	public $selectedTimeperiodID;
	public $selectedIUS;

	/*
	 * For Map purpose
	 */
	public $selectedMapAreaLevel;
	public $dbAllLevel;
	public $selectedCountry;

	/*
	 *  types for var plotBy - 'subgroup', 'indicator'
	 */
	public $plotBy = 'subgroup';

	/*
	 *  Rotate the label on xAxis except bar chart is on yAxis
	 */
	public $rotateLabel = false;

	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller = & $controller;
	}

	//called after Controller::beforeFilter()
	public function startup(Controller $controller) {
		
	}

	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) {
		
	}

	//called after Controller::render()
	public function shutdown(Controller $controller) {
		
	}

	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) {
		
	}

	public function initVariables($IUSData, $AreaData, $TimeperiodData) {
		$this->selectedIndicator = array_values(array_unique(array_map(function ($i) {
							return $i['Indicator']['Indicator_Name'];
						}, $IUSData)));

		$this->selectedDimensions = array_values(array_unique(array_map(function ($i) {
							return $i['SubgroupVal']['Subgroup_Val'];
						}, $IUSData)));

		$this->selectedUnits = array_values(array_unique(array_map(function ($i) {
							return $i['Unit']['Unit_Name'];
						}, $IUSData)));

		$this->selectedIUS = array_values(array_unique(array_map(function ($i) {
							return $i['IndicatorUnitSubgroup']['IUSNId'];
						}, $IUSData)));

		$this->selectedAreaID = array_values(array_unique(array_map(function ($i) {
							return $i['DIArea']['Area_Name'];
						}, $AreaData)));

		$this->selectedTimeperiodID = array_values(array_unique(array_map(function ($i) {
							return $i['TimePeriod']['TimePeriod'];
						}, $TimeperiodData)));

		$this->selectedAreas = $AreaData;
		$this->selectedTimeperiods = $TimeperiodData;
	}

	public function getAreaIds() {
		$data = array_unique(array_map(function ($i) {
					return $i['DIArea']['Area_Nid'];
				}, $this->selectedAreas));

		return $data;
	}

	public function getTimeperiodIds() {
		$data = array_unique(array_map(function ($i) {
					return $i['TimePeriod']['TimePeriod_NId'];
				}, $this->selectedTimeperiods));

		return $data;
	}

	public function getChartData($ctype, $DIData) {
		$chartTypeInfo = explode("-", $ctype);
		$chartType = $chartTypeInfo[0];
		$chartData = $this->customGenerateHeader(array('chartType' => $chartType, 'caption' => $this->getCaption(), 'subcaption' => $this->getSubCaption()));

		switch ($chartType) {
			case 'pie':
				$chartData = array_merge($chartData, $this->getPieChartData($DIData));
				break;
			case 'scatter':
				$chartData = array_merge($chartData, $this->getScatterChartData($DIData));
				$plotOptions['totalDisplayRecords'] = count($DIData);
				break;
			default :
				$chartData = array_merge($chartData, $this->getGenericChartData($chartType, $DIData));
		}

		$plotOptions['stacking'] = !empty($chartTypeInfo[1]) ? $chartTypeInfo[1] : null;
		$chartData = array_merge($chartData, $this->getPlotOptions($chartType, $plotOptions));


		$chartData['xAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['xAxis']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['xAxis']['plotBands']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['plotBands']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['plotOptions']['series']['dataLabels']['useHTML'] = 'js:Highcharts.hasBidiBug';

		return json_encode($chartData, JSON_NUMERIC_CHECK);
	}

	/* ================================================
	 * Customized Functions for DIY purpose
	 * ================================================ */

	public function customGenerateHeader($_options) {
		$chartData['credits']['enabled'] = false;
		$chartData['chart']['type'] = !empty($_options['chartType']) ? $_options['chartType'] : 'column';
		$chartData['chart']['zoomType'] = !empty($_options['zoomType']) ? $_options['zoomType'] : 'xy';
		if (!empty($_options['caption'])) {
			$chartData['title']['text'] = __($_options['caption']);
		}
		if (!empty($_options['subcaption'])) {
			$chartData['subtitle']['text'] = __($_options['subcaption']);
		}
		$chartData['tooltip']['useHTML'] = true;
		$chartData['legend']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['subtitle']['useHTML'] = 'js:Highcharts.hasBidiBug';
		//$chartData['chart']['resetZoomButton']['useHTML'] = true;
		//export
		$chartData = array_merge($chartData, $this->initExportSetup());

		return $chartData;
	}

	public function customGenerateCategory($chartType) {
		$linebreak = $this->getChartBreak($chartType);
		$options['totalColumn'] = count($this->selectedIndicator);
		$options['chartType'] = $chartType;

		switch ($chartType) {
			case 'line':
				$data = $this->sortTimeAsCatergory();
				break;
			case 'column':
			case 'bar':
			default:
				$data = $this->sortIndicatorAsCatergory();
				break;
		}

		$chartData = $this->populateChartCategory($data, $options);
		return $chartData;
	}

	public function setupCustomTextChartCategory() {
		$chartData = array();
		switch ($this->plotBy) {
			case 'indicator':
				$chartData['xAxis']['title']['text'] = $this->selectedIndicator[0];
				$chartData['yAxis']['title']['text'] = $this->selectedIndicator[1];
				break;
			case 'dimension':
			case 'subgroup':
			default:
				$chartData['xAxis']['title']['text'] = $this->selectedDimensions[0];
				$chartData['yAxis']['title']['text'] = $this->selectedDimensions[1];
				break;
		}
		$chartData['yAxis']['min'] = 0;
		return $chartData;
	}

	public function customGetGenericChartData($chartType, $DIData) {
		$chartData = $this->customGenerateCategory($chartType);
		$chartData = array_merge($chartData, $this->setupChartDataset($DIData, $this->getChartBreak($chartType)));
		return $chartData;
	}

	public function customGetScatterChartData($DIData) {
		$chartType = 'scatter';
		$chartData = $this->setupScatterChartDataset($DIData, $this->getChartBreak($chartType));
		$chartData = array_merge($chartData, $this->getPlotOptions($chartType, array('totalDisplayRecords' => count($DIData))));
		return $chartData;
	}

	public function customGetLineChartData($DIData) {
		$chartType = 'line';
		$chartData = $this->customGenerateCategory($chartType);
		$chartData = array_merge($chartData, $this->setupLineChartDataset($DIData, $this->getChartBreak($chartType)));
		return $chartData;
	}

	public function getPlotOptions($type, $_options) {
		$chartData = array();
		if (!empty($_options['stacking']) && ($type == 'bar' || $type == 'column')) {
			$chartData['plotOptions'][$type]['stacking'] = 'normal';
		} else if ($type == 'scatter') {
			$chartData['plotOptions'][$type]['tooltip']['headerFormat'] = ''; // NULL;
			$chartData['plotOptions'][$type]['tooltip']['pointFormat'] = '<span style="fill:{series.color}">●</span><span style="font-size: 10px; font-weight:bold"> {point.header}</span><br/>{point.titlex}: <b>{point.x}</b><br/>{point.titley}: <b>{point.y}</b>';
			if (!empty($_options['totalDisplayRecords'])) {
				$chartData['plotOptions'][$type]['turboThreshold'] = $_options['totalDisplayRecords'];
			}
		}
		//$chartData['plotOptions']['series']['dataLabels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		return $chartData;
	}

	/* ================================================
	 * populating data into highchart format
	 * ================================================ */

	private function getGenericChartData($chartType, $DIData) {
		$chartData = $this->setupChartCategory($chartType);
		$chartData = array_merge($chartData, $this->setupChartDataset($DIData, $this->getChartBreak($chartType)));
		return $chartData;
	}

	private function getPieChartData($DIData) {
		$chartData = $this->setupPieChartDataset($DIData);
		return $chartData;
	}

	private function getScatterChartData($DIData) {
		$chartData = $this->setupCustomTextChartCategory();
		$chartData = array_merge($chartData, $this->setupScatterChartDataset($DIData));
		return $chartData;
	}

	public function setupChartCategory($chartType) {
		$linebreak = $this->getChartBreak($chartType);
		$options['totalColumn'] = count($this->selectedTimeperiods) * count($this->selectedAreas);
		$options['chartType'] = $chartType;

		$data = $this->sortTimeAreaAsCategory($linebreak);
		$chartData = $this->populateChartCategory($data, $options);
		return $chartData;
	}

	private function setupChartDataset($data, $linebreak = false) {
		$chartData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		switch ($this->plotBy) {
			case 'indicator':
				$selectedFilterGrp = $this->selectedAreaID;
				break;
			case 'dimension':
			case 'subgroup':
			default:
				$selectedFilterGrp = $this->selectedDimensions;
				break;
		}
		//Format Data to fusionchart structure
		$counter = 0;
		foreach ($dataStructure as $iKey => $sData) {
			foreach ($sData as $sKey => $yData) {
				foreach ($yData as $yKey => $aData) {
					foreach ($aData as $aKey => $vObj) {
						switch ($this->plotBy) {
							case 'indicator':
								$selectedKey = $aKey;
								break;
							case 'dimension':
							case 'subgroup':
							default:
								$selectedKey = $sKey;
								break;
						}

						//	$selectedKey = ($this->plotBy == 'subgroup')?$sKey:$aKey;
						$counterKey = array_search($selectedKey, $selectedFilterGrp);
						$selectedCounter = $counterKey;
						$chartData['series'][$counterKey]['data'][] = $vObj;
						$chartData['series'][$counterKey]['name'] = $selectedKey;
					}
					if ($linebreak) {
						$chartData['series'][$selectedCounter]['data'][] = null;
					}
				}
				//$counter++;
			}
			//	
		}

		//pr($chartData);
		return $chartData;
	}

	//Currently is being use in dashboard only
	private function setupLineChartDataset($data) {
		$chartData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		$selectedFilterGrp = $this->selectedIndicator;
		//Format Data to fusionchart structure
		//$counter = 0;
		foreach ($dataStructure as $iKey => $sData) {
			foreach ($sData as $sKey => $yData) {
				foreach ($yData as $yKey => $aData) {
					foreach ($aData as $aKey => $vObj) {
						$selectedKey = $iKey;

						//	$selectedKey = ($this->plotBy == 'subgroup')?$sKey:$aKey;
						$counterKey = array_search($selectedKey, $selectedFilterGrp);
						$chartData['series'][$counterKey]['data'][] = $vObj;
						$chartData['series'][$counterKey]['name'] = $selectedKey;
					}
				}
			}
			//$counter++;
		}

		return $chartData;
	}

	private function setupPieChartDataset($data, $linebreak = false) {
		$chartData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		//Format Data to fusionchart structure
		$counter = 0;
		foreach ($dataStructure as $iKey => $sData) {
			foreach ($sData as $sKey => $yData) {
				foreach ($yData as $yKey => $aData) {
					foreach ($aData as $aKey => $vObj) {
						$chartData['series'][$counter]['data'][] = array('name' => sprintf('%s - %s (%s) : %s', $yKey, $aKey, $iKey, $vObj), 'y' => $vObj);
						$chartData['series'][$counter]['name'] = $this->selectedUnits[0];
					}
				}
			}
		}
		return $chartData;
	}

	private function setupScatterChartDataset($data, $linebreak = false) {
		$chartData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		foreach ($dataStructure as $iKey => $sData) {
			foreach ($sData as $sKey => $yData) {
				foreach ($yData as $yKey => $aData) {
					$yCounter = array_search($yKey, $this->selectedTimeperiodID);

					switch ($this->plotBy) {
						case 'indicator':
							$counter = array_search($iKey, $this->selectedIndicator);
							$axisName = $iKey;
							break;
						case 'dimension':
						case 'subgroup':
						default:
							$counter = array_search($sKey, $this->selectedDimensions);
							$axisName = $sKey;
							break;
					}
					$axis = ($counter % 2 == 0) ? 'x' : 'y';

					$chartData['series'][$yCounter]['name'] = $yKey;

					foreach ($aData as $aKey => $vObj) {
						$aCounter = array_search($aKey, $this->selectedAreaID);

						$chartData['series'][$yCounter]['data'][$aCounter][$axis] = $vObj;
						$chartData['series'][$yCounter]['data'][$aCounter]['title' . $axis] = $axisName;
						$chartData['series'][$yCounter]['data'][$aCounter]['header'] = sprintf('%s - %s', $yKey, $aKey);
					}
				}
			}
		}
		return $chartData;
	}

	private function getCaption() {
		$caption = $this->selectedIndicator[0];//sprintf('%s - %s', $this->selectedIndicator[0], $this->selectedUnits[0]);
		return $caption;
	}

	private function getSubCaption($year = NULL) {
		if(empty($year)){
			$year = $this->getYearSubcaption();
		}
		$year = isset($year)? $year : $this->getYearSubcaption();
		$caption = sprintf('Year : %s  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Unit : %s', $year, $this->selectedUnits[0]);
		return $caption;
	}
	
	public function getYearSubcaption($timeperiod = NULL) {
		
		if(empty($timeperiod)){
			$timeperiod = $this->selectedTimeperiods;
		}
		$yearRange = array_unique(array_map(function ($i) {
					return $i['TimePeriod']['TimePeriod'];
				}, $timeperiod));
		sort($yearRange);
		$yearSubcaption = implode(', ', $yearRange) ;

		return $yearSubcaption;
	}

	private function getChartBreak($type) {
		switch ($type) {
			case 'line':
			case 'area':
				return true;
				break;
			default :
				return false;
		}
	}

	private function getLabelRotate($type) {
		switch ($type) {
			case 'column':
			case 'line':
			case 'area':
				return true;
				break;
			default :
				return false;
		}
	}

	private function getTempDataStructure() {
		$chartData = array();
		foreach ($this->selectedIndicator as $indObj) {
			foreach ($this->selectedTimeperiods as $timeObj) {
				$timeValue = $timeObj['TimePeriod']['TimePeriod'];
				foreach ($this->selectedAreas as $areaObj) {
					$areaValue = $areaObj['DIArea']['Area_Name'];
					foreach ($this->selectedDimensions as $dimensionObj) {
						$chartData[$indObj][$dimensionObj][$timeValue][$areaValue] = 0;
					}
				}
			}
		}

		return $chartData;
	}

	private function reformatDataWithNewStructure($data) {
		$dataStructure = $this->getTempDataStructure();

		foreach ($data as $key => $row) {
			$timeValue = $row['TimePeriod']['TimePeriod'];
			$areaValue = $row['DIArea']['Area_Name'];
			$dimensionValue = $row['SubgroupVal']['Subgroup_Val'];
			$dataValue = $row['DIData']['Data_Value'];
			$indValue = $row['Indicator']['Indicator_Name'];
			$dataStructure[$indValue][$dimensionValue][$timeValue][$areaValue] = $dataValue;
		}

		return $dataStructure;
	}

	private function initExportSetup() {
		$chartData['exporting']['filename'] = 'Visualizer_Chart_' . date("Y-m-d");
		$chartData['exporting']['sourceWidth'] = 800;
		$chartData['exporting']['sourceHeight'] = 600;

		return $chartData;
	}

	private function sortIndicatorAsCatergory() {
		$data = array();
		foreach ($this->selectedIndicator as $indObj) {
			$data[] = $indObj;
		}
		return $data;
	}

	private function sortTimeAsCatergory() {
		$data = array();
		foreach ($this->selectedTimeperiods as $timeObj) {
			$data[] = $timeObj['TimePeriod']['TimePeriod'];
		}
		return $data;
	}

	private function sortTimeAreaAsCategory($linebreak = false) {
		$data = array();
		foreach ($this->selectedTimeperiods as $timeObj) {
			foreach ($this->selectedAreas as $areaObj) {
				$data[] = sprintf('%s - %s', $timeObj['TimePeriod']['TimePeriod'], $areaObj['DIArea']['Area_Name']);
			}
			if ($linebreak) {
				$data[] = '';
			}
		}
		return $data;
	}

	private function populateChartCategory($data, $_options) {
		$totalColumn = !empty($_options['totalColumn']) ? $_options['totalColumn'] : 1;
		$rotateLabel = $this->getLabelRotate($_options['chartType']);

		$chartData = array();
		foreach ($data as $displayStr) {
			$chartData['xAxis']['categories'][] = $displayStr; //sprintf('%s - %s', $indObj['TimePeriod']['TimePeriod'], $indObj['DIArea']['Area_Name']);

			if ($totalColumn > 7 && $rotateLabel) {
				$chartData['xAxis']['labels']['rotation'] = $this->labelRotationValue;
			}

			$chartData['xAxis']['labels']['maxStaggerLines'] = 1;
		}
		$chartData['yAxis']['min'] = 0;
		$chartData['yAxis']['title']['text'] = $this->selectedUnits[0];
		return $chartData;
	}

	/* ===================================================================
	 * 					Map Component
	 * =================================================================== */

	public function initMapAreaInfo($areaLevel, $dbAllLevel, $countryData) {
		$this->selectedMapAreaLevel = array_values(array_unique(array_map(function ($i) {
							return $i['DIArea']['Area_Level'];
						}, $areaLevel)));

	//	$dbAreaLvl = $dbAllLevel[count($dbAllLevel) - 2];
		$this->dbAllLevel = $dbAllLevel;//['AreaLevel']['Area_Level'];
		$this->selectedCountry = $countryData;
	}

	public function getMapData($DIData) {
		$this->checkFunctionExist();
		
		$data = $this->mapInit(array('caption' => $this->getCaption(), 'subcaption' => $this->getSubCaption($this->selectedTimeperiods[0]['TimePeriod']['TimePeriod'])));
		$foramtedDBData = $this->mapFormatDBData($DIData);
		//$file = file_get_contents($rootURL.'HighCharts/map/jor/jor_l03_2004.json', FILE_USE_INCLUDE_PATH);
	
		$seriesOptions['hoverColor'] = '#BADA55';
		$seriesOptions['dataLabels']['enabled'] = true;
		$seriesOptions['dataLabels']['format'] = '{point.Area_Name}';
		$seriesOptions['dataLabels']['conutry_name'] = $this->selectedCountry['DIArea']['Area_Name'];
		$data = array_merge($data, $this->mapSeriesData($seriesOptions));
//pr($foramtedDBData);

		$finalData['dbData'] = $foramtedDBData;
		$finalData['mapChartInfo'] = $data;
		reset($this->selectedMapAreaLevel);
		//pr('init current = '.current($this->selectedMapAreaLevel));
		$finalData['mapURL'] = $this->mapGetLevel(array('level'=>current($this->selectedMapAreaLevel)));
		
		return json_encode($finalData, JSON_NUMERIC_CHECK);
	}

	private function mapInit($_options) {
		$mapData['title']['text'] = !empty($_options['caption']) ? $_options['caption'] : 'Map';
		$mapData['title']['useHTML'] = true;

		if (!empty($_options['subcaption'])) {
			$mapData['subtitle']['text'] = $_options['subcaption'];
			$mapData['subtitle']['useHTML'] = true;
		}

		$mapData['mapNavigation']['enabled'] = true;
		$mapData['mapNavigation']['buttonOptions']['verticalAlign'] = 'bottom';

		$mapData['legend']['layout'] = 'vertical';
		$mapData['legend']['align'] = 'right';
		$mapData['legend']['verticalAlign'] = 'middle';

		$mapData['colorAxis']['min'] = 0;
		$mapData['credits']['enabled'] = false;
		return $mapData;
	}

	private function mapSeriesData($_options = NULL) {
		$seriesOptions = array();
		$seriesOptions['tooltip']['headerFormat'] = '';
		$seriesOptions['tooltip']['pointFormat'] = '<span style="fill:{series.color}">●</span><span style="font-size: 10px; font-weight:bold"> {point.ID_Name}</span><br/>{point.dimension}: <b>{point.value}</b>';

		if (!empty($_options['hoverColor'])) {
			$seriesOptions['series'][0]['states']['hover']['color'] = $_options['hoverColor'];
		}

		if (isset($_options['dataLabels']['enabled'])) {
			$seriesOptions['series'][0]['dataLabels']['enabled'] = $_options['dataLabels']['enabled'];
			$seriesOptions['series'][0]['dataLabels']['color'] = 'white';
			$seriesOptions['series'][0]['joinBy'] = 'ID_';
			$seriesOptions['series'][0]['name'] = $_options['dataLabels']['conutry_name'];
			//	$seriesOptions['plotOptions']['series']['dataLabels']['useHTML'] = 'js:Highcharts.hasBidiBug';

			if (!empty($_options['dataLabels']['format'])) {
				//format eg : '{point.name}'
				$seriesOptions['series'][0]['dataLabels']['format'] = $_options['dataLabels']['format'];
			}
			
		//	if (!empty($_options['city']['enabled'])) {
			/*	$seriesOptions['series'][1]['name'] = 'Cities';
				$seriesOptions['series'][1]['type'] = 'mappoint';
				$seriesOptions['series'][1]['color'] = 'red';
				$seriesOptions['series'][1]['marker'] = array('radius'=> 3);
				$seriesOptions['series'][1]['dataLabels'] = array('color'=> 'white', 'borderColor'=> 'green');
				$seriesOptions['series'][1]['data'] = array(
					array('name' => 'Johor', 'properties' => array('name' => 'Johor - 2'), 'x' => 35.5, 'y'=>-30),
				//	array('name' => 'Singapore', 'properties' => array('name' => 'sg - 2'), 'x' => 150, 'y'=>50)
					);*/
		//	}
		}

		$seriesOptions['drilldown']['animation'] = false;
		$seriesOptions['drilldown']['activeDataLabelStyle']['color'] = 'white';
		$seriesOptions['drilldown']['activeDataLabelStyle']['textDecoration'] = 'none';
		$seriesOptions['drilldown']['drillUpButton']['relativeTo'] = 'spacingBox';
		$seriesOptions['drilldown']['drillUpButton']['position'] = array('x' => 0, 'y' => 60);


		return $seriesOptions;
	}

	private function mapFormatDBData($DIData) {
		$data = array();
		$latestYearObj = $this->selectedTimeperiods[0];
		$singleDimension = $this->selectedDimensions[0];


		foreach ($DIData as $obj) {
			if ($obj['TimePeriod']['TimePeriod'] == $latestYearObj['TimePeriod']['TimePeriod'] && $obj['SubgroupVal']['Subgroup_Val'] == $singleDimension) {
					//pr($obj);
				$tempArr = array();
				$tempArr['ID_'] = $obj['DIArea']['Area_ID'];
				$tempArr['Area_Name'] = $obj['DIArea']['Area_Name'];
				$tempArr['ID_Name'] = sprintf('%s - %s', $obj['DIArea']['Area_ID'], $obj['DIArea']['Area_Name']);
				$tempArr['Area_Nid'] = $obj['DIArea']['Area_NId'];
				$tempArr['value'] = $obj['DIData']['Data_Value'];
				$tempArr['TimePeriod'] = $obj['TimePeriod']['TimePeriod'];
				$tempArr['dimension'] = $obj['SubgroupVal']['Subgroup_Val'];
				$tempArr['dimension'] = $obj['DIArea']['Area_Level'];

				$nextLevel = $this->getCheckDrillDown($obj['DIArea']['Area_Level']);
				if (!empty($nextLevel)) {
					$tempArr['mapURL'] = $this->mapGetLevel(array('level'=>$nextLevel)); 
					$tempArr['drilldown'] = $obj['DIArea']['Area_ID']; 
				}
				

				$data[] = $tempArr;
			}
		}//pr('----');
		return $data;
	}

	private function getCheckDrillDown($level) {
		$firstLevel = $this->selectedMapAreaLevel[0];
		$lastLevel = $this->selectedMapAreaLevel[count($this->selectedMapAreaLevel)-1];
		$lastDrillDownLevel = $this->dbAllLevel[count($this->dbAllLevel) - 2]['AreaLevel']['Area_Level'];
		
		if(count($this->selectedMapAreaLevel) == 1){
			return false;
		}
		
		reset($this->selectedMapAreaLevel);
		while (current($this->selectedMapAreaLevel) !== $level) {
			next($this->selectedMapAreaLevel);
			$nextItem = current($this->selectedMapAreaLevel);
		}
		next($this->selectedMapAreaLevel);
		$nextItem = current($this->selectedMapAreaLevel);
		if($nextItem == $firstLevel || $lastDrillDownLevel < $nextItem){
			$nextItem = false;
		}
		
		return $nextItem;
	}

	private function mapGetLevel($_options) {
		$level = $_options['level'];
		$lastDrillDownLevel = $this->dbAllLevel[count($this->dbAllLevel) - 2]['AreaLevel']['Area_Level'];
//		pr($_options);
		$lastLevel = $this->selectedMapAreaLevel[count($this->selectedMapAreaLevel)-1];
	
		$diffValue = ($level > $lastDrillDownLevel)? 0:1;
		$finalItemLevel = $level + $diffValue;
	//	pr($finalItemLevel);
		$controller = isset($_options['controller'])?$_options['controller'] : 'Visualizer';
		//$url = 'HighCharts/map/jor/jor_l0' . $finalItemLevel . '_2014.json';
		$url = $controller.DS.'loadJsonMap'.DS.$this->selectedCountry['DIArea']['Area_ID'].DS.$finalItemLevel;
		return $url;
	}

	private function checkFunctionExist(){
		if (!method_exists($this->controller, 'loadJsonMap')) {
			pr('function loadJsonMap(country, level) must be implemented.');
			$this->log('function loadJsonMap(country, level) must be implemented.', 'debug');
		}
	}
	/* ======================| End Map Component |======================== */
}

?>