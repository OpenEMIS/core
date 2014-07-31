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
	public $selectedDimensions;
	public $selectedIndicator;
	public $selectedAreas;
	public $selectedUnits;
	public $selectedTimeperiods;
	public $selectedIUS;

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
		$this->selectedIndicator = array_unique(array_map(function ($i) {
					return $i['Indicator']['Indicator_Name'];
				}, $IUSData));
		$this->selectedDimensions = array_unique(array_map(function ($i) {
					return $i['SubgroupVal']['Subgroup_Val'];
				}, $IUSData));
		$this->selectedUnits = array_unique(array_map(function ($i) {
					return $i['Unit']['Unit_Name'];
				}, $IUSData));
		$this->selectedIUS = array_unique(array_map(function ($i) {
					return $i['IndicatorUnitSubgroup']['IUSNId'];
				}, $IUSData));

		$this->selectedAreas = $AreaData;
		$this->selectedTimeperiods = $TimeperiodData;
	}
	
	public function getAreaIds(){
		$data = array_unique(array_map(function ($i) {
					return $i['DIArea']['Area_Nid'];
				}, $this->selectedAreas));
		
		return $data;
	}
	
	public function getTimeperiodIds(){
		$data = array_unique(array_map(function ($i) {
					return $i['TimePeriod']['TimePeriod_NId'];
				}, $this->selectedTimeperiods));
		
		return $data;
	}

	public function getChartData($type, $DIData) {
		$chartTypeInfo = explode("-", $type);
		$chartType = $chartTypeInfo[0];
		$chartData = $this->customGenerateHeader(array('chartType' => $chartType, 'caption' => $this->getCaption(), 'subcaption' => $this->getYearSubcaption()));
		
		$plotOptions['stacking'] = !empty($chartTypeInfo[1]) ? $chartTypeInfo[1] : null;
		$chartData = array_merge($chartData, $this->getPlotOptions($chartType, $plotOptions));

		//export
		$chartData = array_merge($chartData, $this->initExportSetup());
		
		switch ($chartType) {
			case 'pie':
				$chartData = array_merge($chartData, $this->getPieChartData($DIData));
				break;
			case 'scatter':
				$chartData = array_merge($chartData, $this->getScatterChartData($DIData));
				break;
			default :
				$chartData = array_merge($chartData, $this->getGenericChartData($chartType, $DIData));
		}

		return json_encode($chartData, JSON_NUMERIC_CHECK);
	}

	/* ================================================
	 * Customized Functions for DIY purpose
	 * ================================================ */
	
	public function customGenerateHeader($_options){
		$chartData['credits']['enabled'] = false;
		$chartData['chart']['type'] = !empty($_options['chartType'])?$_options['chartType']: 'column';
		$chartData['chart']['zoomType'] = !empty($_options['zoomType'])?$_options['zoomType']: 'xy';
		if(!empty($_options['caption'])){
			$chartData['title']['text'] = __($_options['caption']);
		}
		if(!empty($_options['subcaption'])){
			$chartData['subtitle']['text'] = __($_options['subcaption']);
		}
		return $chartData;
	}
	
	public function customGenerateCategory($chartType){
		$linebreak = $this->getChartBreak($chartType);
		$totalColumn = count($this->selectedTimeperiods) * count($this->selectedAreas);
		$rotateLabel = $this->getLabelRotate($chartType);
		
		foreach($this->selectedIndicator as $indObj){
			$finalData['xAxis']['categories'][] = $indObj;//sprintf('%s - %s', $indObj['TimePeriod']['TimePeriod'], $indObj['DIArea']['Area_Name']);
		}
	}
	
	
	public function getPlotOptions($type, $_options) {
		$chartData = array();
		if (!empty($_options['stacking']) && ($type == 'bar' || $type == 'column')) {
			$chartData['plotOptions'][$type]['stacking'] = 'normal';
		} else if ($type == 'scatter') {
			$chartData['plotOptions'][$type]['tooltip']['pointFormat'] = '{point.titlex}: <b>{point.x}</b> <br/> {point.titley}: <b>{point.y}</b>';
		}

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
		$chartData = $this->setupScatterChartCategory();
		$chartData = array_merge($chartData, $this->setupScatterChartDataset($DIData));
		return $chartData;
	}

	

	private function setupChartCategory($chartType) {
		$finalData = array();

		$linebreak = $this->getChartBreak($chartType);
		$totalColumn = count($this->selectedTimeperiods) * count($this->selectedAreas);
		$rotateLabel = $this->getLabelRotate($chartType);

		foreach ($this->selectedTimeperiods as $timeObj) {
			foreach ($this->selectedAreas as $areaObj) {
				$finalData['xAxis']['categories'][] = sprintf('%s - %s', $timeObj['TimePeriod']['TimePeriod'], $areaObj['DIArea']['Area_Name']);
				if ($totalColumn > 8 && $rotateLabel) {
					$finalData['xAxis']['labels']['rotation'] = 270;
				}
			}
			$finalData['yAxis']['min'] = 0;
			if ($linebreak) {
				$finalData['xAxis']['categories'][] = '';
			}
		}
		return $finalData;
	}

	private function setupScatterChartCategory() {
		$finalData = array();
		$finalData['xAxis']['title']['text'] = $this->selectedDimensions[0];
		$finalData['yAxis']['title']['text'] = $this->selectedDimensions[1];
		return $finalData;
	}

	private function setupChartDataset($data, $linebreak = false) {
		$finalData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		//Format Data to fusionchart structure
		$counter = 0;
		foreach ($dataStructure as $key => $yData) {
			foreach ($yData as $aData) {
				foreach ($aData as $vObj) {
					$finalData['series'][$counter]['data'][] = $vObj;
					$finalData['series'][$counter]['name'] = $key;
				}
				if ($linebreak) {
					$finalData['series'][$counter]['data'][] = null;
				}
			}
			$counter++;
		}

		return $finalData;
	}

	private function setupPieChartDataset($data, $linebreak = false) {
		$finalData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		//Format Data to fusionchart structure
		$counter = 0;
		foreach ($dataStructure as $key => $yData) {
			foreach ($yData as $ykey => $aData) {
				foreach ($aData as $akey => $vObj) {
					$finalData['series'][$counter]['data'][] = array('name' => sprintf('%s - %s (%s) : %s', $ykey, $akey, $key, $vObj), 'y' => $vObj);
					$finalData['series'][$counter]['name'] = $this->selectedUnits[0];
				}
			}
		}
		return $finalData;
	}

	private function setupScatterChartDataset($data, $linebreak = false) {
		$finalData = array();
		$dataStructure = $this->reformatDataWithNewStructure($data);

		//Format Data to fusionchart structure
		$counter = 0;
		foreach ($dataStructure as $iKey => $iData) {

			$yCounter = 0;
			foreach ($iData as $yKey => $yData) {
				$finalData['series'][$yCounter]['name'] = $yKey;
				$aCounter = 0;
				$axis = ($counter % 2 == 0) ? 'x' : 'y';
				foreach ($yData as $aKey => $aObj) {
					$finalData['series'][$yCounter]['data'][$aCounter][$axis] = $aObj;
					$finalData['series'][$yCounter]['data'][$aCounter]['title' . $axis] = $iKey;
					$finalData['series'][$yCounter]['tooltip']['headerFormat'] = '<span style="fill:{series.color}">●</span> ' . sprintf('%s - %s', $yKey, $aKey) . '<br/>';
					$aCounter++;
				}
				$yCounter++;
			}
			$counter++;
		}

		return $finalData;
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
		$finalData = array();

		foreach ($this->selectedTimeperiods as $timeObj) {
			$timeValue = $timeObj['TimePeriod']['TimePeriod'];
			foreach ($this->selectedAreas as $areaObj) {
				$areaValue = $areaObj['DIArea']['Area_Name'];
				foreach ($this->selectedDimensions as $dimensionObj) {
					$finalData[$dimensionObj][$timeValue][$areaValue] = 0;
				}
			}
		}

		return $finalData;
	}
	
	private function reformatDataWithNewStructure($data){
		$dataStructure = $this->getTempDataStructure();
		
		foreach ($data as $key => $row) {
			$timeValue = $row['TimePeriod']['TimePeriod'];
			$areaValue = $row['DIArea']['Area_Name'];
			$dimensionValue = $row['SubgroupVal']['Subgroup_Val'];
			$dataValue = $row['DIData']['Data_Value'];
			$dataStructure[$dimensionValue][$timeValue][$areaValue] = $dataValue;
		}
		
		return $dataStructure;
	}

	private function initExportSetup(){
		$chartData['exporting']['filename'] = 'Visualizer_Chart_'. date("Y-m-d");
		$chartData['exporting']['sourceWidth'] = 800;
		$chartData['exporting']['sourceHeight'] = 600;
		
		return $chartData;
	}
}

?>