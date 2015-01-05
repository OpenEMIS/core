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

class CensusStudent extends AppModel {
	public $actsAs = array(
		'Containable',
		'CustomReport' => array(
			'_default' => array('visible')
		),
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'SchoolYear',
		'EducationGrade',
		'Students.StudentCategory',
		'InstitutionSite',
		'Gender'
	);
	
	public function getCensusData($siteId, $yearId, $gradeId, $categoryId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('CensusStudent.id', 'CensusStudent.age', 'CensusStudent.male', 'CensusStudent.female', 'CensusStudent.source'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = CensusStudent.education_grade_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id'
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id'
					)
				)
			),
			'conditions' => array(
				'CensusStudent.education_grade_id' => $gradeId,
				'CensusStudent.school_year_id' => $yearId,
				'CensusStudent.student_category_id' => $categoryId,
				'CensusStudent.institution_site_id' => $siteId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'CensusStudent.age')
		));
		return $data;
	}
	
	public function getCensusDataOrderByAge($siteId, $yearId, $programmeId, $categoryId){
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'CensusStudent.id', 
				'CensusStudent.age', 
				'CensusStudent.value', 
				'CensusStudent.source', 
				'CensusStudent.education_grade_id',
				'Gender.id AS genderId',
				'Gender.name AS genderName'
			),
			'joins' => array(
				array(
					'table' => 'field_option_values',
					'alias' => 'Gender',
					'conditions' => array(
						'CensusStudent.gender_id = Gender.id'
					)
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = CensusStudent.education_grade_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id',
												'EducationProgramme.id = ' . $programmeId
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id'
					)
				)
			),
			'conditions' => array(
				'CensusStudent.school_year_id' => $yearId,
				'CensusStudent.student_category_id' => $categoryId,
				'CensusStudent.institution_site_id' => $siteId
			),
			'order' => array('CensusStudent.age', 'EducationGrade.order')
		));
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId, $source = 0) {
		$keys = array();
		$deleted = array();

		if (isset($data['deleted'])) {
			$deleted = $data['deleted'];
			unset($data['deleted']);
		}
		foreach ($deleted as $id) {
			$this->delete($id);
		}

		for ($i = 0; $i < sizeof($data); $i++) {
			$row = $data[$i];
			if ($row['age'] > 0 && !empty($row['value'])) {
				if ($row['id'] == 0) {
					$this->create();
				}
				
				$row['institution_site_id'] = $institutionSiteId;
				$row['source'] = $source;
				$save = $this->save(array('CensusStudent' => $row));
				if ($row['id'] == 0) {
					$keys[strval($i + 1)] = $save['CensusStudent']['id'];
				}
			} else if ($row['id'] > 0 && empty($row['value'])) {
				$this->delete($row['id']);
			}
		}
		return $keys;
	}
	
	public function findListAsSubgroups() {
		$this->formatResult = true;
		$list = $this->find('all', array(
			'fields' => array('CensusStudent.id', 'CensusStudent.education_grade_id', 'CensusStudent.age'),
			'conditions' => array('EducationGrade.visible' => 1),
			'group' => array('CensusStudent.education_grade_id', 'CensusStudent.age')
		));
		
		$ageList = array();
		foreach($list as $obj) {
			$gradeId = $obj['education_grade_id'];
			$age = $obj['age'];
			if(!isset($ageList[$age])) {
				$ageList[$age] = array('grades' => array());
			}
			$ageList[$age]['grades'][] = $gradeId;
		}
		
		return $ageList;
	}
	
	//Used by Yearbook
	public function getCountByCycleId($yearId, $cycleId, $extras=array()) {
		$this->formatResult = true;
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		
		$optionsMale = array('recursive' => -1, 'fields' => array('SUM(CensusStudent.value) AS M'));
		$optionsFemale = array('recursive' => -1, 'fields' => array('SUM(CensusStudent.value) AS F'));
		
		$joins = array(
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = CensusStudent.education_grade_id')
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array(
					'EducationProgramme.id = EducationGrade.education_programme_id',
					'EducationProgramme.education_cycle_id = ' . $cycleId
				)
			)
		);
		
		if(isset($extras['areaId'])) {
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = CensusStudent.institution_site_id')
			);
			$joins[] = array(
				'table' => 'areas',
				'alias' => 'AreaSite',
				'conditions' => array('AreaSite.id = InstitutionSite.area_id')
			);
			$joins[] = array(
				'table' => 'areas',
				'alias' => 'Area',
				'conditions' => array(
					'Area.id = ' . $extras['areaId'],
					'Area.lft <= AreaSite.lft',
					'Area.rght >= AreaSite.rght'
				)
			);
		}
		if(isset($extras['providerId'])) {
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array(
					'InstitutionSite.id = CensusStudent.institution_site_id',
					'InstitutionSite.institution_site_provider_id' => $extras['providerId']
				)
			);
