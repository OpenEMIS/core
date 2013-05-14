<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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

class DevInfo6Component extends Component {
	private $controller;
	
	public $limit = 1000;
	public $source = 'OpenEMIS_%s_%s';
	public $description = 'This database contains the Education Indicators from OpenEMIS';
	public $sector = 'Education';
	// OpenEMIS Models
	public $Area;
	public $ConfigItem;
	public $BatchIndicator;
	public $BatchIndicatorSubgroup;
	public $BatchIndicatorResult;
	// DevInfo Models
	public $Indicator;
	public $Unit;
	public $SubgroupVal;
	public $Subgroup;
	public $SubgroupType;
	public $SubgroupValsSubgroup;
	public $IndicatorUnitSubgroup;
	public $DIArea;
	public $DIAreaLevel;
	public $IndicatorClassification;
	public $IndicatorClassificationIUS;
	public $TimePeriod;
	public $Data;
	public $DBMetaData;
	
	public $components = array('Logger');
	
	//called before Controller::beforeFilter()
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->init();
	}
	
	public function init() {
		$this->Area = ClassRegistry::init('Area');
		$this->ConfigItem  = ClassRegistry::init('ConfigItem');
		$this->BatchIndicator = ClassRegistry::init('DataProcessing.BatchIndicator');
		$this->BatchIndicatorSubgroup = ClassRegistry::init('DataProcessing.BatchIndicatorSubgroup');
		$this->BatchIndicatorResult = ClassRegistry::init('DataProcessing.BatchIndicatorResult');
		$this->Indicator  = ClassRegistry::init('DevInfo6.Indicator');
		$this->Unit = ClassRegistry::init('DevInfo6.Unit');
		$this->SubgroupVal = ClassRegistry::init('DevInfo6.SubgroupVal');
		$this->Subgroup = ClassRegistry::init('DevInfo6.Subgroup');
		$this->SubgroupType = ClassRegistry::init('DevInfo6.SubgroupType');
		$this->SubgroupValsSubgroup = ClassRegistry::init('DevInfo6.SubgroupValsSubgroup');
		$this->IndicatorUnitSubgroup = ClassRegistry::init('DevInfo6.IndicatorUnitSubgroup');
		$this->DIArea = ClassRegistry::init('DevInfo6.DIArea');
		$this->DIAreaLevel = ClassRegistry::init('DevInfo6.DIAreaLevel');
		$this->IndicatorClassification = ClassRegistry::init('DevInfo6.IndicatorClassification');
		$this->IndicatorClassificationIUS = ClassRegistry::init('DevInfo6.IndicatorClassificationIUS');
		$this->TimePeriod = ClassRegistry::init('DevInfo6.TimePeriod');
		$this->Data = ClassRegistry::init('DevInfo6.Data');
		$this->DBMetaData = ClassRegistry::init('DevInfo6.DBMetaData');
		
		$this->Logger->init('devinfo6');
	}
	
	//called after Controller::beforeFilter()
	public function startup(Controller $controller) { }
	
	//called after Controller::beforeRender()
	public function beforeRender(Controller $controller) { }
	
	//called after Controller::render()
	public function shutdown(Controller $controller) { }
	
	//called before Controller::redirect()
	public function beforeRedirect(Controller $controller, $url, $status = null, $exit = true) { }
	
	public function truncateAllTables() {
		$this->DBMetaData->truncate();
		$this->Data->truncate();
		$this->IndicatorClassificationIUS->truncate();
		$this->IndicatorUnitSubgroup->truncate();
		$this->Indicator->truncate();
		$this->Unit->truncate();
		$this->SubgroupVal->truncate();
		$this->SubgroupValsSubgroup->truncate();
		$this->Subgroup->truncate();
		$this->SubgroupType->truncate();
		$this->TimePeriod->truncate();
		$this->IndicatorClassification->truncate();
	}
	
	public function export($settings=array()) {
		$_settings = array(
			'onBeforeGenerate' => array('callback' => array(), 'params' => array()),
			'onAfterGenerate' => array('callback' => array(), 'params' => array()),
			'onError' => array('callback' => array(), 'params' => array())
		);
		$_settings = array_merge($_settings, $settings);
		
		$indicatorList = $this->BatchIndicator->find('all', array(
			'fields' => array('BatchIndicator.id', 'BatchIndicator.name', 'BatchIndicator.unit', 'BatchIndicator.metadata'),
			'conditions' => array('BatchIndicator.enabled' => 1)
		));
		$areaList = $this->Area->find('list', array('conditions' => array('Area.visible' => 1)));
	
		$this->Logger->start();
		try {
			$this->Logger->write('Truncating all DevInfo tables.');
			$this->truncateAllTables();
			$this->Logger->write('Importing OpenEMIS Areas to DevInfo');
			$this->DIArea->import($this->DIAreaLevel->import());
			$adaptation = $this->ConfigItem->getAdaptation();
			$source = sprintf($this->source, $adaptation, date("dMY"));
			$sourceId = $this->IndicatorClassification->initSource($source);
			$sectorId = $this->IndicatorClassification->initSector($this->sector);
			$TYPE_SECTOR = 'SC';
				
			$this->Logger->write("Start Processing Indicators");
			
			foreach($indicatorList as $indicator) {
				if(!empty($onBeforeGenerate['callback'])) {
					if(!call_user_func_array($onBeforeGenerate['callback'], $onBeforeGenerate['params'])) {
						break;
					}
				}
				$indicatorObj	= $indicator['BatchIndicator'];
				$indicatorId 	= $indicatorObj['id'];
				$indicatorName 	= $indicatorObj['name'];
				$unitName 		= $indicatorObj['unit'];
				$metadata 		= $indicatorObj['metadata'];
				
				$subgroupTypes	= $this->BatchIndicatorSubgroup->getSubgroupTypes($indicatorId);
				$diIndicatorId 	= $this->Indicator->getPrimaryKey($indicatorName, $metadata);
				$diUnitId 		= $this->Unit->getPrimaryKey($unitName);
							
				foreach($areaList as $areaId => $areaName) {
					$conditions = array('BatchIndicatorResult.batch_indicator_id' => $indicatorId, 'BatchIndicatorResult.area_id' => $areaId);
					$dataCount = $this->BatchIndicatorResult->find('count', array('conditions' => $conditions));
					$pages = ceil($dataCount / $this->limit);
					
					$this->Logger->write(sprintf("Processing %s for Area [%s] [%s records]", $indicatorName, $areaName, $dataCount));
					
					for($i=0; $i<$pages; $i++) {
						$data = $this->BatchIndicatorResult->find('all', array(
							'conditions' => $conditions,
							'offset' => $this->limit * $i,
							'limit' => $this->limit
						));
						
						foreach($data as $obj) {
							$dataRow 			= $obj['BatchIndicatorResult'];
							$subgroups 			= $dataRow['subgroups'];
							$classification		= $dataRow['classification'];
							$diClassificationId = $this->IndicatorClassification->getPrimaryKey($classification, $TYPE_SECTOR, $sectorId);
							$diSubgroupValId 	= $this->SubgroupVal->getPrimaryKey($subgroups, $subgroupTypes);
							$diTimePeriodId 	= $this->TimePeriod->getPrimaryKey($dataRow['timeperiod']);
							$diIUSId 			= $this->IndicatorUnitSubgroup->getPrimaryKey($diIndicatorId, $diUnitId, $diSubgroupValId);
							$this->IndicatorClassificationIUS->getPrimaryKey($diClassificationId, $diIUSId);
							
							$model = array();
							$model['IUSNId'] 			= $diIUSId;
							$model['TimePeriod_NId'] 	= $diTimePeriodId;
							$model['Area_NId'] 			= $dataRow['area_id'];
							$model['Data_Value'] 		= $dataRow['data_value'];
							$model['Source_NId'] 		= $sourceId;
							$model['Indicator_NId'] 	= $diIndicatorId;
							$model['Unit_NId'] 			= $diUnitId;
							$model['Subgroup_Val_NId'] 	= $diSubgroupValId;
							
							$this->Data->createRecord($model);
						}
					}
				}
			}
			$this->Logger->write("End Processing Indicators");
			$this->Logger->write("Creating Metadata");
			$dbMetadata = array(
				'DBMetaData' => array(
					'DBMtd_Desc' => $this->description,
					'DBMtd_PubName' => '',
					'DBMtd_PubDate' => date('Y-m-d H:i:s'),
					'DBMtd_PubCountry' => '',
					'DBMtd_PubRegion' => '',
					'DBMtd_PubOffice' => '',
					'DBMtd_AreaCnt' => $this->DIArea->find('count'),
					'DBMtd_IndCnt' => $this->Indicator->find('count'),
					'DBMtd_IUSCnt' => $this->IndicatorUnitSubgroup->find('count'),
					'DBMtd_TimeCnt' => $this->TimePeriod->find('count'),
					'DBMtd_SrcCnt' => $this->IndicatorClassification->find('count', array(
						'conditions' => array('IC_Parent_NId <>' => '-1', 'IC_Type' => 'SR')
					)),
					'DBMtd_DataCnt' => $this->Data->find('count')
				)
			);
			
			$this->DBMetaData->create();
			$this->DBMetaData->save($dbMetadata);
		} catch(Exception $ex) {
			$error = $ex->getMessage();
			$this->Logger->write("Exception encountered while exporting indicator\n\n" . $error);
			$logFile = $this->Logger->end();
			
			if(!empty($onError['callback'])) {
				$params = array_merge($onError['params'], array($logFile));
				if(!call_user_func_array($onError['callback'], $params)) {
					// do something on false
				}
			}
		}
		if(!empty($onAfterGenerate['callback'])) {
			if(!call_user_func_array($onAfterGenerate['callback'], $onAfterGenerate['params'])) {
				// do something on false
			}
		}
		
		return $this->Logger->end();
	}
}
?>
