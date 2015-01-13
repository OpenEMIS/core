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

App::uses('AppModel', 'Model');

class InfrastructureCategory extends AppModel {
	public $actsAs = array(
		'FieldOption',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	
	private $censusInfraMapping = array(
        'Rooms' => array(
            'censusModel' => 'CensusRoom',
            'typesModel' => 'InfrastructureRoom',
            'typeForeignKey' => 'infrastructure_room_id'
        ),
        'Water' => array(
            'censusModel' => 'CensusWater',
            'typesModel' => 'InfrastructureWater',
            'typeForeignKey' => 'infrastructure_water_id'
        ),
        'Resources' => array(
            'censusModel' => 'CensusResource',
            'typesModel' => 'InfrastructureResource',
            'typeForeignKey' => 'infrastructure_resource_id'
        ),
        'Energy' => array(
            'censusModel' => 'CensusEnergy',
            'typesModel' => 'InfrastructureEnergy',
            'typeForeignKey' => 'infrastructure_energy_id'
        ),
        'Furniture' => array(
            'censusModel' => 'CensusFurniture',
            'typesModel' => 'InfrastructureFurniture',
            'typeForeignKey' => 'infrastructure_furniture_id'
        )
    );
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return array();
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$data = array();
			$headerCommon = array(__('Year'), __('Infrastructure Name'), __('Category'));
			
			$SchoolYearModel = ClassRegistry::init('SchoolYear');
			$yearList = $SchoolYearModel->getYearList();
			
			$InfrastructureStatusModel = ClassRegistry::init('InfrastructureStatus');
			$InfrastructureSanitationModel = ClassRegistry::init('InfrastructureSanitation');
			$CensusSanitationModel = ClassRegistry::init('CensusSanitation');
			$InfrastructureBuildingModel = ClassRegistry::init('InfrastructureBuilding');
			$CensusBuildingModel = ClassRegistry::init('CensusBuilding');
			
			foreach ($yearList AS $yearId => $yearName) {
				$infraCategories = $this->find('list', array('conditions' => array('InfrastructureCategory.visible' => 1), 'order' => 'InfrastructureCategory.order'));
				foreach ($infraCategories AS $categoryId => $categoryName) {
					$dataInfraStatuses = $InfrastructureStatusModel->find('list', array('conditions' => array('InfrastructureStatus.infrastructure_category_id' => $categoryId, 'InfrastructureStatus.visible' => 1)));
					$countStatuses = count($dataInfraStatuses);
					if ($categoryName == 'Sanitation') {
						$CensusSanitation = ClassRegistry::init('CensusSanitation');
						$genderOptions = $CensusSanitation->SanitationGender->getList(array('listOnly'=>true));
						
						$dataInfraTypes = $InfrastructureSanitationModel->find('list', array('conditions' => array('InfrastructureSanitation.visible' => 1)));
						$dataSanitationMaterials = $CensusSanitationModel->find('all', array(
							'recursive' => -1,
							'fields' => array(
								'CensusSanitation.infrastructure_material_id',
								'InfrastructureMaterial.name'
							),
							'joins' => array(
								array(
									'table' => 'infrastructure_materials',
									'alias' => 'InfrastructureMaterial',
									'conditions' => array(
										'CensusSanitation.infrastructure_material_id = InfrastructureMaterial.id'
									)
								)
							),
							'conditions' => array(
								'CensusSanitation.institution_site_id' => $institutionSiteId,
								'CensusSanitation.school_year_id' => $yearId
							),
							'group' => array('CensusSanitation.infrastructure_material_id'),
							'order' => array('InfrastructureMaterial.order')
								)
						);

						if (count($dataSanitationMaterials) > 0) {
							foreach ($dataSanitationMaterials AS $RowSanitationMaterials) {
								$sanitationMaterialId = $RowSanitationMaterials['CensusSanitation']['infrastructure_material_id'];
								$sanitationMaterialName = $RowSanitationMaterials['InfrastructureMaterial']['name'];
								$dataSanitationMaterialsById = $CensusSanitationModel->find('all', array(
									'recursive' => -1,
									'conditions' => array(
										'CensusSanitation.institution_site_id' => $institutionSiteId,
										'CensusSanitation.school_year_id' => $yearId,
										'CensusSanitation.infrastructure_material_id' => $sanitationMaterialId
									)
										)
								);

								$cellValueCheckSource = array();
								foreach ($dataSanitationMaterialsById AS $rowSanitationMaterialsById) {
									$infrastructure_sanitation_id = $rowSanitationMaterialsById['CensusSanitation']['infrastructure_sanitation_id'];
									$infrastructure_status_id = $rowSanitationMaterialsById['CensusSanitation']['infrastructure_status_id'];
									$censusGenderId = $rowSanitationMaterialsById['CensusSanitation']['gender_id'];
									$cellValueCheckSource[$infrastructure_sanitation_id][$infrastructure_status_id][$censusGenderId] = $rowSanitationMaterialsById['CensusSanitation'];
								}
								//pr($cellValueCheckSource);

								foreach ($genderOptions AS $genderId => $genderName) {
									$countByGender = $CensusSanitationModel->find('count', array(
										'conditions' => array(
											'CensusSanitation.institution_site_id' => $institutionSiteId,
											'CensusSanitation.school_year_id' => $yearId,
											'CensusSanitation.infrastructure_material_id' => $sanitationMaterialId,
											'CensusSanitation.gender_id' => $genderId
										)
											)
									);

									if ($countByGender > 0) {
										$header = array(__('Year'), __('Infrastructure Name'), __('Infrastructure Type'), __('Gender'), __('Category'));
										foreach ($dataInfraStatuses AS $infraStatusName) {
											$header[] = __($infraStatusName);
										}
										$header[] = __('Total');
										$data[] = $header;

										$totalAll = 0;
										foreach ($dataInfraTypes AS $infraTypeId => $infraTypeName) {
											$csvRow = array(__($yearName), __($categoryName), __($sanitationMaterialName), __($genderName), __($infraTypeName));
											$totalRow = 0;
											foreach ($dataInfraStatuses AS $infraStatusId => $infraStatusName) {
												if (isset($cellValueCheckSource[$infraTypeId][$infraStatusId][$censusGenderId])) {
													$cellValue = $cellValueCheckSource[$infraTypeId][$infraStatusId][$censusGenderId]['value'];
												} else {
													$cellValue = 0;
												}
												$csvRow[] = $cellValue;
												$totalRow += $cellValue;
											}
											$csvRow[] = $totalRow;
											$data[] = $csvRow;
											$totalAll += $totalRow;
										}
										$emptyColumns = $countStatuses + 4;
										$rowTotal = array();
										for ($i = 0; $i < $emptyColumns; $i++) {
											$rowTotal[] = '';
										}
										$rowTotal[] = __('Total');
										$rowTotal[] = $totalAll;
										$data[] = $rowTotal;
										$data[] = array();
									}
								}
							}
						}
					} else if ($categoryName == 'Buildings') {
						$dataInfraTypes = $InfrastructureBuildingModel->find('list', array('conditions' => array('InfrastructureBuilding.visible' => 1)));
						$dataBuildingMaterials = $CensusBuildingModel->find('all', array(
							'recursive' => -1,
							'fields' => array(
								'CensusBuilding.infrastructure_material_id',
								'InfrastructureMaterial.name'
							),
							'joins' => array(
								array(
									'table' => 'infrastructure_materials',
									'alias' => 'InfrastructureMaterial',
									'conditions' => array(
										'CensusBuilding.infrastructure_material_id = InfrastructureMaterial.id'
									)
								)
							),
							'conditions' => array(
								'CensusBuilding.institution_site_id' => $institutionSiteId,
								'CensusBuilding.school_year_id' => $yearId
							),
							'group' => array('CensusBuilding.infrastructure_material_id'),
							'order' => array('InfrastructureMaterial.order')
								)
						);

						if (count($dataBuildingMaterials) > 0) {
							foreach ($dataBuildingMaterials AS $RowBuildingMaterials) {
								$buildingMaterialId = $RowBuildingMaterials['CensusBuilding']['infrastructure_material_id'];
								$buildingMaterialName = $RowBuildingMaterials['InfrastructureMaterial']['name'];
								$dataBuildingMaterialsById = $CensusBuildingModel->find('all', array(
									'recursive' => -1,
									'conditions' => array(
										'CensusBuilding.institution_site_id' => $institutionSiteId,
										'CensusBuilding.school_year_id' => $yearId,
										'CensusBuilding.infrastructure_material_id' => $buildingMaterialId
									)
										)
								);

								$cellValueCheckSource = array();
								foreach ($dataBuildingMaterialsById AS $rowBuildingMaterialsById) {
									$infrastructure_building_id = $rowBuildingMaterialsById['CensusBuilding']['infrastructure_building_id'];
									$infrastructure_status_id = $rowBuildingMaterialsById['CensusBuilding']['infrastructure_status_id'];
									$cellValueCheckSource[$infrastructure_building_id][$infrastructure_status_id] = $rowBuildingMaterialsById['CensusBuilding'];
								}
								//pr($buildingCheckSource);

								$header = array(__('Year'), __('Infrastructure Name'), __('Infrastructure Type'), __('Category'));
								foreach ($dataInfraStatuses AS $infraStatusName) {
									$header[] = __($infraStatusName);
								}
								$header[] = __('Total');
								$data[] = $header;

								$totalAll = 0;
								foreach ($dataInfraTypes AS $infraTypeId => $infraTypeName) {
									$csvRow = array(__($yearName), __($categoryName), __($buildingMaterialName), __($infraTypeName));
									$totalRow = 0;
									foreach ($dataInfraStatuses AS $infraStatusId => $infraStatusName) {
										if (isset($cellValueCheckSource[$infraTypeId][$infraStatusId]['value'])) {
											$cellValue = !empty($cellValueCheckSource[$infraTypeId][$infraStatusId]['value']) ? $cellValueCheckSource[$infraTypeId][$infraStatusId]['value'] : 0;
										} else {
											$cellValue = 0;
										}
										$csvRow[] = $cellValue;
										$totalRow += $cellValue;
									}
									$csvRow[] = $totalRow;
									$data[] = $csvRow;
									$totalAll += $totalRow;
								}
								$emptyColumns = $countStatuses + 3;
								$rowTotal = array();
								for ($i = 0; $i < $emptyColumns; $i++) {
									$rowTotal[] = '';
								}
								$rowTotal[] = __('Total');
								$rowTotal[] = $totalAll;
								$data[] = $rowTotal;
								$data[] = array();
							}
						}
					} else {
						$censusModel = $this->censusInfraMapping[$categoryName]['censusModel'];
						$censusModelClass = ClassRegistry::init($this->censusInfraMapping[$categoryName]['censusModel']);
						//$typesModel = $this->censusInfraMapping[$categoryName]['typesModel'];
						$typesModelClass = ClassRegistry::init($this->censusInfraMapping[$categoryName]['typesModel']);
						$typeForeignKey = $this->censusInfraMapping[$categoryName]['typeForeignKey'];
						$dataInfraTypes = $typesModelClass->find('list', array('conditions' => array('visible' => 1)));
						
						$dataCensus = $censusModelClass->find('all', array(
							'recursive' => -1,
							'conditions' => array(
								'institution_site_id' => $institutionSiteId,
								'school_year_id' => $yearId
							)
								)
						);

						if (count($dataCensus) > 0) {
							$cellValueCheckSource = array();
							foreach ($dataCensus AS $rowCensus) {
								$infrastructure_type_id = $rowCensus[$censusModel][$typeForeignKey];
								$infrastructure_status_id = $rowCensus[$censusModel]['infrastructure_status_id'];
								$cellValueCheckSource[$infrastructure_type_id][$infrastructure_status_id] = $rowCensus[$censusModel];
							}
							//pr($cellValueCheckSource);

							$header = $headerCommon;
							foreach ($dataInfraStatuses AS $infraStatusName) {
								$header[] = __($infraStatusName);
							}
							$header[] = __('Total');
							$data[] = $header;

							$totalAll = 0;
							foreach ($dataInfraTypes AS $infraTypeId => $infraTypeName) {
								$csvRow = array(__($yearName), __($categoryName), __($infraTypeName));
								$totalRow = 0;
								foreach ($dataInfraStatuses AS $infraStatusId => $infraStatusName) {
									if (isset($cellValueCheckSource[$infraTypeId][$infraStatusId]['value'])) {
										$cellValue = !empty($cellValueCheckSource[$infraTypeId][$infraStatusId]['value']) ? $cellValueCheckSource[$infraTypeId][$infraStatusId]['value'] : 0;
									} else {
										$cellValue = 0;
									}
									$csvRow[] = $cellValue;
									$totalRow += $cellValue;
								}
								$csvRow[] = $totalRow;
								$data[] = $csvRow;
								$totalAll += $totalRow;
							}
							$emptyColumns = $countStatuses + 2;
							$rowTotal = array();
							for ($i = 0; $i < $emptyColumns; $i++) {
								$rowTotal[] = '';
							}
							$rowTotal[] = __('Total');
							$rowTotal[] = $totalAll;
							$data[] = $rowTotal;
							$data[] = array();
						}
					}
				}
			}
			//pr($data);
			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Infrastructure';
	}
}