//			$joins[] = array(
//				'table' => 'institutions',
//				'alias' => 'Institution',
//				'conditions' => array(
//					'Institution.id = InstitutionSite.institution_id',
//					'Institution.institution_provider_id = ' . $extras['providerId']
//				)
//			);
		}
		
		$optionsMale['joins'] = $joins;
		$optionsFemale['joins'] = $joins;
		
		$optionsMale['conditions'] = array('CensusStudent.school_year_id' => $yearId, 'CensusStudent.gender_id' => $maleGenderId);
		$optionsFemale['conditions'] = array('CensusStudent.school_year_id' => $yearId, 'CensusStudent.gender_id' => $femaleGenderId);
		
		$optionsMale['group'] = array('EducationProgramme.education_cycle_id');
		$optionsFemale['group'] = array('EducationProgramme.education_cycle_id');
		
		$dataMale = $this->find('first', $optionsMale);
		$dataFemale = $this->find('first', $optionsFemale);
		
		$data = array(
			'M' => $dataMale['M'],
			'F' => $dataFemale['F']
		);
		
		return $data;
	}
	
	public function getCountByAreaId($yearId, $areaId) {
		$this->formatResult = true;

		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		
		$optionsMale = array('recursive' => -1, 'fields' => array('SUM(CensusStudent.value) AS M'));
		$optionsFemale = array('recursive' => -1, 'fields' => array('SUM(CensusStudent.value) AS F'));
		
		$joins = array(
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = CensusStudent.institution_site_id')
			),
			array(
				'table' => 'areas',
				'alias' => 'AreaSite',
				'conditions' => array('AreaSite.id = InstitutionSite.area_id')
			),
			array(
				'table' => 'areas',
				'alias' => 'Area',
				'conditions' => array(
					'Area.id = ' . $areaId,
					'Area.lft <= AreaSite.lft',
					'Area.rght >= AreaSite.rght'
				)
			)
		);
		
		$optionsMale['joins'] = $joins;
		$optionsFemale['joins'] = $joins;
		
		$optionsMale['conditions'] = array('CensusStudent.school_year_id' => $yearId, 'CensusStudent.gender_id' => $maleGenderId);
		$optionsFemale['conditions'] = array('CensusStudent.school_year_id' => $yearId, 'CensusStudent.gender_id' => $femaleGenderId);
		
		$dataMale = $this->find('first', $optionsMale);
		$dataFemale = $this->find('first', $optionsFemale);
		
		$data = array(
			'M' => $dataMale['M'],
			'F' => $dataFemale['F']
		);
		
		return $data;
	}
	// End Yearbook
		
	public function groupByYearGradeCategory($institutionSiteId){
		$data = $this->find('all', array(
				'recursive' => -1,
				'fields' => array(
					'SchoolYear.name',
					'StudentCategory.name',
					'EducationCycle.name',
					'EducationProgramme.name',
					'EducationGrade.name',
					'CensusStudent.school_year_id',
					'CensusStudent.education_grade_id',
					'CensusStudent.student_category_id'
				),
				'joins' => array(
						array(
							'table' => 'school_years',
							'alias' => 'SchoolYear',
							'conditions' => array(
								'CensusStudent.school_year_id = SchoolYear.id'
							)
						),
						array(
							'table' => 'field_option_values',
							'alias' => 'StudentCategory',
							'conditions' => array(
								'CensusStudent.student_category_id = StudentCategory.id'
							)
						),
						array(
							'table' => 'education_grades',
							'alias' => 'EducationGrade',
							'conditions' => array(
								'CensusStudent.education_grade_id = EducationGrade.id'
							)
						),
						array(
							'table' => 'education_programmes',
							'alias' => 'EducationProgramme',
							'conditions' => array(
								'EducationGrade.education_programme_id = EducationProgramme.id'
							)
						),
						array(
							'table' => 'education_cycles',
							'alias' => 'EducationCycle',
							'conditions' => array(
								'EducationProgramme.education_cycle_id = EducationCycle.id'
							)
						),
						array(
							'table' => 'education_levels',
							'alias' => 'EducationLevel',
							'conditions' => array(
								'EducationCycle.education_level_id = EducationLevel.id'
							)
						)
				),
				'conditions' => array('CensusStudent.institution_site_id' => $institutionSiteId),
				'group' => array('CensusStudent.school_year_id', 'CensusStudent.education_grade_id', 'CensusStudent.student_category_id'),
				'order' => array('SchoolYear.name DESC', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order', 'StudentCategory.order')
			)
		);
		
		return $data;
	}
		
	public function enrolment($controller, $params) {
		$controller->Navigation->addCrumb('Students');
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$categoryList = $controller->StudentCategory->getList(1);
		$selectedCategory = sizeof($categoryList) > 0 ? key($categoryList) : 0;
		$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammes($institutionSiteId, $selectedYear);
		
		$data = array();
		if(empty($programmes)) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		} else {
			foreach($programmes as $obj) {
				$dataRowsArr = $this->getEnrolmentDataByRowsView($institutionSiteId, $selectedYear, $obj['education_programme_id'], $selectedCategory, $obj['admission_age']);
				//pr($dataRowsArr);
								
				$conditions = array('EducationGrade.education_programme_id' => $obj['education_programme_id']);
				$gradeList = $this->EducationGrade->findList(array('conditions' => $conditions));
				
				if(empty($gradeList)) {
					$gradeList[0] = '-- ' . __('No Grade') . ' --';
				}
				$data[] = array(
					'name' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
					'grades' => $gradeList,
					'dataRowsArr' => $dataRowsArr,
					'education_programme_id' => $obj['education_programme_id'],
					'admission_age' => $obj['admission_age']
				);
			}
		}
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);
		$controller->set(compact('data', 'selectedYear', 'yearList', 'categoryList', 'isEditable'));
	}
	
	public function enrolmentEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit Students');
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$yearList = $this->SchoolYear->getAvailableYears(); // check for empty year list
		$selectedYear = $controller->getAvailableYearId($yearList);
		$categoryList = $this->StudentCategory->getList(1);
		$selectedCategory = !empty($categoryList) ? key($categoryList) : 0;
		$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammes($institutionSiteId, $selectedYear);
		//pr($programmes);
		$data = array();
		$editable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);
		if(!$editable) {
			$controller->redirect(array('action' => 'enrolment', $selectedYear));
		} else {
			if(empty($programmes)) {
				$controller->Message->alert('InstitutionSiteProgramme.noData');
			} else {
				foreach($programmes as $obj) {
					$dataRowsArr = $this->getEnrolmentDataByRowsEdit($institutionSiteId, $selectedYear, $obj['education_programme_id'], $selectedCategory, $obj['admission_age']);
					//pr($dataRowsArr);
					
					$conditions = array('EducationGrade.education_programme_id' => $obj['education_programme_id']);
					$gradeList = $this->EducationGrade->findList(array('conditions' => $conditions));
					
					if(empty($gradeList)) {
						$gradeList[0] = '-- ' . __('No Grade') . ' --';
					}
					$data[] = array(
						'name' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
						'grades' => $gradeList,
						'dataRowsArr' => $dataRowsArr,
						'education_programme_id' => $obj['education_programme_id'],
						'admission_age' => $obj['admission_age']
					);
				}
			}
		}
		//pr($data);
		$controller->set(compact('data', 'selectedYear', 'yearList', 'categoryList'));
	}
		
	private function getEnrolmentDataByRowsView($institutionSiteId, $yearId, $educationProgrammeId, $studentCategoryId, $age) {
		$ConfigItem = ClassRegistry::init('ConfigItem');
	
		$admission_age = $age;
		
		$agePlus = $ConfigItem->getValue('admission_age_plus');
		$ageMinus = $ConfigItem->getValue('admission_age_minus');
		if(empty($agePlus)){
		  $agePlus = 0;  
		}
		if(empty($ageMinus)){
		  $ageMinus = 0;  
		}
		$ageStart = ($admission_age - $ageMinus) >= 1 ? ($admission_age - $ageMinus) : 1;
		$ageEnd = $admission_age + $agePlus;
		$ageRange = array();
		for ($i = $ageStart; $i <= $ageEnd; $i++) {
			$ageRange[] = $i;
		}
		//pr($ageRange);
		$conditions = array('EducationGrade.education_programme_id' => $educationProgrammeId);
		$gradeList = $this->EducationGrade->findList(array('conditions' => $conditions));
		//pr($gradeList);

		$censusDataOrderByAge = $this->getCensusDataOrderByAge($institutionSiteId, $yearId, $educationProgrammeId, $studentCategoryId);
		//pr($censusDataOrderByAge);
		$enrolmentArr = array();
		foreach ($censusDataOrderByAge AS $row) {
			$recordId = $row['id'];
			$age = $row['age'];
			$education_grade_id = $row['education_grade_id'];
			$source = $row['source'];
			
			if($row['genderName'] == 'Male'){
				$male = $row['value'];
				$enrolmentArr[$age][$education_grade_id]['male'] = $male;
				
				$enrolmentArr[$age][$education_grade_id]['censusId']['male'] = $recordId;
			}else if($row['genderName'] == 'Female'){
				$female = $row['value'];
				$enrolmentArr[$age][$education_grade_id]['female'] = $female;
				
				$enrolmentArr[$age][$education_grade_id]['censusId']['female'] = $recordId;
			}

			$enrolmentArr[$age][$education_grade_id]['source'] = $source;

			if (!in_array($age, $ageRange)) {
				$ageRange[] = $age;
			}
		}
		//pr($ageRange);
		//pr($enrolmentArr);
		
		$defaultValueCharacter = '-';

		$dataRowsArr = array();
		$totalByGradeMale = array();
		$totalByGradeFemale = array();
		$totalByGrade = array();

		asort($ageRange);
		foreach ($ageRange AS $ageOnCheck) {
			$tempRowMale = array();
			$tempRowMale['type'] = 'input';
			$tempRowMale['age'] = $ageOnCheck;
			$tempRowMale['gender'] = 'M';
			$tempRowMale['data']['age'] = $ageOnCheck;
			$tempRowMale['data']['gender'] = 'M';

			$tempRowFemale = array();
			$tempRowFemale['type'] = 'input';
			$tempRowFemale['age'] = $ageOnCheck;
			$tempRowFemale['gender'] = 'F';
			$tempRowFemale['data']['gender'] = 'F';

			foreach ($gradeList AS $gradeId => $gradeName) {
				if (isset($enrolmentArr[$ageOnCheck][$gradeId]['male'])) {
					if(!empty($enrolmentArr[$ageOnCheck][$gradeId]['male'])){
						$tempRowMale['data']['grades'][$gradeId]['value'] = $enrolmentArr[$ageOnCheck][$gradeId]['male'];
						$tempRowMale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['male'];
						$tempRowMale['data']['grades'][$gradeId]['source'] = $enrolmentArr[$ageOnCheck][$gradeId]['source'];
						//$tempRowMale[$gradeId] = $enrolmentArr[$ageOnCheck][$gradeId]['male'];

						if(!isset($totalAgeMale)){
							$totalAgeMale = 0;
						}
						$totalAgeMale += $enrolmentArr[$ageOnCheck][$gradeId]['male'];
						
						if (!isset($totalByGradeMale[$gradeId])) {
							$totalByGradeMale[$gradeId] = 0;
						}
						$totalByGradeMale[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['male'];

						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
						$totalByGrade[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['male'];
					}else{
						$tempRowMale['data']['grades'][$gradeId]['value'] = 0;
						
						if(!isset($totalAgeMale)){
							$totalAgeMale = 0;
						}

						if (isset($enrolmentArr[$ageOnCheck][$gradeId]['male'])) {
							$tempRowMale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['male'];
						} else {
							$tempRowMale['data']['grades'][$gradeId]['censusId'] = 0;
						}

						//$tempRowMale[$gradeId] = 0;

						if (!isset($totalByGradeMale[$gradeId])) {
							$totalByGradeMale[$gradeId] = 0;
						}

						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
					}
				} else {
					$tempRowMale['data']['grades'][$gradeId]['value'] = $defaultValueCharacter;

					if (isset($enrolmentArr[$ageOnCheck][$gradeId]['male'])) {
						$tempRowMale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['male'];
					} else {
						$tempRowMale['data']['grades'][$gradeId]['censusId'] = 0;
					}

					//$tempRowMale[$gradeId] = 0;
				}

				if (isset($enrolmentArr[$ageOnCheck][$gradeId]['female'])) {
					if(!empty($enrolmentArr[$ageOnCheck][$gradeId]['female'])){
						$tempRowFemale['data']['grades'][$gradeId]['value'] = $enrolmentArr[$ageOnCheck][$gradeId]['female'];
						$tempRowFemale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['female'];
						$tempRowFemale['data']['grades'][$gradeId]['source'] = $enrolmentArr[$ageOnCheck][$gradeId]['source'];
						//$tempRowFemale[$gradeId] = $enrolmentArr[$ageOnCheck][$gradeId]['female'];

						if(!isset($totalAgeFemale)){
							$totalAgeFemale = 0;
						}
						$totalAgeFemale += $enrolmentArr[$ageOnCheck][$gradeId]['female'];
						
						if (!isset($totalByGradeFemale[$gradeId])) {
							$totalByGradeFemale[$gradeId] = 0;
						}
						$totalByGradeFemale[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['female'];
						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
						$totalByGrade[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['female'];
					}else{
						$tempRowFemale['data']['grades'][$gradeId]['value'] = 0;
						
						if(!isset($totalAgeFemale)){
							$totalAgeFemale = 0;
						}

						if (isset($enrolmentArr[$ageOnCheck][$gradeId]['female'])) {
							$tempRowFemale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['female'];
						} else {
							$tempRowFemale['data']['grades'][$gradeId]['censusId'] = 0;
						}

						//$tempRowFemale[$gradeId] = 0;

						if (!isset($totalByGradeFemale[$gradeId])) {
							$totalByGradeFemale[$gradeId] = 0;
						}

						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
					}
					
				} else {
					$tempRowFemale['data']['grades'][$gradeId]['value'] = $defaultValueCharacter;

					if (isset($enrolmentArr[$ageOnCheck][$gradeId]['female'])) {
						$tempRowFemale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['female'];
					} else {
						$tempRowFemale['data']['grades'][$gradeId]['censusId'] = 0;
					}

					//$tempRowFemale[$gradeId] = 0;
				}
			}

			if(isset($totalAgeMale) && isset($totalAgeFemale)){
				$totalAge = $totalAgeMale + $totalAgeFemale;
			}else if(!isset($totalAgeMale) && isset($totalAgeFemale)){
				$totalAge = $totalAgeFemale;
			}else if(isset($totalAgeMale) && !isset($totalAgeFemale)){
				$totalAge = $totalAgeMale;
			}else{
				$totalAge = $defaultValueCharacter;
			}
			
			$tempRowMale['data']['totalByAgeMale'] = isset($totalAgeMale) ? $totalAgeMale : $defaultValueCharacter;
			$tempRowMale['data']['lastColumn'] = $totalAge;
			$dataRowsArr[] = $tempRowMale;

			$tempRowFemale['data']['totalByAgeFemale'] = isset($totalAgeFemale) ? $totalAgeFemale : $defaultValueCharacter;
			$dataRowsArr[] = $tempRowFemale;
			
			if(isset($totalAgeMale)){
			   unset($totalAgeMale); 
			}
			
			if(isset($totalAgeFemale)){
			   unset($totalAgeFemale); 
			}
			
			if(isset($totalAge)){
			   unset($totalAge); 
			}
		}

		$rowTotalMale = array();
		$rowTotalMale['type'] = 'read-only';
		$rowTotalMale['gender'] = 'M';
		$rowTotalMale['data']['firstColumn'] = __('Total');
		$rowTotalMale['data']['gender'] = 'M';

		$rowTotalFemale = array();
		$rowTotalFemale['type'] = 'read-only';
		$rowTotalFemale['gender'] = 'F';
		$rowTotalFemale['data']['gender'] = 'F';

		$rowTotal = array();
		$rowTotal['type'] = 'read-only';
		$rowTotal['gender'] = 'all';
		$rowTotal['data']['colspan2'] = __('Total');

		foreach ($gradeList AS $gradeId => $gradeName) {
			$rowTotalMale['data']['grades'][$gradeId]['value'] = isset($totalByGradeMale[$gradeId]) ? $totalByGradeMale[$gradeId] : $defaultValueCharacter;
			$rowTotalFemale['data']['grades'][$gradeId]['value'] = isset($totalByGradeFemale[$gradeId]) ? $totalByGradeFemale[$gradeId] : $defaultValueCharacter;
			$rowTotal['data']['grades'][$gradeId]['value'] = isset($totalByGrade[$gradeId]) ? $totalByGrade[$gradeId] : $defaultValueCharacter;

			if(isset($totalByGradeMale[$gradeId])){
				if(!isset($totalEnrolMale)){
					$totalEnrolMale = 0;
				}
				$totalEnrolMale += $totalByGradeMale[$gradeId];
			}
			
			if(isset($totalByGradeFemale[$gradeId])){
				if(!isset($totalEnrolFemale)){
					$totalEnrolFemale = 0;
				}
				$totalEnrolFemale += $totalByGradeFemale[$gradeId];
			}
			
			if(isset($totalByGrade[$gradeId])){
				if(!isset($totalEnrolAllGrades)){
					$totalEnrolAllGrades = 0;
				}
				$totalEnrolAllGrades += $totalByGrade[$gradeId];
			}
		}
		$rowTotalMale['data']['totalMaleAllGrades'] = isset($totalEnrolMale) ? $totalEnrolMale : $defaultValueCharacter;
		
		if(isset($totalEnrolMale) && isset($totalEnrolFemale)){
			$total2GendersAllGrades = $totalEnrolMale + $totalEnrolFemale;
		}else if(!isset($totalEnrolMale) && isset($totalEnrolFemale)){
			$total2GendersAllGrades = $totalEnrolFemale;
		}else if(isset($totalEnrolMale) && !isset($totalEnrolFemale)){
			$total2GendersAllGrades = $totalEnrolMale;
		}else{
			$total2GendersAllGrades = $defaultValueCharacter;
		}
		$rowTotalMale['data']['lastColumn'] = $total2GendersAllGrades;

		$rowTotalFemale['data']['totalFemaleAllGrades'] = isset($totalEnrolFemale) ? $totalEnrolFemale : $defaultValueCharacter;
		$rowTotal['data']['totalAllGrades'] = isset($totalEnrolAllGrades) ? $totalEnrolAllGrades : $defaultValueCharacter;
		$rowTotal['data']['bottomRight'] = '';

		$dataRowsArr[] = $rowTotalMale;
		$dataRowsArr[] = $rowTotalFemale;
		$dataRowsArr[] = $rowTotal;

		//pr($dataRowsArr);
		return $dataRowsArr;
	}
		
	private function getEnrolmentDataByRowsEdit($institutionSiteId, $yearId, $educationProgrammeId, $studentCategoryId, $age) {
		$ConfigItem = ClassRegistry::init('ConfigItem');
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
	
		$admission_age = $age;
		$agePlus = $ConfigItem->getValue('admission_age_plus');
		$ageMinus = $ConfigItem->getValue('admission_age_minus');
		if(empty($agePlus)){
		  $agePlus = 0;  
		}
		if(empty($ageMinus)){
		  $ageMinus = 0;  
		}
		$ageStart = ($admission_age - $ageMinus) >= 1 ? ($admission_age - $ageMinus) : 1;
		$ageEnd = $admission_age + $agePlus;
		$ageRange = array();
		for ($i = $ageStart; $i <= $ageEnd; $i++) {
			$ageRange[] = $i;
		}
		$ageRangeStand = $ageRange;
		
		//pr($ageRange);
		$conditions = array('EducationGrade.education_programme_id' => $educationProgrammeId);
		$gradeList = $this->EducationGrade->findList(array('conditions' => $conditions));
		//pr($gradeList);

		$censusDataOrderByAge = $this->getCensusDataOrderByAge($institutionSiteId, $yearId, $educationProgrammeId, $studentCategoryId);
		//pr($censusDataOrderByAge);die;
		$enrolmentArr = array();
		foreach ($censusDataOrderByAge AS $row) {
			$recordId = $row['id'];
			$age = $row['age'];
			$education_grade_id = $row['education_grade_id'];
			$source = $row['source'];
			
			if($row['genderName'] == 'Male'){
				$male = $row['value'];
				$enrolmentArr[$age][$education_grade_id]['male'] = $male;
				
				$enrolmentArr[$age][$education_grade_id]['censusId']['male'] = $recordId;
			}else if($row['genderName'] == 'Female'){
				$female = $row['value'];
				$enrolmentArr[$age][$education_grade_id]['female'] = $female;
				
				$enrolmentArr[$age][$education_grade_id]['censusId']['female'] = $recordId;
			}

			$enrolmentArr[$age][$education_grade_id]['source'] = $source;

			if (!in_array($age, $ageRange)) {
				$ageRange[] = $age;
			}
		}
		//pr($ageRange);
		//pr($enrolmentArr);
		
		$defaultValueCharacter = '';

		$dataRowsArr = array();
		$totalByGradeMale = array();
		$totalByGradeFemale = array();
		$totalByGrade = array();

		asort($ageRange);
		foreach ($ageRange AS $ageOnCheck) {
			$tempRowMale = array();
			$tempRowMale['type'] = 'input';
			$tempRowMale['age'] = $ageOnCheck;
			$tempRowMale['gender'] = 'M';
			$tempRowMale['genderId'] = $maleGenderId;
			$tempRowMale['data']['age'] = $ageOnCheck;
			$tempRowMale['data']['gender'] = 'M';
			if(!in_array($ageOnCheck, $ageRangeStand)){
				$tempRowMale['ageEditable'] = 'yes';
			}

			$tempRowFemale = array();
			$tempRowFemale['type'] = 'input';
			$tempRowFemale['age'] = $ageOnCheck;
			$tempRowFemale['gender'] = 'F';
			$tempRowFemale['genderId'] = $femaleGenderId;
			$tempRowFemale['data']['gender'] = 'F';

			foreach ($gradeList AS $gradeId => $gradeName) {
				if (isset($enrolmentArr[$ageOnCheck][$gradeId]['male'])) {
					if(!empty($enrolmentArr[$ageOnCheck][$gradeId]['male'])){
						$tempRowMale['data']['grades'][$gradeId]['value'] = $enrolmentArr[$ageOnCheck][$gradeId]['male'];
						$tempRowMale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['male'];
						$tempRowMale['data']['grades'][$gradeId]['source'] = $enrolmentArr[$ageOnCheck][$gradeId]['source'];
						//$tempRowMale[$gradeId] = $enrolmentArr[$ageOnCheck][$gradeId]['male'];

						if(!isset($totalAgeMale)){
							$totalAgeMale = 0;
						}
						$totalAgeMale += $enrolmentArr[$ageOnCheck][$gradeId]['male'];
						
						if (!isset($totalByGradeMale[$gradeId])) {
							$totalByGradeMale[$gradeId] = 0;
						}
						$totalByGradeMale[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['male'];

						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
						$totalByGrade[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['male'];
					}else{
						$tempRowMale['data']['grades'][$gradeId]['value'] = 0;
						
						if(!isset($totalAgeMale)){
							$totalAgeMale = 0;
						}

						if (isset($enrolmentArr[$ageOnCheck][$gradeId]['male'])) {
							$tempRowMale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['male'];
						} else {
							$tempRowMale['data']['grades'][$gradeId]['censusId'] = 0;
						}

						//$tempRowMale[$gradeId] = 0;

						if (!isset($totalByGradeMale[$gradeId])) {
							$totalByGradeMale[$gradeId] = 0;
						}

						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
					}
				} else {
					$tempRowMale['data']['grades'][$gradeId]['value'] = $defaultValueCharacter;

					if (isset($enrolmentArr[$ageOnCheck][$gradeId]['male'])) {
						$tempRowMale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['male'];
					} else {
						$tempRowMale['data']['grades'][$gradeId]['censusId'] = 0;
					}

					//$tempRowMale[$gradeId] = 0;
				}

				if (isset($enrolmentArr[$ageOnCheck][$gradeId]['female'])) {
					if(!empty($enrolmentArr[$ageOnCheck][$gradeId]['female'])){
						$tempRowFemale['data']['grades'][$gradeId]['value'] = $enrolmentArr[$ageOnCheck][$gradeId]['female'];
						$tempRowFemale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['female'];
						$tempRowFemale['data']['grades'][$gradeId]['source'] = $enrolmentArr[$ageOnCheck][$gradeId]['source'];
						//$tempRowFemale[$gradeId] = $enrolmentArr[$ageOnCheck][$gradeId]['female'];

						if(!isset($totalAgeFemale)){
							$totalAgeFemale = 0;
						}
						$totalAgeFemale += $enrolmentArr[$ageOnCheck][$gradeId]['female'];
						
						if (!isset($totalByGradeFemale[$gradeId])) {
							$totalByGradeFemale[$gradeId] = 0;
						}
						$totalByGradeFemale[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['female'];
						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
						$totalByGrade[$gradeId] += $enrolmentArr[$ageOnCheck][$gradeId]['female'];
					}else{
						$tempRowFemale['data']['grades'][$gradeId]['value'] = 0;
						
						if(!isset($totalAgeFemale)){
							$totalAgeFemale = 0;
						}

						if (isset($enrolmentArr[$ageOnCheck][$gradeId]['female'])) {
							$tempRowFemale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['female'];
						} else {
							$tempRowFemale['data']['grades'][$gradeId]['censusId'] = 0;
						}

						//$tempRowFemale[$gradeId] = 0;

						if (!isset($totalByGradeFemale[$gradeId])) {
							$totalByGradeFemale[$gradeId] = 0;
						}

						if (!isset($totalByGrade[$gradeId])) {
							$totalByGrade[$gradeId] = 0;
						}
					}
					
				} else {
					$tempRowFemale['data']['grades'][$gradeId]['value'] = $defaultValueCharacter;

					if (isset($enrolmentArr[$ageOnCheck][$gradeId]['female'])) {
						$tempRowFemale['data']['grades'][$gradeId]['censusId'] = $enrolmentArr[$ageOnCheck][$gradeId]['censusId']['female'];
					} else {
						$tempRowFemale['data']['grades'][$gradeId]['censusId'] = 0;
					}

					//$tempRowFemale[$gradeId] = 0;
				}
			}

			if(isset($totalAgeMale) && isset($totalAgeFemale)){
				$totalAge = $totalAgeMale + $totalAgeFemale;
			}else if(!isset($totalAgeMale) && isset($totalAgeFemale)){
				$totalAge = $totalAgeFemale;
			}else if(isset($totalAgeMale) && !isset($totalAgeFemale)){
				$totalAge = $totalAgeMale;
			}else{
				$totalAge = $defaultValueCharacter;
			}
			
			$tempRowMale['data']['totalByAgeMale'] = isset($totalAgeMale) ? $totalAgeMale : $defaultValueCharacter;
			$tempRowMale['data']['totalByAgeAllGender'] = $totalAge;
			$dataRowsArr[] = $tempRowMale;

			$tempRowFemale['data']['totalByAgeFemale'] = isset($totalAgeFemale) ? $totalAgeFemale : $defaultValueCharacter;
			$dataRowsArr[] = $tempRowFemale;
			
			if($totalAge !== $defaultValueCharacter){
				if(!isset($totalAllAges)){
					$totalAllAges = 0;
				}
				$totalAllAges += $totalAge;
			}
			
			if(isset($totalAgeMale)){
			   unset($totalAgeMale); 
			}
			
			if(isset($totalAgeFemale)){
			   unset($totalAgeFemale); 
			}
			
			if(isset($totalAge)){
			   unset($totalAge); 
			}
		}
		
		$rowTotal = array();
		$rowTotal['type'] = 'read-only';
		$rowTotal['gender'] = 'na';
		$countGrades = count($gradeList);
		$rowTotal['colspan'] = $countGrades + 3;
		$rowTotal['data']['firstHalf'] = __('Total');
		$rowTotal['data']['totalAllGrades'] = isset($totalAllAges) ? $totalAllAges : $defaultValueCharacter;
		
		$dataRowsArr[] = $rowTotal;
		
		//pr($dataRowsArr);
		return $dataRowsArr;
	}
	
	public function enrolmentAjax($controller, $params) {
		$EducationProgramme = ClassRegistry::init('EducationProgramme');
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
			
		$this->render = false;

		if($controller->request->is('get')) {
			$yearId = $controller->params['pass'][0];
			//$gradeId = $controller->params->query['gradeId'];
			$categoryId = $controller->params->query['categoryId'];
			$programmeId = $controller->params->query['programmeId'];
			
			$programmeObj = $EducationProgramme->getProgrammeById($programmeId);
			$admission_age = $programmeObj['admission_age'];
			
			if($controller->params->query['edit'] === 'true') {
				$dataRowsArr = $this->getEnrolmentDataByRowsEdit($institutionSiteId, $yearId, $programmeId, $categoryId, $admission_age);
			} else {
				$dataRowsArr = $this->getEnrolmentDataByRowsView($institutionSiteId, $yearId, $programmeId, $categoryId, $admission_age);
			}
			//pr($dataRowsArr);
							
			$conditions = array('EducationGrade.education_programme_id' => $programmeId);
			$gradeList = $this->EducationGrade->findList(array('conditions' => $conditions));
						
			//$enrolment = $this->getCensusDataOrderByAge($institutionSiteId, $yearId, $programmeId, $categoryId);
						
			$controller->set(compact('dataRowsArr', 'gradeList'));
			
			if($controller->params->query['edit'] === 'true') {
				$controller->render('enrolment_edit_ajax');
			} else {
				$controller->render('enrolment_ajax');
			}
		} else {
			$keys = $this->saveCensusData($controller->data, $institutionSiteId);
			return json_encode($keys);
		}
	}
	
	public function enrolmentAddRow($controller, $params) {
		$controller->layout = 'ajax';
		
		$EducationProgramme = ClassRegistry::init('EducationProgramme');

		$age = $controller->params->query['age'];
		$programmeId = $controller->params->query['programmeId'];
				
		$programmeObj = $EducationProgramme->getProgrammeById($programmeId);
		$admission_age = $programmeObj['admission_age'];
		
		if($age == 0){
			$age = $admission_age;
		}
								
		$conditions = array('EducationGrade.education_programme_id' => $programmeId);
		$gradeList = $this->EducationGrade->findList(array('conditions' => $conditions));
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		
		$controller->set(compact('age', 'gradeList', 'maleGenderId', 'femaleGenderId'));	 
	}
	
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
			//$header = array('Age', 'Male', 'Female', __('Total'));
			$header = array(__('Year'), __('Programme'), __('Grade'), __('Category'), __('Age'), __('Male'), __('Female'), __('Total'));

			$baseData = $this->groupByYearGradeCategory($institutionSiteId);
			
			$maleGenderId = $this->Gender->getIdByName('Male');
			$femaleGenderId = $this->Gender->getIdByName('Female');

			foreach ($baseData AS $row) {
				$year = $row['SchoolYear']['name'];
				$educationCycle = $row['EducationCycle']['name'];
				$educationProgramme = $row['EducationProgramme']['name'];
				$educationGrade = $row['EducationGrade']['name'];
				$studentCategory = $row['StudentCategory']['name'];

				$data[] = $header;

				$censusData = $this->find('all', array(
					'recursive' => -1,
					'fields' => array(
						'CensusStudent.age',
						'CensusStudent.gender_id',
						'CensusStudent.value'
					),
					'conditions' => array(
						'CensusStudent.institution_site_id' => $institutionSiteId,
						'CensusStudent.school_year_id' => $row['CensusStudent']['school_year_id'],
						'CensusStudent.education_grade_id' => $row['CensusStudent']['education_grade_id'],
						'CensusStudent.student_category_id' => $row['CensusStudent']['student_category_id']
					),
					'order' => array('CensusStudent.age')
				));
				
				$tempData = array();
				
				foreach ($censusData AS $censusRow) {
					$age = $censusRow['CensusStudent']['age'];
					$genderId = $censusRow['CensusStudent']['gender_id'];
					$value = $censusRow['CensusStudent']['value'];
					
					$tempData[$age][$genderId] = $value;
				}
				
				$total = 0;
				foreach ($tempData AS $age => $ageArr) {
					if(!empty($ageArr[$maleGenderId])){
						$maleValue = $ageArr[$maleGenderId];
					}else{
						$maleValue = 0;
					}
					
					if(!empty($ageArr[$femaleGenderId])){
						$femaleValue = $ageArr[$femaleGenderId];
					}else{
						$femaleValue = 0;
					}
					
					$data[] = array(
						$year,
						$educationCycle . ' - ' . $educationProgramme,
						$educationGrade,
						$studentCategory,
						$age,
						$maleValue,
						$femaleValue,
						$maleValue + $femaleValue
					);

					$total += $maleValue;
					$total += $femaleValue;
				}

				$data[] = array('', '', '', '', '', '', __('Total'), $total);
				$data[] = array();
			}

			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Studensts';
	}
}