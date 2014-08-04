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
	
	private $colorCodes = array(
		'blue' => '9ACCF6',
		'purple' => '9A22F6',
		'green' => '82CF27',
		'red' => '82CF27'
	);
	
	public $indicators = array(
			'SubgrpVal' => array(
				'Total' => 'aac52507-016f-4218-9975-e31c399ff717',
				'Urban' => '7a5835ba-62cc-41df-b7d8-4b1272a06544',
				'Rural' => '176a684b-d4cc-4d6e-9546-ffed2d8c2a69',
				'Owned' => 'add236eb-9c1a-43be-b7a2-9c4acd5e16c4',
				'Rented' => '91b550f7-c97d-4d9e-8795-97d4802913f4',
				'Permanent' => '5af34b3f-733e-40e1-bbcc-7dc5917ca2b9',
				'Contract' => '298dc93c-040f-4a88-b724-472651bb4daa',
			),
			'Unit' => array(
				'Percent' => '15114068-3fad-4750-80f6-acecff6cff5d',
				'Number' => '13778d1c-03c8-4fe8-aab6-94ff04afce56'
			),
			'QA_AdminTechBoth_Score' => array(
				'Admin' => '083d3156-1e2b-4db7-ab35-79c19662b4ee',
				'Tech' => 'c687cf37-f8d1-4ce4-8151-6460d30da63c',
				'Both' => '17ea0dea-4277-4bc6-b08f-1ef0777c6987',
			),
			'QA_AdminTech_Score' => array(
				'Admin' => '083d3156-1e2b-4db7-ab35-79c19662b4ee',
				'Tech' => 'c687cf37-f8d1-4ce4-8151-6460d30da63c',
			),
			'QA_AdminTechBoth_PassFail' => array(
				'Admin' => '2cd08c6f-5990-4bf5-9854-2921c8b889e6',
				'Tech' => 'c0d97a12-229f-42d5-b9bc-06f18dbee8c2',
				'Both' => 'b5295670-35f4-4dcc-b45e-a64674b7bf94',
			),
			'QA_AdminBreakdown_Score' => array(
				'Management' => '56905763-41ef-4e2b-a845-5f735f92a962',
				'Health' => 'cadbc6a5-136a-4824-94af-ce585e7caaf6',
				'Physical' => '1da4a23a-a058-410f-81b3-5cf85a25af12',
				'Teacher' => '66b9fc72-4d3a-459f-9ddc-f82fdb060f1b',
				'Evaluation' => 'f3ef08e1-b00a-4443-8e98-d97185efa8c7',
				'Parents' => '7bcc3641-acc3-4dbf-8bd1-390a716d9d3c',
				'Disability' => '852ae38f-037b-4bf3-b16b-1f654f6533a7',
			),
			'QA_TechBreakdown_Score' => array(
				'Planning' => '20a46b15-dda5-41c4-982a-b2ecca711d72',
				'Implementation' => '80ff44e6-15ce-44ef-a0c9-17483c8758b1',
				'Evaluation ' => 'c8f623a1-5e08-4210-8b1c-be7a4dc7f4b9',
				'Professionalism  ' => 'c48e4f93-bd6c-45a7-9c9b-ac9b1a5bfc99',
			),
		);
	
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
		//$lang = $_COOKIE['language'];
	}
	
	private function return_flat_array($data, $type = 'key'){
		$indicators = array();
		if(is_array($data)){
			foreach($data as $key => $value){
				if($type === 'key'){
					$indicators[] = $key;
				}
				else{
					$indicators[] = $value;
				}
			}
		}
		else{
			$indicators[] = $data;
		}
		
		return $indicators;
	}
	
	
	// ======================
	//	Data Retriving 
	// ======================
	/*public function getUnitIndicatorByGID($gid){
		$jorUnitData = ClassRegistry::init('Dashboards.Unit');
	
		if(is_array($gid)){
			$gid = $this->return_flat_array($gid, 'value');
		}
		
		$data = $jorUnitData->getUnitIndicatorByGId($gid);
		
		$finalData = array();
		foreach($data as $obj){
			$obj = $obj['Unit'];
			$finalData[$obj['Unit_NId']] = $obj['Unit_Name'];
		}
		return $finalData;
	}


	public function getIndicatorByGID($gid){
		$jorIndicatorData = ClassRegistry::init('Dashboards.Indicator');
	
		if(is_array($gid)){
			$gid = $this->return_flat_array($gid, 'value');
		}
		
		$data = $jorIndicatorData->getIndicatorByAreaGId($gid);
		
		$finalData = array();
		foreach($data as $obj){
			$obj = $obj['Indicator'];
			$finalData[$obj['Indicator_NId']] = $obj['Indicator_Name'];
		}
		return $finalData;
	}
	*/
	
	public function getIUSByIndividualGId($_options){
		$iusData = ClassRegistry::init('Dashboards.DashIndicatorUnitSubgroup');
		$data = $iusData->getIUSByIndividualGId($_options['indicatorGId'],$_options['unitGId'],$_options['subgroupValGId']);
		return $data;
	}
	
	public function getCountry(){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getCountry();
		return $data;
	}
	
	public function getAreasByLevel($lvl){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getAreasByLevel($lvl, 'list');
		return $data;
	}
	/*
	public function getAreaByGID($gid = NULL){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getAreaByAreaGId($gid);
		return $data;
	}
	*/
	public function getAreaById($id, $mode = 'all'){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getAreaById($id, $mode);
		return $data;
	}
	
	public function getAreaName($id){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getAreaName($id);
		return $data;
	}
	/*
	public function getAreaParentData($id){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getParentInfo($id);
		return $data;
	}
	*/
	public function getAreaChildLevel($id, $withCode = true){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getChildLevel('all', $id, $withCode);
		return $data;
	}
	
	public function getAllAreaChildByLevel($id, $lvl, $withCode = true){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getAllChildByLevel($id, $lvl,'all', $withCode);
		return $data;
	}
	/*
	public function getAreaAllChildLevel($id, $withCode = true){
		$jorAreaData = ClassRegistry::init('Dashboards.DashArea');
		$data = $jorAreaData->getAllChildLevel('list', $id, $withCode);
		return $data;
	}
	*/
	public function getAreaLevel($maxLvl = NULL){
		$jorAreaLevel = ClassRegistry::init('Dashboards.DashAreaLevel');
		
		$data = $jorAreaLevel->getAreaLevel($maxLvl);
		
		return $data;
	}
	
	public function getYear($id){
		$jorTimePeriodData = ClassRegistry::init('Dashboards.TimePeriod');
		
		$options['fields'] = array('TimePeriod_NId', 'TimePeriod');
		$options['conditions'] = array('TimePeriod_NId' => $id);
		
		$data = $jorTimePeriodData->find('first', $options);
		
		return $data['TimePeriod']['TimePeriod'];
	}
	
	public function getYears($mode = 'list', $_options = NULL){//$id = NULL, $limit = NULL, $toYear = NULL){
		$jorTimePeriodData = ClassRegistry::init('Dashboards.DashTimePeriod');
		
		$options['fields'] = array('TimePeriod_NId', 'TimePeriod');
		$options['order'] = array('TimePeriod ASC');
		if(!empty($_options['id'])){
			$options['conditions'] = array('TimePeriod_NId' => $_options['id']);
		}
		if(!empty($_options['limit'])){
			$options['limit'] = $_options['limit'];
		}
		/*if(!empty($_options['toYear'])){
			$options['conditions'] = array('TimePeriod <=' => $_options['toYear'] + (floor($limit/2)), 'TimePeriod >' => $_options['toYear']  - (floor($limit/2)));
		}*/
		$data = $jorTimePeriodData->find($mode, $options);
		
		return $data;
	}
	public function getYearRange($id, $limit){
		$jorTimePeriodData = ClassRegistry::init('Dashboards.DashTimePeriod');
		$options['limit'] = round($limit/2);
		$options['conditions'] = array('TimePeriod_NId <=' => $id);
		$options['order'] = array('TimePeriod DESC');
		
		$data = $jorTimePeriodData->find('all', $options);
		
		$options['conditions'] = array('TimePeriod_NId >' => $id);
		$options['order'] = array('TimePeriod ASC');
		
		$data2 = $jorTimePeriodData->find('all', $options);
		
		foreach($data2 as $obj){
			$data[] = $obj;
		}
		
		usort($data, array('QADashboardComponent', 'sortTime'));
		
		return $data;
	}
	
	function sortTime($a, $b) {
		return $a["TimePeriod"]["TimePeriod"]-$b["TimePeriod"]["TimePeriod"];
	}

	
	/*
	public function getSummaryJorData($options){
		$jorIndicatorUnitSubgroupData = ClassRegistry::init('Dashboards.IndicatorUnitSubgroup');
		
		$conditions = array('TimePeriod_NId' => $options['TimePeriod_Nid']);
		$indicators = $this->return_flat_array($options['indicators']);
		$unitIndicators = $this->return_flat_array($options['UnitIds']);
		$areas = $this->return_flat_array($options['areaIds']);
		$subgroupVal = $this->return_flat_array($options['Subgroup_Val_GId'], 'value');

		$IUSNid = $jorIndicatorUnitSubgroupData->getIUSNid($indicators, $unitIndicators ,$subgroupVal);
		
		$jorMainData = ClassRegistry::init('Dashboards.DIData');
		$conditions = array('DIData.IUSNId' => $IUSNid,'DIData.TimePeriod_NId' => $options['TimePeriod_Nid'], 'DIData.Area_NId' => $areas);
		$data = $jorMainData->getData($conditions);

		return $data;
	}
	
	public function getSummaryTrendJorData($options){
		$jorIndicatorUnitSubgroupData = ClassRegistry::init('Dashboards.IndicatorUnitSubgroup');
		$indicators = $this->return_flat_array($options['indicators']);
		$unitIndicators = $this->return_flat_array($options['UnitIds']);
		$IUSNid = $jorIndicatorUnitSubgroupData->getIUSNid($indicators, $unitIndicators ,$options['Subgroup_Val_GId']);
		$areas = $this->return_flat_array($options['areaIds']);
		
		$years = $this->return_flat_array($options['years']);
		
		$jorMainData = ClassRegistry::init('Dashboards.DIData');
		$conditions = array('IUSNId' => $IUSNid,'TimePeriod_NId' => $years, 'Area_NId' => $areas);
		
		$data = $jorMainData->getData($conditions);
		return $data;
	}
	
/*	public function getSummaryAllFDBreakdownJorData($areaId,$yearId){
		$jorMainData = ClassRegistry::init('Dashboards.DIData');
		$conditions = array('DIData.IUSNId' => array(8,15,18));
		$data = $jorMainData->getFDData($areaId,$yearId, $conditions);
	
		return $data;
	}
	
	public function getSummaryAllAspectTotalKG($areaId,$yearId){
		$conditions =  array('DIData.IUSNId' => array(20,21,22,23),'DIData.TimePeriod_NId' => $yearId);
		
		$jorMainData = ClassRegistry::init('Dashboards.DIData');
		$data = $jorMainData->getTotalKGData($areaId, $conditions);
		
		return $data;
	}*/
	
	
	/*// ======================
	//	Setup Chart Data
	// ======================
	public function setupChartInfo($caption){
		$data['chart']['caption'] = __($caption);//"Administrative and Technical Aspects";
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
	
	public function setupChartCategory($data = array(), $defaultVal = ''){
		$finalData = array();
		
		
		if(empty($data)){
			$finalData['categories']['category'][]['label'] = $defaultVal;
		}
		else{
			foreach($data as $item){
				$finalData['categories']['category'][]['label'] = $item;
			}
		}
		return $finalData;
	}
	
	public function setupChartDataset($chartDataSetup, $indData, $unitIndData, $data){
		$compareKey = empty($chartDataSetup['compareKey'])? 'Indicator_NId':$chartDataSetup['compareKey'];
		
		$finalData['seriesname'] = __($chartDataSetup['caption']);
		$finalData['color'] = $this->colorCodes[$chartDataSetup['color']];
		$finalData['alpha'] = "90";
		$finalData['showvalues'] = "0";
		
		$filtedData = array();
		foreach($unitIndData as $uKey => $unit){
			foreach($indData as $key => $indName){
				foreach($data as $item){
					$item = $item['DIData'];
					if($item[$compareKey] == $key && $item['Unit_NId'] == $uKey){
						$filtedData[$item[$compareKey]][$unit] = $item['Data_Value'];
						continue;
					}
				}
			}
		}
		
		
		
		if(empty($filtedData)){
			$finalData['data'][] = array('empty'=>'');
		}
		else{
			//Process Data to fusion chart format
			foreach($filtedData as $key =>$obj){
				$tempData['value'] = $obj['Percent'];
				$tempData['number'] = $obj['Number'];
				$tempData['tooltext'] = sprintf("%s%%, %s", $obj['Percent'], $obj['Number']);
				$finalData['data'][]=$tempData;
			}
		}
		
		return $finalData;
	}
	
	public function sumDataForChart($chartDataSetup, $data){
		$finalData['seriesname'] = __($chartDataSetup['caption']);
		$finalData['color'] = $this->colorCodes[$chartDataSetup['color']];
		$finalData['alpha'] = "90";
		$finalData['showvalues'] = "0";
		
		$sumData = array();
		
		foreach ($data as $item) {
			if (!empty($item['data'])) {
				foreach ($item['data'] as $key => $score) {
					if(!isset($score['empty'])){
						if (empty($sumData[$key])) {
							$sumData[$key]['tempSum'] = $score['value'] * $score['number'];
							$sumData[$key]['number'] = $score['number'];
						} else {
							$sumData[$key]['tempSum'] += $score['value'] * $score['number'];
							$sumData[$key]['number'] += $score['number'];
						}
					}
				}
			}
		}

		if(!empty($sumData)){
			foreach($sumData as $key => $finaloItem){
				$tempValue = round(ceil(($sumData[$key]['tempSum']/ $sumData[$key]['number'])*100)/100, 2);
				$sumData[$key]['value'] = $tempValue;
				$sumData[$key]['tooltext'] = sprintf("%s%%, %s", $tempValue, $finaloItem['number']);
			}

			$finalData['data']=$sumData;
		}
		return $finalData;
	}
	
	// ======================
	//	Setup Scatter Chart Data
	// ======================
	public function setupScatterChartInfo($caption, $indData){
		$data['chart']['caption'] = __($caption);//"Administrative and Technical Aspects";
		$i = 0;
		foreach($indData as $name){
			if($i == 0){
				$data['chart']['xaxisname'] = $name." (X)";
			}
			else{
				$data['chart']['yaxisname'] = $name." (Y)";
			}
			$i++;
		}
		$data['chart']['xaxislabelmode'] = 'auto';
	
		//$data['chart']['numdivlines'] = 6;
		$data['chart']['adjustVDiv'] = 0;
		$data['chart']['numbersuffix'] = '%';
		$data['chart']['yaxismaxvalue'] = '100';
		$data['chart']['xaxismaxvalue'] = '100';
		$data['chart']['canvasBorderThickness'] = 1;
		
		//$categoryData = $this->setupScatterChartCategory(4,10);
		
		//$data = array_merge($data, $categoryData);
		return $data;
	}
	
	private function setupScatterChartCategory($no, $multiplyer){
		$data = array();
		for($i = 0; $i < $no; $i++){
			$data['categories'][0]['category'][$i]['label'] = $multiplyer*($i+1);
			$data['categories'][0]['category'][$i]['x'] = $multiplyer*($i+1);
			$data['categories'][0]['category'][$i]['showverticalline'] = 1;
		}
		return $data;
	}
	
	public function setupScatterChartDataset($data,$indData, $unitIndData, $areaData){
		$finalData = array();
		$finalData['anchorcolor'] = '9ACCF6';
		$finalData['anchorradius'] = '4';
		$finalData['anchorsides'] = '4';
		$finalData['anchorbgcolor'] = '9ACCF6';
		$finalData['showplotborder'] = '0';
		//pr($areaData);
		$xaxisKey = key($indData);
		$dataSet = array();
		foreach($data as $item){
			$item = $item['DIData'];
			$plotXY = ($xaxisKey == $item['Indicator_NId'])? 'x': 'y';
			$dataSet[$item['Area_NId']][$plotXY] = $item['Data_Value'];
		}

		$scatterPlotData = array();
		foreach($dataSet as $key => $item){
			$item['x'] = isset($item['x'])?$item['x']:0;
			$item['y'] = isset($item['y'])?$item['y']:0;
			$item['tooltext'] = sprintf($areaData[$key]." X:%s%%, Y:%s%%", $item['x'], $item['y']);
			$scatterPlotData[] = $item;
		}
		if(empty($scatterPlotData)){
			$finalData['data'] = array('empty'=>'');
		}
		else{
			$finalData['data'][] = $scatterPlotData;
		}
		return $finalData;
	}
	
	// ======================
	//	Setup Scatter Chart Data
	// ======================
	public function setupLineChartInfo($caption){
		$data['chart']['caption'] = __($caption);
		$data['chart']['xaxislabelmode'] = 'auto';
		$data['chart']['numbersuffix'] = '%';
		$data['chart']['yaxismaxvalue'] = '100';
		$data['chart']['xaxismaxvalue'] = '100';
		$data['chart']['numdivlines'] = 4;
		$data['chart']['legendBorderAlpha'] = 0;
		$data['chart']['canvasBorderThickness'] = 1;
		
		return $data;
	}
	
	//public function setupLineChartDataset($name, $achorOptions , $catData, $data, $filterArr = array('compareKey' => 'TimePeriod_NId', 'filterDataBy' => array('key' => 'IUSNId', 'value' => 0))){
	public function setupLineChartDataset($data, $indData, $unitIndData, $yearOptions) {
		$colorArr = array('9ACCF6', '82CF27', 'CF5227');
		$anchorSides = array(3, 4, 20);
		$counter = 0;
		$returnData = array();
		foreach ($indData as $indKey => $indName) {
			$finalData = array();
			$finalData['seriesname'] = $indName;
			$finalData['color'] = $colorArr[$counter];
			$finalData['alpha'] = "90";
			$finalData['showvalues'] = 0;
			$finalData['anchorBorderThickness'] = 1; //$color;
			$finalData['anchorSides'] = $anchorSides[$counter]; //$color;
			$finalData['anchorRadius'] = 4; //$color;
			$filtedData = array();
			foreach ($yearOptions as $yKey => $year) {
				foreach ($unitIndData as $uKey => $unit) {
					foreach ($data as $i => $item) {
						$item = $item['DIData'];

						if ($item['TimePeriod_NId'] == $yKey && $item['Indicator_NId'] == $indKey && $item['Unit_NId'] == $uKey) {
							$filtedData[$item['Indicator_NId']][$year][$unit] = $item['Data_Value'];
						}
					}
				}
			}
			$tempData = array();
			foreach ($filtedData as $year) {
				foreach ($yearOptions as $defaultYear) {
					if (array_key_exists($defaultYear, $year)) {
						foreach ($year as $key => $obj) {
							$tempData['value'] = $obj['Percent'];
							$tempData['tooltext'] = sprintf("%s%%, %s", $obj['Percent'], $obj['Number']);
							$finalData['data'][] = $tempData;
						}
					} else {
						$tempData['value'] = 0;
						$tempData['tooltext'] = sprintf("%s%%, %s", 0, 0);
						$finalData['data'][] = $tempData;
					}
				}
			}
			$returnData[] = $finalData;
			$counter++;
		}

		return $returnData;
	}
*/
	
	public function getDashboardRawData($options){
		$dashData = ClassRegistry::init('Dashboards.DashData');
		$selectedOptions = $dashData->getQueryOptionsSetup($options);
		$data = $dashData->find('all', $selectedOptions);

		return $data;
	}
	
	public function getLatestSourceID($ius, $timeperiod){
		$dashData = ClassRegistry::init('Dashboards.DashData');
		$sourceId = $dashData->getLatestSourceID($ius,$timeperiod);
	
		return $sourceId;
	}
}
?>