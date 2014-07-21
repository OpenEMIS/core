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

class FusionChartsComponent extends Component {
	private $controller;
	
	private $chartHeaderData = null;
	
	public $selectedDimensions;
	public $selectedIndicator;
	public $selectedAreas;
	public $selectedUnits;
	public $selectedTimeperiods;
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->init();
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) { }
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) { }
	
	//called after Controller::render()
	public function shutdown(Controller $controller) { }
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }
	
	public function init() {}
	
	public function initVariables($IUSData, $AreaData, $TimeperiodData){
		$this->selectedIndicator = array_unique(array_map(function ($i) { return $i['Indicator']['Indicator_Name']; }, $IUSData));
		$this->selectedDimensions = array_unique(array_map(function ($i) { return $i['SubgroupVal']['Subgroup_Val']; }, $IUSData));
		$this->selectedUnits = array_unique(array_map(function ($i) { return $i['Unit']['Unit_Name']; }, $IUSData));
		
		$this->selectedAreas = $AreaData;
		$this->selectedTimeperiods = $TimeperiodData;//array_unique(array_map(function ($i) { return $i['TimePeriod']['TimePeriod']; }, $TimeperiodData));
	}

	public function getDisplayType($name, $options){
		$_name = strtolower($name);
		$_width = (isset($options['width']))? $options['width']:400;
		$_height = (isset($options['height']))? $options['height']:300;
		
		switch($_name){
			case 'bar':
				$_swfChart = 'MSBar2D.swf';
			break;
			case 'stackbar':
				$_swfChart = 'StackedBar2D.swf';
			break;
			case 'column':
				$_swfChart = 'ScrollColumn2D.swf';
			break;
			case 'stackcolumn':
				$_swfChart = 'StackedColumn2D.swf';
			break;
			case 'line':
				$_swfChart = 'ZoomLine.swf';
			break;
			case 'area':
				$_swfChart = 'MSArea.swf';
			break;
			case 'pie':
				$_swfChart = 'Pie2D.swf';
			break;
			case 'scatter':
				$_swfChart = 'Scatter.swf';
			break;
			default:
				$_swfChart = 'ScrollColumn2D.swf';
			break;
		}
		
		return array('chartURLdata' => $options['url'],'swfUrl' => $_swfChart, 'width' => $_width, 'height' => $_height);
	}
	
	public function getBarChartData($DIData){
		$chartData = $this->setupChartInfo(array('caption' => $this->getCaption(), 'subcaption' => $this->getYearSubcaption()));
		$chartData = array_merge($chartData, $this->setupChartCategory());
		$chartData = array_merge($chartData, $this->setupChartDataset($DIData));
		
		return json_encode($chartData);
	}
	
	public function getLineChartData($DIData){
		$chartData = $this->setupChartInfo(array('caption' => $this->getCaption(), 'subcaption' => $this->getYearSubcaption()));
		$chartData = array_merge($chartData, $this->setupChartCategory(true));
		$chartData = array_merge($chartData, $this->setupChartDataset($DIData, true));
		
		$chartData =  str_replace('"":""', '', json_encode($chartData));
		
		return $chartData;
	}

	public function getPieChartData($DIData){
		$_option['caption'] = $this->getCaption();
		$_option['subcaption'] = $this->getYearSubcaption();
		$_option['showPercentInToolTip'] = 0;
		$chartData = $this->setupChartInfo($_option);
		$chartData = array_merge($chartData, $this->setupPieChartDataset($DIData));
		
		return json_encode($chartData);
	}
	
	public function getScatterChartData($DIData){
		$_option['caption'] = $this->getCaption();
		$_option['subcaption'] = $this->getYearSubcaption();
		$_option['xaxisname'] = reset($this->selectedDimensions);
		$_option['yaxisname'] = next($this->selectedDimensions);
	
		$chartData = $this->setupChartInfo($_option);
		$chartData = array_merge($chartData, $this->setupScatterChartDataset($DIData));
		
		return json_encode($chartData);
	}
	
	// ======================
	//	Setup Chart Data
	// ======================
	private function getCaption(){
		$caption = sprintf('%s - %s', $this->selectedIndicator[0] , $this->selectedUnits[0]);
		return $caption;
	}
	
	private function getYearSubcaption(){
		$yearRange = array_unique(array_map(function ($i) { return $i['TimePeriod']['TimePeriod']; }, $this->selectedTimeperiods));
		sort($yearRange);
		$yearSubcaption = '('.implode(', ', $yearRange).')';
		
		return $yearSubcaption;
	}
	
	/* ===================================================================================================
	 * This are the customisable options for setting up the chart general data
	 * 
	 * @$setupOtions['caption']
	 * @$setupOtions['subcaption']
	 * @$setupOtions['xaxisname']
	 * @$setupOtions['yaxisname']
	 * ================================================================================================== */
	private function setupChartInfo($setupOptions){ 
		$data['chart']['animation'] = 1;
		$data['chart']['basefont'] = "Arial";
		$data['chart']['basefontsize'] = 12;
		$data['chart']['useroundedges'] = 0;
		$data['chart']['legendborderalpha'] = 0;
		$data['chart']['showvalues'] = 0;
		
		$data = array_merge($data['chart'], $setupOptions);
		
		return $data;
	}
	
	private function setupChartCategory($linebreak = false){
		$finalData = array();
		
		foreach($this->selectedTimeperiods as $timeObj){
			foreach ($this->selectedAreas as $areaObj){
				$finalData['categories']['category'][]['label'] = sprintf('%s - %s', $timeObj['TimePeriod']['TimePeriod'], $areaObj['DIArea']['Area_Name']);
			}
			
			if($linebreak){
				$finalData['categories']['category'][]['label'] = '';
			}
		}
		return $finalData;
	}
	
	private function getTempDataStructure(){
		$finalData = array();
		
		foreach($this->selectedTimeperiods as $timeObj){
			$timeValue = $timeObj['TimePeriod']['TimePeriod'];
			foreach ($this->selectedAreas as $areaObj){
				$areaValue = $areaObj['DIArea']['Area_Name'];
				foreach($this->selectedDimensions as $dimensionObj){
					$finalData[$dimensionObj][$timeValue][$areaValue]= 0;
				}
			}
		}
		
		return $finalData;
	}
	
	private function setupChartDataset($data, $linebreak = false){
		$finalData = array();
		$dataStructure = $this->getTempDataStructure();
		
		foreach($data as $key => $row){
			$timeValue = $row['TimePeriod']['TimePeriod'];
			$areaValue = $row['DIArea']['Area_Name'];
			$dimensionValue = $row['SubgroupVal']['Subgroup_Val'];
			$dataValue = $row['DIData']['Data_Value'];
			$dataStructure[$dimensionValue][$timeValue][$areaValue] = $dataValue;
		}
		
		//Format Data to fusionchart structure
		$counter = 0;
		foreach($dataStructure as $key => $yData){
			$finalData['dataset'][$counter]['seriesname'] = $key;
			$finalData['dataset'][$counter]['alpha'] = 90;
			foreach($yData as $aData){
				foreach($aData as $vObj){
					$finalData['dataset'][$counter]['data'][] = array('value' => $vObj);
				}
				if($linebreak){
						$finalData['dataset'][$counter]['data'][] = array('' => '');
					}
			}
			$counter++;
		}
		
		return $finalData;
	}
	
	
	private function setupPieChartDataset($data){
		$finalData = array();
		$dataStructure = $this->getTempDataStructure();
		
		foreach($data as $key => $row){
			$timeValue = $row['TimePeriod']['TimePeriod'];
			$areaValue = $row['DIArea']['Area_Name'];
			$dimensionValue = $row['SubgroupVal']['Subgroup_Val'];
			$dataValue = $row['DIData']['Data_Value'];
			$dataStructure[$dimensionValue][$timeValue][$areaValue] = $dataValue;
		}
		//Format Data to fusionchart structure
		$counter = 0;
		foreach($dataStructure as $iKey => $iData){
			foreach($iData as $yKey => $yData){
				foreach($yData as $aKey => $aObj){
					$label = sprintf('%s - %s (%s)', $yKey, $aKey, $iKey);
					$finalData['data'][$counter][] = array('value' => $aObj, 'label' => $label);
				}
			}
			$counter++;
		}
		return $finalData;
	}
	
	
	
	private function setupScatterChartDataset($data){
		$finalData = array();
		$dataStructure = $this->getTempDataStructure();
		
		foreach($data as $key => $row){
			$timeValue = $row['TimePeriod']['TimePeriod'];
			$areaValue = $row['DIArea']['Area_Name'];
			$dimensionValue = $row['SubgroupVal']['Subgroup_Val'];
			$dataValue = $row['DIData']['Data_Value'];
			$dataStructure[$dimensionValue][$timeValue][$areaValue] = $dataValue;
		}
		//pr($dataStructure);
		$counter = 0;
		foreach($dataStructure as $iKey => $iData){
			
			$yCounter = 0;
			foreach($iData as $yKey => $yData){
				$finalData['dataset'][$yCounter]['seriesname'] = $yKey;
				$finalData['dataset'][$yCounter]['anchorradius'] = 5;
				$finalData['dataset'][$yCounter]['anchorBorderThickness'] = 2;
				$aCounter = 0;
				$axis = ($counter % 2 == 0)? 'x':'y';
				foreach($yData as $aKey => $aObj){
					$finalData['dataset'][$yCounter]['data'][$aCounter][$axis] = $aObj;
					if(empty($finalData['dataset'][$yCounter]['data'][$aCounter]['tooltext'])){
						$toolTips = $yKey.' '.$aKey.'('.$iKey.'):'.$aObj;
						$finalData['dataset'][$yCounter]['data'][$aCounter]['tooltext'] = $toolTips;
					}
					else{
						$toolTips = $finalData['dataset'][$yCounter]['data'][$aCounter]['tooltext'];
						$finalData['dataset'][$yCounter]['data'][$aCounter]['tooltext'] = $toolTips.' '.$aKey.'('.$iKey.'):'.$aObj;
					}
					$aCounter++;
				}
				$yCounter++;
			}
			$counter++;
		}
		
		return $finalData;
	}
}
?>