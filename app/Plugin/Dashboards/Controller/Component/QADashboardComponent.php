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

class QADashboardComponent extends Component {
	private $controller;
	
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
	
	public function init() {
		
	}
	
	
	// ======================
	//	Data Retriving 
	// ======================
	public function getAreaName($id){
		$jorAreaData = ClassRegistry::init('Dashboards.JORArea');
		$data = $jorAreaData->getAreaName($id);
		return $data;
	}
	
	public function getAreaParentData($id){
		$jorAreaData = ClassRegistry::init('Dashboards.JORArea');
		$data = $jorAreaData->getParentInfo($id);
		return $data;
	}
	
	public function getAreaChildLevel($id){
		$jorAreaData = ClassRegistry::init('Dashboards.JORArea');
		$data = $jorAreaData->getChildLevel('list', $id);
		return $data;
	}
	
	public function getYear($id){
		$jorTimePeriodData = ClassRegistry::init('Dashboards.JORTimePeriod');
		
		$options['fields'] = array('TimePeriod_NId', 'TimePeriod');
		$options['conditions'] = array('TimePeriod_NId' => $id);
		
		$data = $jorTimePeriodData->find('first', $options);
		
		return $data['JORTimePeriod']['TimePeriod'];
	}
	
	public function getYears($limit = NULL, $toYear = NULL){
		$jorTimePeriodData = ClassRegistry::init('Dashboards.JORTimePeriod');
		
		$options['fields'] = array('TimePeriod_NId', 'TimePeriod');
		$options['order'] = array('TimePeriod ASC');
		if(!empty($limit)){
			$options['limit'] = $limit;
		}
		if(!empty($toYear)){
			$options['conditions'] = array('TimePeriod <=' => $toYear, 'TimePeriod >' => $toYear - $limit);
		}
		$data = $jorTimePeriodData->find('list', $options);
		
		return $data;
	}
	
	public function getSummaryJorData($areaId,$yearId){
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$conditions = array('IUSNId' => array(8,15,18),'TimePeriod_NId' => $yearId);
		$data = $jorMainData->getData($areaId,$conditions);
	
		return $data;
	}
	
	public function getSummaryAdminBreakdownJorData($areaId,$yearId){
		$conditions = array('IUSNId' => array(1,2,3,4,5,6,7),'TimePeriod_NId' => $yearId);
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getData($areaId,$conditions);
		
		return $data;
	}
	
	public function getSummaryTechBreakdownJorData($areaId,$yearId){
		$conditions = array('IUSNId' => array(11,12,13,14),'TimePeriod_NId' => $yearId);
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getData($areaId,$conditions);
		
		return $data;
	}
	
	public function getSummaryTrendJorData($areaId, $years = array()){
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$conditions = array('IUSNId' => array(8,15, 18));
		//$includedTimePeriod = array('TimePeriod' =>array());
		if(!empty($years) && is_array($years)){
			foreach($years as $key => $name){
				$includedTimePeriod['TimePeriod_NId'][] = $key;
			}
			
			$conditions = array_merge($conditions, $includedTimePeriod);
		}
		
		$data = $jorMainData->getData($areaId, $conditions);
		//pr($data);die;
		return $data;
	}
	
	//Combine info
	public function getSummaryBothFDBreakdownJorData($areaId,$yearId){
		$conditions =  array('JORData.IUSNId' => 18);
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getFDData($areaId,$yearId, $conditions);
		
		return $data;
	}
	
	//Saperated Info
	public function getSummaryTechAdminFDBreakdownJorData($areaId,$yearId){
		$conditions =  array('JORData.IUSNId' => array(8,15));
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getFDData($areaId,$yearId, $conditions);
		
		return $data;
	}
	
	public function getSummaryAllFDBreakdownJorData($areaId,$yearId){
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$conditions = array('JORData.IUSNId' => array(8,15,18));
		$data = $jorMainData->getFDData($areaId,$yearId, $conditions);
	
		return $data;
	}
	
	public function getSummaryAllAspectTotalKG($areaId,$yearId){
		$conditions =  array('JORData.IUSNId' => array(20,21,22,23),'JORData.TimePeriod_NId' => $yearId);
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getTotalKGData($areaId, $conditions);
		
		return $data;
	}
	
	
	// ======================
	//	Setup Chart Data
	// ======================
	public function setupChartInfo($caption){
		$data['chart']['caption'] = $caption;//"Administrative and Technical Aspects";
		$data['chart']['rotatenames'] = 0;
		$data['chart']['animation'] = 1;
		$data['chart']['numdivlines'] = 4;
		$data['chart']['basefont'] = "Arial";
		$data['chart']['basefontsize'] = 12;
		$data['chart']['useroundedges'] = 0;
		$data['chart']['legendborderalpha'] = 0;
		$data['chart']['numbersuffix'] = '%';
		$data['chart']['yaxismaxvalue'] = '100';
		
		return $data;
	}
	
