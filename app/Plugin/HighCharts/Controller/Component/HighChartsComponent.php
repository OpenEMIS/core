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
		$this->init();
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

	public function init() {
		
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
		$chartData = $this->customGenerateHeader(array('chartType' => $chartType, 'caption' => $this->getCaption(), 'subcaption' => $this->getYearSubcaption()));

		//export
		$chartData = array_merge($chartData, $this->initExportSetup());

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
		return $chartData;
	}

	public function customGenerateCategory($chartType) {
		$linebreak = $this->getChartBreak($chartType);
		$totalColumn = count($this->selectedIndicator);
		$rotateLabel = $this->getLabelRotate($chartType);

		switch($chartType){
			case 'line':
				$chartData = $this->sortTimeAsCatergory($totalColumn, $rotateLabel);
				break;
			case 'column':
			case 'bar':
			default:
				$chartData = $this->sortIndicatorAsCatergory($totalColumn, $rotateLabel);
				break;
		}
		/*$chartData['xAxis']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['xAxis']['plotBands']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['plotBands']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';*/
		return $chartData;
	}
	
	public function setupCustomTextChartCategory() {
		$chartData = array();
		//$chartData['xAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		//$chartData['yAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
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
	
	public function customGetLineChartData($DIData){
		$chartType = 'line';
		$chartData = $this->customGenerateCategory($chartType);
		$chartData =  array_merge($chartData,$this->setupLineChartDataset($DIData, $this->getChartBreak($chartType)));
		return $chartData;
	}

	public function getPlotOptions($type, $_options) {
		$chartData = array();
		if (!empty($_options['stacking']) && ($type == 'bar' || $type == 'column')) {
			$chartData['plotOptions'][$type]['stacking'] = 'normal';
		} else if ($type == 'scatter') {
			$chartData['plotOptions'][$type]['tooltip']['headerFormat'] = '';// NULL;
			$chartData['plotOptions'][$type]['tooltip']['pointFormat'] = '<span style="fill:{series.color}">●</span><span style="font-size: 10px; font-weight:bold"> {point.header}</span><br/>{point.titlex}: <b>{point.x}</b><br/>{point.titley}: <b>{point.y}</b>';
			if(!empty($_options['totalDisplayRecords'])){
				$chartData['plotOptions'][$type]['turboThreshold']= $_options['totalDisplayRecords'];
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
		$totalColumn = count($this->selectedTimeperiods) * count($this->selectedAreas);
		$rotateLabel = $this->getLabelRotate($chartType);
		
		$chartData = $this->sortTimeAreaAsCatergory($totalColumn, $rotateLabel, $linebreak);
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
	private function setupLineChartDataset($data, $linebreak = false) {
		$chartData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		$selectedFilterGrp = $this->selectedIndicator;
		//Format Data to fusionchart structure
		$counter = 0;
		foreach ($dataStructure as $iKey => $sData) {
			foreach ($sData as $sKey => $yData) {
				foreach ($yData as $yKey => $aData) {
					foreach ($aData as $aKey => $vObj) {
						$selectedKey = $iKey;
						$selectedCounter = $counterKey;
						//	$selectedKey = ($this->plotBy == 'subgroup')?$sKey:$aKey;
						$counterKey = array_search($selectedKey, $selectedFilterGrp);
						$chartData['series'][$counterKey]['data'][] = $vObj;
						$chartData['series'][$counterKey]['name'] = $selectedKey;
					}
					if ($linebreak) {
						$chartData['series'][$selectedCounter]['data'][] = null;
					}
				}
			}
			$counter++;
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
						$chartData['series'][$yCounter]['data'][$aCounter]['header'] =  sprintf('%s - %s', $yKey, $aKey);
					}
				}
			}
		}
		return $chartData;
	}

	private function getCaption() {
		$caption = sprintf('%s - %s', $this->selectedIndicator[0], $this->selectedUnits[0]);
		return $caption;
	}

	private function getYearSubcaption() {
		$yearRange = array_unique(array_map(function ($i) {
					return $i['TimePeriod']['TimePeriod'];
				}, $this->selectedTimeperiods));
		sort($yearRange);
		$yearSubcaption = '(' . implode(', ', $yearRange) . ')';

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

	private function sortIndicatorAsCatergory($totalColumn, $rotateLabel){
		$chartData = array();
		foreach ($this->selectedIndicator as $indObj) {
			$chartData['xAxis']['categories'][] = $indObj; //sprintf('%s - %s', $indObj['TimePeriod']['TimePeriod'], $indObj['DIArea']['Area_Name']);

			if ($totalColumn > 8 && $rotateLabel) {
				$chartData['xAxis']['labels']['rotation'] = $this->labelRotationValue;
			}

			$chartData['xAxis']['labels']['maxStaggerLines'] = 1;
		}
		$chartData['yAxis']['min'] = 0;
		//$chartData['xAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		//$chartData['yAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['title']['text'] = $this->selectedUnits[0];
		return $chartData;
	}
	
	private function sortTimeAsCatergory($totalColumn, $rotateLabel){
		$chartData = array();
		foreach ($this->selectedTimeperiods as $timeObj) {
			$chartData['xAxis']['categories'][] = $timeObj['TimePeriod']['TimePeriod']; //sprintf('%s - %s', $indObj['TimePeriod']['TimePeriod'], $indObj['DIArea']['Area_Name']);

			if ($totalColumn > 8 && $rotateLabel) {
				$chartData['xAxis']['labels']['rotation'] = $this->labelRotationValue;
			}

			$chartData['xAxis']['labels']['maxStaggerLines'] = 1;
		}
		$chartData['yAxis']['min'] = 0;
		//$chartData['xAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		//$chartData['yAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['title']['text'] = $this->selectedUnits[0];
		return $chartData;
	}
	
	private function sortTimeAreaAsCatergory($totalColumn, $rotateLabel, $linebreak = false){
		$chartData = array();
		foreach ($this->selectedTimeperiods as $timeObj) {
			foreach ($this->selectedAreas as $areaObj) {
				$chartData['xAxis']['categories'][] = sprintf('%s - %s', $timeObj['TimePeriod']['TimePeriod'], $areaObj['DIArea']['Area_Name']);

				if ($totalColumn > 8 && $rotateLabel) {
					$chartData['xAxis']['labels']['rotation'] = $this->labelRotationValue;
				}
				$chartData['xAxis']['labels']['maxStaggerLines'] = 1;
			}
			$chartData['yAxis']['min'] = 0;
			if ($linebreak) {
				$chartData['xAxis']['categories'][] = '';
			}
		}

		/*$chartData['xAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['title']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['xAxis']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['xAxis']['plotBands']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';
		$chartData['yAxis']['plotBands']['labels']['useHTML'] = 'js:Highcharts.hasBidiBug';*/
		$chartData['yAxis']['title']['text'] = $this->selectedUnits[0];
		return $chartData;
	}
	
}

?>