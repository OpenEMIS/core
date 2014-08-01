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

class DevInfoComponent extends Component {
	private $controller;
	
	public $limit = 1000;
	public $source = 'OpenEMIS_%s_%s';
	public $description = 'This database contains the Education Indicators from OpenEMIS';
	public $sector = 'Education';
	// OpenEMIS Models
	public $Area;
	public $ConfigItem;
	public $DatawarehouseIndicator;
	public $DatawarehouseIndicatorDimension;
	public $DatawarehouseIndicatorSubgroup;
	public $DatawarehouseModule;
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
		$this->SchoolYear = ClassRegistry::init('SchoolYear');
		$this->ConfigItem  = ClassRegistry::init('ConfigItem');
		$this->DatawarehouseIndicator = ClassRegistry::init('Datawarehouse.DatawarehouseIndicator');
		$this->DatawarehouseIndicatorDimension = ClassRegistry::init('Datawarehouse.DatawarehouseIndicatorDimension');
		$this->DatawarehouseIndicatorSubgroup = ClassRegistry::init('Datawarehouse.DatawarehouseIndicatorSubgroup');
		$this->DatawarehouseModule = ClassRegistry::init('Datawarehouse.DatawarehouseModule');
		$this->DatawarehouseDimension = ClassRegistry::init('Datawarehouse.DatawarehouseDimension');
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
		$this->DIAreaLevel->truncate();
		$this->DIArea->truncate();
	}

	public function export($settings=array()) {
        $indicatorId = $settings['indicatorId'];
        unset($settings['indicatorId']);

        $areaLevelId = $settings['areaLevelId'];
        $schoolYearId = $settings['schoolYearId'];
        unset($settings['areaId']);
     	unset($settings['schoolYearId']);

		$_settings = array(
			'onBeforeGenerate' => array('callback' => array(), 'params' => array()),
			'onAfterGenerate' => array('callback' => array(), 'params' => array()),
			'onError' => array('callback' => array(), 'params' => array())
		);
		$_settings = array_merge($_settings, $settings);
		
		$indicator = $this->DatawarehouseIndicator->find('first', array(
			'recursive' => 2,
			'conditions' => array('DatawarehouseIndicator.enabled' => 1, 'DatawarehouseIndicator.id' => $indicatorId)
		));

		$areaListId = array();
		$areaList = $this->Area->find('list', array('fields'=>array('id', 'id'), 'conditions'=>array('Area.area_level_id'=>$areaLevelId)));
		
		foreach($areaList as $areaID){
			$areaListId[] = $areaID;
			$childAreaList = $this->Area->children($areaID);
			if(!empty($childAreaList)){
				foreach($childAreaList as $key=>$val){
					$areaListId[] = $val['Area']['id'];
				}
			}
		}
		
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
			$subgroups = array();
			$subgroupTypes = array();
			if(!empty($indicator)) {
				if(!empty($onBeforeGenerate['callback'])) {
					if(!call_user_func_array($onBeforeGenerate['callback'], $onBeforeGenerate['params'])) {
						break;
					}
				}

				$indicatorNumeratorObj = $indicator['DatawarehouseIndicator'];
				$unitObj = $indicator['DatawarehouseUnit'];
				$indicatorNumeratorFieldObj = $indicator['DatawarehouseField'];
				$indicatorNumeratorModuleID = $indicatorNumeratorFieldObj['datawarehouse_module_id'];
				$indicatorNumeratorDimensionObj = $indicator['DatawarehouseIndicatorDimension'];
				$typeOption = array('Numerator');

				$subgroupTypes = array();
				$this->getSubgroupType($indicatorNumeratorModuleID, $subgroupTypes);


				$indicatorDenominatorFieldObj = array();
				$indicatorDenominatorModel = array();
				$hasDenominator = false;
				if(isset($indicator['Denominator']['id'])){
					$typeOption = array('Numerator', 'Denominator');
					$indicatorDenominatorObj = $indicator['DatawarehouseIndicator'];
					$indicatorDenominatorFieldObj = $indicator['Denominator']['DatawarehouseField'];
					$indicatorDenominatorModuleID = $indicatorDenominatorFieldObj['datawarehouse_module_id'];
					$indicatorDenominatorDimensionObj = $indicator['Denominator']['DatawarehouseIndicatorDimension'];


					$this->getSubgroupType($indicatorDenominatorModuleID, $subgroupTypes);
					$hasDenominator = true;
				}

		
				$indicatorName 		= $indicatorNumeratorObj['name'];
				$unitName 			= $unitObj['name'];
				$metadata 			= (!empty($indicatorNumeratorObj['description']) ? $indicatorNumeratorObj['description'] : $this->description);
				

				//if(!isset($subgroupTypes) || empty($subgroupTypes)) $subgroupTypes = $this->getSubgroupTypefromXML($indicatorFilename);
				$diIndicatorId 	= $this->Indicator->getPrimaryKey($indicatorName, $metadata);
				$diUnitId 		= $this->Unit->getPrimaryKey($unitName);
						

				$subqueryNumerator = null;
				$subqueryDenominator = null;	



				$schoolYear = $this->SchoolYear->find('first', array('recursive'=>-1, 'conditions'=>array('SchoolYear.id'=>$schoolYearId)));
				$diTimePeriodId 	= $this->TimePeriod->getPrimaryKey($schoolYear['SchoolYear']['name']);
				

				$subgroups = array();
				foreach($typeOption as $type){
					$joins = array();
					$moduleID = ${'indicator'.$type.'ModuleID'};
					$indicatorObj = ${'indicator'.$type.'Obj'};
					$indicatorID = $indicatorObj['id'];
					
					$datawarehouseModule = $this->DatawarehouseModule->find('first', array('recursive'=>-1, 'conditions'=>array('DatawarehouseModule.id'=>$moduleID)));

					$modelName = $datawarehouseModule['DatawarehouseModule']['model'];
					$modelTable = ClassRegistry::init($modelName);
					$joins = array();


					$datawarehouseDimension = $this->DatawarehouseDimension->find('all', array('recursive'=>-1, 'conditions'=>array('DatawarehouseDimension.datawarehouse_module_id'=>$moduleID)));
					

					if(isset($datawarehouseModule['DatawarehouseModule']['joins'])){
						$dimenJoin = $datawarehouseModule['DatawarehouseModule']['joins'];
						eval("\$tempJoin = $dimenJoin;");
						$joins[] = $tempJoin;
					}

					if(!empty($datawarehouseDimension)){
						foreach($datawarehouseDimension as $c){
							$dimensionJoin = $c['DatawarehouseDimension']['joins'];
							if(!empty($dimensionJoin)){
								eval("\$tempJoin = $dimensionJoin;");
								$joins[] = $tempJoin;
							}
						}
					}

					$fieldObj = ${'indicator'.$type.'FieldObj'};
					$aggregate = $fieldObj['type'];
					$fieldName = $fieldObj['name'];


					$group = array('area_id', 'school_year_id');


					$dbo = $modelTable->getDataSource();
					
					$offset = 0;
					${'subquery'.$type} = array();

					$fieldFormat = '%s(%s.%s) as %s, "%s" as Subgroup, %s as AreaID, %s as SchoolYearID';

					$conditions['area_id'] = $areaListId;
					$conditions['school_year_id'] = $schoolYearId;

					do {
						$datawarehouseSubgroups = $this->DatawarehouseIndicatorSubgroup->find('all', array('recursive'=>-1, 
							'conditions'=>array('DatawarehouseIndicatorSubgroup.datawarehouse_indicator_id'=>$indicatorID),
							'limit'=>$this->limit,
							'offset'=>$offset
							)
						);
						if(!empty($datawarehouseSubgroups)){
							foreach($datawarehouseSubgroups as $subgroup){
								$subgroupConditions = $conditions;
								if(!empty($subgroup['DatawarehouseIndicatorSubgroup']['value'])){
									$subgroupConditions[] = $subgroup['DatawarehouseIndicatorSubgroup']['value'];
								}
								$subgroups[] = $subgroup['DatawarehouseIndicatorSubgroup']['subgroup']; 

								$fields = array(sprintf($fieldFormat, $aggregate, $modelName, $fieldName, strtolower($type), $subgroup['DatawarehouseIndicatorSubgroup']['subgroup'], 'area_id', 'school_year_id'));

								${'subquery'.$type}[] =
									$dbo->buildStatement(
									array(
										'fields' => $fields,
										'joins'=> $joins,
										'table' => $dbo->fullTableName($modelTable),
										'alias' => $modelName,
										'group' =>  $group,
										'conditions' => $subgroupConditions,
										'limit' => null
									)
									,$modelTable
								);
							}
							
							$offset += $this->limit;
						}
						if(empty($datawarehouseSubgroups)){
							break;
						}
					} while(true);
					${'subquery'.$type} = implode(" UNION ", ${'subquery'.$type});
				} 




 				$outerQueryField = array('Numerator.numerator as DataValue', 'Numerator.Subgroup', 'Numerator.numerator as Numerator', 'NULL as Denominator', 'Numerator.AreaID', 'Numerator.SchoolYearID');
				switch($unitObj['id']){
					case 2:
					 	//RATE
				        
				        break;
				    case 3:
					 	//RATIO
				       
				        break;
				    case 4:
					 	//PERCENT
				       $outerQueryField = array('(IFNULL(Numerator.numerator,0)/IFNULL(Denominator.denominator,0))*100 as DataValue', 'IFNULL(Numerator.numerator, 0) as Numerator', 'IFNULL(Denominator.denominator, 0) as Denominator', 'Numerator.AreaID', 'Numerator.SchoolYearID');
				       break;
				}

				
				if(!empty($subqueryDenominator)){
					$outerQuery = $dbo->buildStatement(
						array(
							'fields' => $outerQueryField,
							'table' => '('.$subqueryNumerator.') as Numerator, ('. $subqueryDenominator.')',
							'alias' => 'Denominator'
						)
						,$modelTable
					);
				}else{
					$outerQuery = $dbo->buildStatement(
						array(
							'fields' => $outerQueryField,
							'table' => '('.$subqueryNumerator.')',
							'alias' => 'Numerator'
						)
						,$modelTable
					);
				}
			


				/*
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
				*/
				$modelData = $modelTable->query($outerQuery);

				pr($subgroups);
				pr($subgroupTypes);


				
				foreach($modelData as $data){
					$diSubgroupValId 	= $this->SubgroupVal->getPrimaryKey($data['Subgroup'], $subgroupTypes);
					$diIUSId 			= $this->IndicatorUnitSubgroup->getPrimaryKey($diIndicatorId, $diUnitId, $diSubgroupValId);

					$model = array();
					$model['IUSNId'] 			= $diIUSId;
					$model['TimePeriod_NId'] 	= $diTimePeriodId;
					$model['Area_NId'] 			= $data['AreaID'];
					$model['Data_Value'] 		= $data['DataValue'];
					$model['Source_NId'] 		= $sourceId;
					$model['Indicator_NId'] 	= $diIndicatorId;
					$model['Unit_NId'] 			= $diUnitId;
					$model['Subgroup_Val_NId'] 	= $diSubgroupValId;
					$this->Data->createRecord($model);
				}			
				pr($modelData);

				exit;


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

	private function getSubgroupType($moduleId, &$subgroupTypes){
		$subgroupTypeList = $this->DatawarehouseDimension->find('list', array(
			'fields'=> array('name', 'name'),
			'recursive' => -1,
			'conditions' => array('DatawarehouseDimension.datawarehouse_module_id' => $moduleId)
		));
		
		$subgroupTypes = array_merge($subgroupTypes, array_keys($subgroupTypeList));
		
		return $subgroupTypes;
	}

}
?>
