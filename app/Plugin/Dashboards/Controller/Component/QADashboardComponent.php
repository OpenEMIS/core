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
	public $indicators;
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
		$this->indicators = array(
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
	public function getUnitIndicatorByGID($gid){
		$jorUnitData = ClassRegistry::init('Dashboards.JORUnit');
	
		if(is_array($gid)){
			$gid = $this->return_flat_array($gid, 'value');
		}
		
		$data = $jorUnitData->getUnitIndicatorByGId($gid);
		
		$finalData = array();
		foreach($data as $obj){
			$obj = $obj['JORUnit'];
			$finalData[$obj['Unit_NId']] = $obj['Unit_Name'];
		}
		return $finalData;
	}


	public function getIndicatorByGID($gid){
		$jorIndicatorData = ClassRegistry::init('Dashboards.JORIndicator');
	
		if(is_array($gid)){
			$gid = $this->return_flat_array($gid, 'value');
		}
		
		$data = $jorIndicatorData->getIndicatorByAreaGId($gid);
		
		$finalData = array();
		foreach($data as $obj){
			$obj = $obj['JORIndicator'];
			$finalData[$obj['Indicator_NId']] = $obj['Indicator_Name'];
		}
		return $finalData;
	}
	
	public function getAreaByGID($gid = NULL){
		$jorAreaData = ClassRegistry::init('Dashboards.JORArea');
		$data = $jorAreaData->getAreaByAreaGId($gid);
		return $data;
	}
	
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
			$options['conditions'] = array('TimePeriod <=' => $toYear + (floor($limit/2)), 'TimePeriod >' => $toYear  - (floor($limit/2)));
		}
		$data = $jorTimePeriodData->find('list', $options);
		
		return $data;
	}
	
	public function getSummaryJorData($options){
		$jorIndicatorUnitSubgroupData = ClassRegistry::init('Dashboards.JORIndicatorUnitSubgroup');
	//	pr($options['areaIds']);die;
		$conditions = array('TimePeriod_NId' => $options['TimePeriod_Nid']);
		$indicators = $this->return_flat_array($options['indicators']);
		$unitIndicators = $this->return_flat_array($options['UnitIds']);
		$areas = $this->return_flat_array($options['areaIds']);
		$subgroupVal = $this->return_flat_array($options['Subgroup_Val_GId'], 'value');

		$IUSNid = $jorIndicatorUnitSubgroupData->getIUSNid($indicators, $unitIndicators ,$subgroupVal);
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$conditions = array('IUSNId' => $IUSNid,'TimePeriod_NId' => $options['TimePeriod_Nid'], 'Area_NId' => $areas);
		$data = $jorMainData->getData($conditions);

		return $data;
	}
	
	/*public function getSummaryAdminBreakdownJorData($areaId,$yearId){
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
	}*/
	
	public function getSummaryTrendJorData($options){
		$jorIndicatorUnitSubgroupData = ClassRegistry::init('Dashboards.JORIndicatorUnitSubgroup');
		$indicators = $this->return_flat_array($options['indicators']);
		$unitIndicators = $this->return_flat_array($options['UnitIds']);
		$IUSNid = $jorIndicatorUnitSubgroupData->getIUSNid($indicators, $unitIndicators ,$options['Subgroup_Val_GId']);
		$areas = $this->return_flat_array($options['areaIds']);
		
		$years = $this->return_flat_array($options['years']);
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$conditions = array('IUSNId' => $IUSNid,'TimePeriod_NId' => $years, 'Area_NId' => $areas);
		
		$data = $jorMainData->getData($conditions);
		return $data;
	}
	
	//Combine info
	/*public function getSummaryBothFDBreakdownJorData($areaId,$yearId){
		$conditions =  array('JORData.IUSNId' => 18);
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getFDData($areaId,$yearId, $conditions);
		
		return $data;
	}*/
	
	//Saperated Info
	/*public function getSummaryTechAdminFDBreakdownJorData($areaId,$yearId){
		$conditions =  array('JORData.IUSNId' => array(8,15));
		
		$jorMainData = ClassRegistry::init('Dashboards.JORData');
		$data = $jorMainData->getFDData($areaId,$yearId, $conditions);
		
		return $data;
	}*/
	
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
	
	public function setupChartCategory($data = array()){
		$finalData = array();
		foreach($data as $item){
			$finalData['categories']['category'][]['label'] = $item;
		}
		
		return $finalData;
	}
	
	public function setupChartDataset($name, $color, $indData, $unitIndData, $data, $compareKey = 'Indicator_NId'){
		$finalData['seriesname'] = __($name);
		$finalData['color'] = $color;
		$finalData['alpha'] = "90";
		$finalData['showvalues'] = "0";
	//	pr($indData);die;
		$filtedData = array();
		foreach($unitIndData as $uKey => $unit){
			foreach($indData as $key => $indName){
				foreach($data as $item){
					$item = $item['JORData'];
					if($item[$compareKey] == $key && $item['Unit_NId'] == $uKey){
						$filtedData[$item[$compareKey]][$unit] = $item['Data_Value'];
						//$finalData['data'][]['value'] = $item['Data_Value'];
						continue;
					}
				}
			}
		}
		//Process Data to fusion chart format
		foreach($filtedData as $key =>$obj){
			$tempData['value'] = $obj['Percent'];
			$tempData['tooltext'] = sprintf("%s%%, %s", $obj['Percent'], $obj['Number']);
			$finalData['data'][]=$tempData;
		}
		return $finalData;
	}
	
	// ======================
	//	Setup Scatter Chart Data
	// ======================
	public function setupScatterChartInfo($caption, $xaxisname, $yaxisname){
		$data['chart']['caption'] = __($caption);//"Administrative and Technical Aspects";
		$data['chart']['xaxisname'] = $xaxisname;
		$data['chart']['yaxisname'] = $yaxisname;
		$data['chart']['xaxislabelmode'] = 'auto';
		$data['chart']['numbersuffix'] = '%';
		$data['chart']['yaxismaxvalue'] = '100';
		$data['chart']['xaxismaxvalue'] = '100';
		$data['chart']['canvasBorderThickness'] = 1;
		
		return $data;
	}
	
	public function setupScatterChartDataset($data,$indData, $unitIndData, $areaData){
		//pr($data);
		//pr($indData);
		
		$finalData = array();
		$finalData['anchorcolor'] = '9ACCF6';
		$finalData['anchorradius'] = '4';
		$finalData['anchorsides'] = '4';
		$finalData['anchorbgcolor'] = '9ACCF6';
		$finalData['showplotborder'] = '0';
		
		$dataSet = array();
		foreach($areaData as $aKey => $areaObj){
			foreach($indData as $iKey => $indObj){
				foreach($unitIndData as $uKey => $unit){
					foreach($data as $item){
						$item = $item['JORData'];

						if($item['Area_NId'] == $aKey && $item['Indicator_NId'] == $iKey && $item['Unit_NId'] == $uKey){
							$dataSet[$aKey][$iKey]['name']= $indObj;
							$dataSet[$aKey][$iKey][$unit]= $item['Data_Value'];
							continue;
							
						}
					}
				}
			}
		}
		//pr($dataSet);
		
		$scatterPlotData = array();
		foreach($dataSet as $key => $item){
			$loopCounter = 0;
			$finalPlotObj = array();
			foreach($item as $plotObj){
				
				if($loopCounter == 0){
					$finalPlotObj['x'] = $plotObj['Percent'];
					//$finalPlotObj['x_num'] = $plotObj['Number'];
				}
				else{
					$finalPlotObj['y'] = $plotObj['Percent'];
					//$finalPlotObj['y_num'] = $plotObj['Number'];
				}
				$loopCounter++;
				//$item['tooltext'] = sprintf($areaData[$pKey]." TA:%s, AA:%s", $item['Percent'], $item['x']);
			}
			$finalPlotObj['tooltext'] = sprintf($areaData[$key]." TA:%s%%, AA:%s%%", $finalPlotObj['y'], $finalPlotObj['x']);
			$scatterPlotData[] = $finalPlotObj;
			
		}
		$finalData['data'][] = $scatterPlotData;
		
		return $finalData;
	}
	
	// ======================
	//	Setup Scatter Chart Data
	// ======================
	public function setupLineChartInfo($caption){
		$data['chart']['caption'] = __($caption);//"Administrative and Technical Aspects";
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
	public function setupLineChartDataset($data, $indData, $unitIndData, $yearOptions){	
		$colorArr = array('9ACCF6', '82CF27', 'CF5227');
		$anchorSides = array(3,4,20);
		$counter = 0;
		$returnData = array();
		foreach($indData as $indKey =>  $indName){
			$finalData = array();
			$finalData['seriesname'] = $indName;
			$finalData['color'] = $colorArr[$counter];
			$finalData['alpha'] = "90";
			$finalData['showvalues'] = 0;
			$finalData['anchorBorderThickness'] = 1;//$color;
			$finalData['anchorSides'] = $anchorSides[$counter];//$color;
			$finalData['anchorRadius'] = 4;//$color;
			$filtedData = array();
			foreach ($yearOptions as $yKey => $year){
				foreach($unitIndData as $uKey => $unit){
					foreach($data as $i => $item){
						$item = $item['JORData'];

						if($item['TimePeriod_NId'] == $yKey && $item['Indicator_NId'] == $indKey && $item['Unit_NId'] == $uKey){
							$filtedData[$item['Indicator_NId']][$year][$unit] = $item['Data_Value'];
						}
					}
				}
			}
			$tempData = array();
			foreach($filtedData as $year){
				foreach($year as $key =>$obj){
					$tempData['value'] = $obj['Percent'];
					$tempData['tooltext'] = sprintf("%s%%, %s", $obj['Percent'], $obj['Number']);
					$finalData['data'][]=$tempData;
				}
			}
			
			$returnData[] = $finalData;
			$counter++;
		}
		
		
		return $returnData;
	}
}
?>