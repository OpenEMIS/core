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
		
		$this->Logger->init('datawarehouse');
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
		//set_time_limit(0);
		//ini_set('max_execution_time', 300);

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
			$areaListId[$areaID] = $areaID;
			$childAreaList = $this->Area->children($areaID);
			if(!empty($childAreaList)){
				foreach($childAreaList as $key=>$val){
					$areaListId[$val['Area']['id']] = $val['Area']['id'];
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


			
				
			$this->Logger->write("Start Processing Indicators");
			$subgroups = array();
			$subgroupTypes = array();

			if(!empty($indicator)) {
				if(!empty($onBeforeGenerate['callback'])) {
					if(!call_user_func_array($onBeforeGenerate['callback'], $onBeforeGenerate['params'])) {
						break;
					}
				}
				$sector = $this->sector;
				if(!empty($indicator['DatawarehouseIndicator']['classification'])){
					$sector = $indicator['DatawarehouseIndicator']['classification'];
				}
				$sectorId = $this->IndicatorClassification->initSector($sector);
				$TYPE_SECTOR = 'SC';

				$indicatorNumeratorObj = $indicator['DatawarehouseIndicator'];
				$unitObj = $indicator['DatawarehouseUnit'];
				$indicatorNumeratorFieldObj = $indicator['DatawarehouseField'];
				$indicatorNumeratorModuleID = $indicatorNumeratorFieldObj['datawarehouse_module_id'];
				$indicatorNumeratorDimensionObj = $indicator['DatawarehouseIndicatorDimension'];

				$indicatorName = $indicatorNumeratorObj['name'];
				$typeOption = array('Numerator');

				$subgroupTypes = array();
				$this->getSubgroupType($indicatorNumeratorModuleID, $subgroupTypes);


				$indicatorDenominatorFieldObj = array();
				$indicatorDenominatorModel = array();
				$hasDenominator = false;

				if(isset($indicator['Denominator']['id'])){
					$typeOption = array('Numerator', 'Denominator');
					$indicatorDenominatorObj = $indicator['Denominator'];
					$indicatorDenominatorFieldObj = $indicator['Denominator']['DatawarehouseField'];
					$indicatorDenominatorModuleID = $indicatorDenominatorFieldObj['datawarehouse_module_id'];
					$indicatorDenominatorDimensionObj = $indicator['Denominator']['DatawarehouseIndicatorDimension'];


					$this->getSubgroupType($indicatorDenominatorModuleID, $subgroupTypes);
					$hasDenominator = true;
				}

				$unitName 			= $unitObj['name'];
				$metadata 			= (!empty($indicatorNumeratorObj['description']) ? $indicatorNumeratorObj['description'] : $this->description);

				//if(!isset($subgroupTypes) || empty($subgroupTypes)) $subgroupTypes = $this->getSubgroupTypefromXML($indicatorFilename);

				$diIndicatorId 	= $this->Indicator->getPrimaryKey($indicatorName, $metadata);
				$this->Logger->write("Populate ut_indicator_en - " . $indicatorName);

				$diUnitId 		= $this->Unit->getPrimaryKey($unitName);
				$this->Logger->write("Populate ut_unit_en - " . $unitName);
				$diClassificationId = $this->IndicatorClassification->getPrimaryKey($source, $TYPE_SECTOR, $sectorId);
				$this->Logger->write("Populate ut_indicator_classifications_en - " . $source);
					
				$subqueryNumerator = null;
				$subqueryDenominator = null;	

				$schoolYear = $this->SchoolYear->find('first', array('recursive'=>-1, 'conditions'=>array('SchoolYear.id'=>$schoolYearId)));
				$diTimePeriodId 	= $this->TimePeriod->getPrimaryKey($schoolYear['SchoolYear']['name']);
				
				$this->Logger->write("Populate ut_timeperiod - " . $schoolYear['SchoolYear']['name']);

				$offset = 0;
				do {
					$numeratorID = $indicatorNumeratorObj['id'];
					$numeratorFieldName = $indicatorNumeratorFieldObj['name'];

					$group = array('area_id', 'school_year_id');

					$numeratorModule = $this->DatawarehouseModule->find('first', array('recursive'=>-1, 'conditions'=>array('DatawarehouseModule.id'=>$indicatorNumeratorFieldObj['datawarehouse_module_id'])));
					$numeratorModelName = $numeratorModule['DatawarehouseModule']['model'];
					$numeratorModelTable = ClassRegistry::init($numeratorModelName);

					$dbo = $numeratorModelTable->getDataSource();
					
					$fieldFormat = '%s(%s.%s) as %s, "%s" as Subgroup, %s as AreaID, %s as SchoolYearID';

					$conditions['area_id'] = $areaListId;
					$conditions['school_year_id'] = $schoolYearId;

					$numeratorSubgroups = $this->DatawarehouseIndicatorSubgroup->find('all', array('recursive'=>-1, 
						'conditions'=>array('DatawarehouseIndicatorSubgroup.datawarehouse_indicator_id'=>$numeratorID),
						'limit'=>$this->limit,
						'offset'=>$offset,
						'page'=>ceil(1 + $offset/$this->limit)
						)
					);

					$denominatorModelTable = array();

					$numeratorJoins = array();
					$numeratorJoinUnique = array();

					if(!empty($numeratorSubgroups)){
						foreach($numeratorSubgroups as $numeratorSubgroup){
							$numeratorSubgroupConditions = $conditions;
							if(!empty($numeratorSubgroup['DatawarehouseIndicatorSubgroup']['value'])){
								$numeratorSubgroupConditions[] = $numeratorSubgroup['DatawarehouseIndicatorSubgroup']['value'];
							}
							$numeratorAggregate = $indicatorNumeratorFieldObj['type'];
							$type = 'numerator';
							$tempJoin = array();
							if(isset($numeratorModule['DatawarehouseModule']['joins'])){
								$dimenJoin = $numeratorModule['DatawarehouseModule']['joins'];
								eval("\$tempJoin = array($dimenJoin);");
								foreach($tempJoin as $k=>$j){
									$this->mergeJoin($numeratorJoins, $j);
								}
							}


							if(!empty($indicatorNumeratorDimensionObj)){
								foreach($indicatorNumeratorDimensionObj as $c){
									$dimensionJoin = $c['DatawarehouseDimension']['joins'];

									$tempJoin = array();
									if(!empty($dimensionJoin)){
										eval("\$tempJoin = array($dimensionJoin);");
										foreach($tempJoin as $k=>$j){
											$this->mergeJoin($numeratorJoins, $j);
										}
									}
								}
							}


							$numeratorFields = array(sprintf($fieldFormat, $numeratorAggregate, $numeratorModelName, $numeratorFieldName, strtolower($type), $numeratorSubgroup['DatawarehouseIndicatorSubgroup']['subgroup'], 'area_id', 'school_year_id'));

							$subqueryNumerator =
								$dbo->buildStatement(
								array(
									'fields' => $numeratorFields,
									'joins'=> $numeratorJoins,
									'table' => $dbo->fullTableName($numeratorModelTable),
									'alias' => $numeratorModelName,
									'group' =>  $group,
									'conditions' => $numeratorSubgroupConditions
								)
								,$numeratorModelTable
							);

							if($hasDenominator){
								$denominatorJoins = array();

								$denominatorID = $indicatorDenominatorObj['id'];
								$denominatorFieldName = $indicatorDenominatorFieldObj['name'];

								$datawarehouseDenominatorSubgroups = $this->DatawarehouseIndicatorSubgroup->find('first', array('recursive'=>-1, 
									'conditions'=>array('DatawarehouseIndicatorSubgroup.datawarehouse_indicator_id'=>$denominatorID)
									)
								);
								$denominatorModule = $this->DatawarehouseModule->find('first', array('recursive'=>-1, 'conditions'=>array('DatawarehouseModule.id'=>$indicatorDenominatorModuleID)));
								$denominatorModelName = $denominatorModule['DatawarehouseModule']['model'];
								$denominatorModelTable = ClassRegistry::init($denominatorModelName);

								$type = 'denominator';

								$denominatorAggregate = $indicatorDenominatorFieldObj['type'];

								$tempJoin = array();
								if(isset($denominatorModule['DatawarehouseModule']['joins'])){
									$dimenJoin = $denominatorModule['DatawarehouseModule']['joins'];
									eval("\$tempJoin = array($dimenJoin);");
									foreach($tempJoin as $k=>$j){
										$this->mergeJoin($denominatorJoins, $j);
									}
								}
								if(!empty($indicatorDenominatorDimensionObj) && isset($indicatorDenominatorDimensionObj[0]['datawarehouse_dimension_id'])){
									$denominatorDimensionObj = $this->DatawarehouseDimension->find('first', array('recursive'=>-1, 'conditions'=>array('DatawarehouseDimension.id'=>$indicatorDenominatorDimensionObj[0]['datawarehouse_dimension_id'])));
									if(!empty($denominatorDimensionObj)){
										foreach($denominatorDimensionObj as $c){
											$dimensionJoin = $c['joins'];
											$tempJoin = array();
											if(!empty($dimensionJoin)){
												eval("\$tempJoin = array($dimensionJoin);");
												foreach($tempJoin as $k=>$j){
													$this->mergeJoin($numeratorJoins, $j);
												}
											}
										}
									}
								}


								$denominatorSubgroupConditions = array();
								if(!empty($datawarehouseDenominatorSubgroups)){
									$denominatorSubgroupConditions = $conditions;
									if(!empty($datawarehouseDenominatorSubgroups['DatawarehouseIndicatorSubgroup']['value'])){
										$denominatorSubgroupConditions[] = $datawarehouseDenominatorSubgroups['DatawarehouseIndicatorSubgroup']['value'];
									} 
								}

								$denominatorFields = array(sprintf($fieldFormat, $denominatorAggregate, $denominatorModelName, $denominatorFieldName, strtolower($type), $datawarehouseDenominatorSubgroups['DatawarehouseIndicatorSubgroup']['subgroup'], 'area_id', 'school_year_id'));


								$dbo = $denominatorModelTable->getDataSource();

								$subqueryDenominator =
									$dbo->buildStatement(
									array(
										'fields' => $denominatorFields,
										'joins'=> $denominatorJoins,
										'table' => $dbo->fullTableName($denominatorModelTable),
										'alias' => $denominatorModelName,
										'group' =>  $group,
										'conditions' => $denominatorSubgroupConditions,
										'limit' => null
									)
									,$denominatorModelTable
								);
							}

							$outerQueryField = array('Numerator.numerator as DataValue', 'Numerator.Subgroup as Subgroup', 'Numerator.numerator as Numerator', 'NULL as Denominator', 'Numerator.AreaID', 'Numerator.SchoolYearID');
							switch($unitObj['id']){
								case 2:
								 	//RATE
							        $outerQueryField = array('ROUND(IFNULL(Numerator.numerator,0)/IFNULL(Denominator.denominator,0),2) as DataValue', 'Numerator.Subgroup as Subgroup', 'IFNULL(Numerator.numerator, 0) as Numerator', 'IFNULL(Denominator.denominator, 0) as Denominator', 'Numerator.AreaID', 'Numerator.SchoolYearID');
							        break;
							    case 3:
								 	//RATIO
							       	$outerQueryField = array('CONCAT(IFNULL(Numerator.numerator,0), ":", IFNULL(Denominator.denominator,0))  as DataValue', 'Numerator.Subgroup as Subgroup', 'IFNULL(Numerator.numerator, 0) as Numerator', 'IFNULL(Denominator.denominator, 0) as Denominator', 'Numerator.AreaID', 'Numerator.SchoolYearID');
							        break;
							    case 4:
								 	//PERCENT
							       $outerQueryField = array('ROUND((IFNULL(Numerator.numerator,0)/IFNULL(Denominator.denominator,0))*100,2) as DataValue', 'Numerator.Subgroup as Subgroup', 'IFNULL(Numerator.numerator, 0) as Numerator', 'IFNULL(Denominator.denominator, 0) as Denominator', 'Numerator.AreaID', 'Numerator.SchoolYearID');
							       break;
							}
							if($hasDenominator){
								$outerQuery = $dbo->buildStatement(
									array(
										'fields' => $outerQueryField,
										'table' => '('.$subqueryNumerator.') as Numerator, ('. $subqueryDenominator.')',
										'alias' => 'Denominator'
									)
									,$numeratorModelTable
								);
							}else{
								$outerQuery = $dbo->buildStatement(
									array(
										'fields' => $outerQueryField,
										'table' => '('.$subqueryNumerator.')',
										'alias' => 'Numerator'
									)
									,$numeratorModelTable
								);
							}

							$this->Logger->write($outerQuery);
							try{
								$modelData = $numeratorModelTable->query($outerQuery);
							} catch(Exception $ex) {
								$error = $ex->getMessage();
								$this->Logger->write("Exception encountered while executing query\n\n" . $error);
								$logFile = $this->Logger->end();
								
								if(!empty($onError['callback'])) {
									$params = array_merge($onError['params'], array($logFile));
									if(!call_user_func_array($onError['callback'], $params)) {
										// do something on false
									}
								}
							}

							if(!empty($modelData)){
								foreach($modelData as $data){
									$subgroups 			= $data['Numerator']['Subgroup'];
									$classification		= $data['Numerator']['Subgroup'];

									$diSubgroupValId 	= $this->SubgroupVal->getPrimaryKey($subgroups, $subgroupTypes);
									$diIUSId 			= $this->IndicatorUnitSubgroup->getPrimaryKey($diIndicatorId, $diUnitId, $diSubgroupValId);
									$this->IndicatorClassificationIUS->getPrimaryKey($diClassificationId, $diIUSId);
									
									$model = array();
									$model['IUSNId'] 			= $diIUSId;
									$model['TimePeriod_NId'] 	= $diTimePeriodId;
									$model['Area_NId'] 			= $data['Numerator']['AreaID'];
									$model['Data_Value'] 		= isset($data['Numerator']['DataValue']) ? $data['Numerator']['DataValue'] : $data[0]['DataValue'];
									$model['Source_NId'] 		= $sourceId;
									$model['Indicator_NId'] 	= $diIndicatorId;
									$model['Unit_NId'] 			= $diUnitId;
									$model['Subgroup_Val_NId'] 	= $diSubgroupValId;
									$this->Data->createRecord($model);
								}
							}
						}
					}else{
						break;
					}
					$offset += $this->limit;
				}while(true);
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
			$this->Logger->write("Completed");
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

	private function mergeJoin(&$joins, $newJoin){
		if(!empty($newJoin)){
			if(!empty($joins)){
				$addFlag = true;
				foreach($joins as $join){
					if($join['table'] == $newJoin['table']){
						$addFlag = false;
						break;
					}
				}
				if($addFlag){
					$joins[] = $newJoin;
				}
			}else{
				$joins[] = $newJoin;
			}
		}
		return $joins;
	}
}
?>