	public function setupChartCategory($data = array()){
		$finalData = array();
		foreach($data as $item){
			$finalData['categories']['category'][]['label'] = $item;
		}
		
		return $finalData;
	}
	
	public function setupChartDataset($name, $color, $catData, $data, $compareKey = 'IUSNId'){
		$finalData['seriesname'] = $name;
		$finalData['color'] = $color;
		$finalData['alpha'] = "90";
		$finalData['showvalues'] = "0";
		//pr($catData);
		foreach($catData as $key => $catName){
			foreach($data as $item){
			//	pr($item);
				$item = $item['JORData'];
				if($item[$compareKey] == $key){
					$finalData['data'][]['value'] = $item['Data_Value'];
					continue;
				}
			}
		}
	//pr($finalData);
		return $finalData;
	}
	
	// ======================
	//	Setup Scatter Chart Data
	// ======================
	public function setupScatterChartInfo($caption, $xaxisname, $yaxisname){
		$data['chart']['caption'] = $caption;//"Administrative and Technical Aspects";
		$data['chart']['xaxisname'] = $xaxisname;
		$data['chart']['yaxisname'] = $yaxisname;
		$data['chart']['xaxislabelmode'] = 'auto';
		$data['chart']['numbersuffix'] = '%';
		$data['chart']['yaxismaxvalue'] = '100';
		$data['chart']['xaxismaxvalue'] = '100';
		$data['chart']['canvasBorderThickness'] = 1;
		
		return $data;
	}
	
	public function setupScatterChartDataset($data,$catData){
		$finalData = array();
		$finalData['anchorcolor'] = '9ACCF6';
		$finalData['anchorradius'] = '4';
		$finalData['anchorsides'] = '4';
		$finalData['anchorbgcolor'] = '9ACCF6';
		$finalData['showplotborder'] = '0';
		$compareOptions = array('compareKey' => 'IUSNId', 'compX' => 8, 'compY' => 15);
		$dataSet = array();
		foreach($data as $item){
			$item = $item['JORData'];
			
			if($item[$compareOptions['compareKey']] == $compareOptions['compX']){
				$dataSet[$item['Area_NId']]['x'] = $item['Data_Value'];
			}
			else if($item[$compareOptions['compareKey']] == $compareOptions['compY']){
				$dataSet[$item['Area_NId']]['y'] = $item['Data_Value'];
			}
		}
		foreach($dataSet as $key => $item){
			$item['tooltext'] = sprintf($catData[$key]." TA:%s, AA:%s", $item['y'], $item['x']);
			$jsonData[] = $item;
		}
		$finalData['data'][0] = $jsonData;
		return $finalData;
	}
	
	// ======================
	//	Setup Scatter Chart Data
	// ======================
	public function setupLineChartInfo($caption){
		$data['chart']['caption'] = $caption;//"Administrative and Technical Aspects";
		$data['chart']['xaxislabelmode'] = 'auto';
		$data['chart']['numbersuffix'] = '%';
		$data['chart']['yaxismaxvalue'] = '100';
		$data['chart']['xaxismaxvalue'] = '100';
		$data['chart']['numdivlines'] = 4;
		$data['chart']['legendBorderAlpha'] = 0;
		$data['chart']['canvasBorderThickness'] = 1;
		
		return $data;
	}
	
	public function setupLineChartDataset($name, $achorOptions , $catData, $data, $filterArr = array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 0))){
		$finalData['seriesname'] = $name;
		$finalData['color'] = $achorOptions['color'];
		$finalData['alpha'] = "90";
		$finalData['showvalues'] = 0;
		$finalData['anchorBorderThickness'] = 1;//$color;
		$finalData['anchorSides'] = $achorOptions['anchorSides'];//$color;
		$finalData['anchorRadius'] = 4;//$color;
		$finalData['anchorbgcolor'] = $achorOptions['color'];
		foreach($catData as $key => $catName){
			foreach($data as $item){
			//	pr($item);
				$item = $item['JORData'];
				if($item[$filterArr['compareKey']] == $key && $item[$filterArr['filterDataBy']['key']] == $filterArr['filterDataBy']['value']){
					$finalData['data'][]['value'] = $item['Data_Value'];
					continue;
				}
			}
		}
	//pr($finalData);
		return $finalData;
	}
}
?>