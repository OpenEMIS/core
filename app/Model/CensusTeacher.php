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

class CensusTeacher extends AppModel {
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'SchoolYear',
		'InstitutionSite',
		'Gender' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'gender_id'
		)
	);
	
	public function getTeacherId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusTeacher.id'),
			'conditions' => array('CensusTeacher.institution_site_id' => $institutionSiteId, 'CensusTeacher.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		$genderOptions = array(
			$maleGenderId => 'Male', 
			$femaleGenderId => 'Female'
		);
		//pr($genderOptions);die;
		//pr($class);
		//pr($data);
		foreach($class as $key => &$obj) {
			foreach($obj['education_grades'] as $gradeId => &$grade) {
				$maleChecked = 0;
				$femaleChecked = 0;
				foreach($data as $value) {
					if($value['education_grade_id'] == $gradeId && $value['education_programme_id'] == $obj['education_programme_id']) {
						$genderId = $value['gender_id'];
						$genderName = $genderOptions[$genderId];
						$recordValue = $value['value'];
						$source = $value['source'];
						
						$grade[$genderId] = array(
							'genderName' => $genderName,
							'value' => $recordValue,
							'source' => $source
						);
						
						if($genderName == 'Male'){
							$maleChecked = 1;
						}else{
							$femaleChecked = 1;
						}
						
						if($maleChecked == 1 && $femaleChecked == 1){
							break;
						}
					}
				}
			}
		}
		//pr($class);
	}
	
	public function getSingleGradeData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusTeacher.id',
				'CensusTeacher.gender_id',
				'CensusTeacher.value',
				'CensusTeacher.source',
				'EducationProgramme.id AS education_programme_id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id AS education_grade_id',
				'EducationGrade.name AS education_grade_name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = CensusTeacher.institution_site_id',
						'InstitutionSiteProgramme.school_year_id = CensusTeacher.school_year_id',
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.education_programme_id = EducationProgramme.id')
				),
				array(
					'table' => 'census_teacher_grades',
					'alias' => 'CensusTeacherGrade',
					'conditions' => array(
						'CensusTeacherGrade.census_teacher_id = CensusTeacher.id',
						'CensusTeacherGrade.education_grade_id = EducationGrade.id'
					)
				)
			),
			'conditions' => array(
				'CensusTeacher.school_year_id' => $yearId,
				'CensusTeacher.institution_site_id' => $institutionSiteId
			),
			'group' => array('CensusTeacher.id HAVING COUNT(CensusTeacherGrade.census_teacher_id) = 1'),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getMultiGradeData($institutionSiteId, $yearId) {
		$dataGradeStr = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array('CensusTeacher.id', 'GROUP_CONCAT(CensusTeacherGrade.education_grade_id ORDER BY CensusTeacherGrade.education_grade_id SEPARATOR "-") AS gradeStr'),
			'joins' => array(
				array(
					'table' => 'census_teacher_grades',
					'alias' => 'CensusTeacherGrade',
					'conditions' => array('CensusTeacherGrade.census_teacher_id = CensusTeacher.id')
				)
			),
			'conditions' => array(
				'CensusTeacher.institution_site_id' => $institutionSiteId,
				'CensusTeacher.school_year_id' => $yearId
			),
			'group' => array('CensusTeacher.id HAVING COUNT(CensusTeacherGrade.census_teacher_id) > 1')
		));
		
		$censusTeacherIds = array();
		$baseGradeIds = array();
		foreach($dataGradeStr AS $rowGradeStr){
			$censusTeacherId = $rowGradeStr['CensusTeacher']['id'];
			$gradeStr = $rowGradeStr[0]['gradeStr'];
			
			if(!empty($gradeStr)){
				$censusTeacherIds[] = $censusTeacherId;
				$baseGradeIds[$censusTeacherId] = $gradeStr;
			}
		}
		
		$gradeList = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusTeacher.id',
				'CensusTeacher.gender_id',
				'CensusTeacher.value',
				'CensusTeacher.source',
				'EducationProgramme.id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id',
				'EducationGrade.name'
			),
			'joins' => array(
				array(
					'table' => 'census_teacher_grades',
					'alias' => 'CensusTeacherGrade',
					'conditions' => array('CensusTeacherGrade.census_teacher_id = CensusTeacher.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = CensusTeacherGrade.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				)
			),
			'conditions' => array('CensusTeacher.id' => $censusTeacherIds),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		$genderOptions = array(
			$maleGenderId => 'Male',
			$femaleGenderId => 'Female'
		);
		
		$data = array();
		foreach($gradeList as $obj) {
			$grade = $obj['EducationGrade'];
			$teacher = $obj['CensusTeacher'];
			$programmeId = $obj['EducationProgramme']['id'];
			$censusTeacherId = $teacher['id'];
			$genderId = $teacher['gender_id'];
			
			$tempGradeStr = $baseGradeIds[$censusTeacherId];
			
			if ($genderOptions[$genderId] == 'Male') {
				if(!isset($data[$tempGradeStr]['genders'][$genderId])){
					$data[$tempGradeStr]['genders'][$genderId] = array(
						'source' => $teacher['source'],
						'value' => $teacher['value']
					);
				}
			} else {
				if(!isset($data[$tempGradeStr]['genders'][$genderId])){
					$data[$tempGradeStr]['genders'][$genderId] = array(
						'source' => $teacher['source'],
						'value' => $teacher['value']
					);
				}
			}
			
			$data[$tempGradeStr]['programmes'][$programmeId] = $obj[0]['education_programme_name'];
			$data[$tempGradeStr]['grades'][$grade['id']] = $grade['name'];
		}

		return $data;
	}
	
	public function clean($data, $yearId, $institutionSiteId, &$duplicate) {
		//pr($data);die;
		$clean = array();
		$gradeList = array();
		// get the current list of census teacher record ids from the database
		$ids = $this->getTeacherId($institutionSiteId, $yearId);		
		foreach($data as $obj) {
			// remove duplicate grades per record
			$grades = array_unique($obj['CensusTeacherGrade']);
			$genderId = $obj['gender_id'];
			$gradesForSearch= array(
				'genderId' => $genderId,
				'grades' => $grades
			);
			
			if(array_search($gradesForSearch, $gradeList, true) === false) { // the multi grade combination must not exists
				if(!empty($obj['value'])){
					$gradeList[] = $gradesForSearch;
					// reuse the current census class record ids
					$id = current($ids);
					if($id === false) {
						$id = null;
					} else {
						next($ids);
					}
					// build census grades records
					foreach($grades as &$grade) {
						$grade = array('census_teacher_id' => $id, 'education_grade_id' => $grade);
					}

					if ((int)$obj['value'] < 0){ $obj['value'] =0; }

					$clean[] = array(
						'id' => $id,
						'gender_id' => $obj['gender_id'],
						'value' => $obj['value'],
						'institution_site_id' => $institutionSiteId,
						'school_year_id' => $yearId,
						'CensusTeacherGrade' => $grades
					);
				}
			} else {
				if(!$duplicate) $duplicate = true;
			}
		}
		// Reset all values of male and female for the existing ids
		$this->unbindModel(array('belongsTo' => array_keys($this->belongsTo)), true);
		$this->updateAll(
			array('CensusTeacher.gender_id' => 0, 'CensusTeacher.value' => 0),
			array('CensusTeacher.id' => $ids)
		);
		// Finally, delete all existing census grades records and re-insert them upon saving
		$CensusTeacherGrade = ClassRegistry::init('CensusTeacherGrade');
		$CensusTeacherGrade->deleteAll(array('CensusTeacherGrade.census_teacher_id' => $ids), false);
		return $clean;
	}
	
	public function saveCensusData($data) {
		$CensusTeacherGrade = ClassRegistry::init('CensusTeacherGrade');
		foreach($data as $obj) {
			if(empty($obj['id'])) {
				$this->create();
			}
			$censusGrades = $obj['CensusTeacherGrade'];
			unset($obj['CensusTeacherGrade']);
			$result = $this->save($obj);
			$id = $result['CensusTeacher']['id'];
			foreach($censusGrades as $grade) {
				$grade['census_teacher_id'] = $id;
				$CensusTeacherGrade->save($grade);
			}
		}
	}
	
	//Used by Yearbook
	public function getCountByCycleId($yearId, $cycleId, $extras=array()) {
		$this->formatResult = true;
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		
		$optionsMale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusTeacher.value) AS M')
		);
		
		$optionsFemale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusTeacher.value) AS F')
		);
		
		$joins = array(
			array(
				'table' => 'census_teacher_grades',
				'alias' => 'CensusTeacherGrade',
				'conditions' => array('CensusTeacherGrade.census_teacher_id = CensusTeacher.id')
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = CensusTeacherGrade.education_grade_id')
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
				'conditions' => array('InstitutionSite.id = CensusTeacher.institution_site_id')
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
					'InstitutionSite.id = CensusTeacher.institution_site_id',
					'InstitutionSite.institution_site_provider_id' => $extras['providerId']
				)
			);
		}
		
		$optionsMale['joins'] = $joins;
		$optionsFemale['joins'] = $joins;
		
		$optionsMale['conditions'] = array(
			'CensusTeacher.school_year_id' => $yearId,
			'CensusTeacher.gender_id' => $maleGenderId
		);
		$optionsFemale['conditions'] = array(
			'CensusTeacher.school_year_id' => $yearId,
			'CensusTeacher.gender_id' => $femaleGenderId
		);
		
		$options['group'] = array('EducationProgramme.education_cycle_id');
		$optionsMale['group'] = $options['group'];
		$optionsFemale['group'] = $options['group'];
		
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
		
		$optionsMale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusTeacher.value) AS M')
		);
		
		$optionsFemale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusTeacher.value) AS F')
		);
		
		$joins = array(
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = CensusTeacher.institution_site_id')
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
		
		$optionsMale['conditions'] = array(
			'CensusTeacher.school_year_id' => $yearId,
			'CensusTeacher.gender_id' => $maleGenderId
		);
		$optionsFemale['conditions'] = array(
			'CensusTeacher.school_year_id' => $yearId,
			'CensusTeacher.gender_id' => $femaleGenderId
		);
		
		$dataMale = $this->find('first', $optionsMale);
		$dataFemale = $this->find('first', $optionsFemale);
		
		$data = array(
			'M' => $dataMale['M'],
			'F' => $dataFemale['F']
		);
		
		return $data;
	}
	// End Yearbook
		
	public function teachers($controller, $params) {
		$controller->Navigation->addCrumb('Teachers');

		$yearList = $this->SchoolYear->getAvailableYears();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$displayContent = true;
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$programmes = $InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedYear);

		$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $selectedYear);
		if (empty($programmes)) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
			$displayContent = false;
		} else {
			$maleGenderId = $this->Gender->getIdByName('Male');
			$femaleGenderId = $this->Gender->getIdByName('Female');
			$genderOptions = array(
				$maleGenderId => 'Male', 
				$femaleGenderId => 'Female'
			);
			//pr($genderOptions);die;
			
			$EducationLevel = ClassRegistry::init('EducationLevel');
			$eduLevelOptions = $EducationLevel->getInstitutionLevelsBySchoolYear($institutionSiteId, $selectedYear);
			
			$fte = $controller->CensusTeacherFte->getCensusData($institutionSiteId, $selectedYear);
			$training = $controller->CensusTeacherTraining->getCensusData($institutionSiteId, $selectedYear);
			$singleGradeTeachers = $this->getSingleGradeData($institutionSiteId, $selectedYear);
			$multiGradeData = $this->getMultiGradeData($institutionSiteId, $selectedYear);
			$singleGradeData = $programmeGrades;
			
			$this->mergeSingleGradeData($singleGradeData, $singleGradeTeachers);
			
			$controller->set(compact('fte', 'training', 'singleGradeData', 'multiGradeData', 'genderOptions', 'eduLevelOptions'));
		}
		
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);
		
		$controller->set(compact('displayContent', 'selectedYear', 'yearList', 'isEditable'));
	}

	public function teachersEdit($controller, $params) {
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Teachers');

			$yearList = $this->SchoolYear->getAvailableYears();
			$selectedYear = $controller->getAvailableYearId($yearList);	
			$editable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);
			if (!$editable) {
				$controller->redirect(array('action' => 'teachers', $selectedYear));
			} else {
				$displayContent = true;
				$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $selectedYear);

				if (empty($programmeGrades)) {
					$controller->Message->alert('InstitutionSiteProgramme.noData');
					$displayContent = false;
				} else {
					$maleGenderId = $this->Gender->getIdByName('Male');
					$femaleGenderId = $this->Gender->getIdByName('Female');
					$genderOptions = array(
						$maleGenderId => 'Male', 
						$femaleGenderId => 'Female'
					);
					//pr($genderOptions);die;

					$EducationLevel = ClassRegistry::init('EducationLevel');
					$eduLevelOptions = $EducationLevel->getInstitutionLevelsBySchoolYear($institutionSiteId, $selectedYear);
					
					$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $selectedYear, false);
					$fte = $controller->CensusTeacherFte->getCensusData($institutionSiteId, $selectedYear);
					$training = $controller->CensusTeacherTraining->getCensusData($institutionSiteId, $selectedYear);
					$singleGradeTeachers = $this->getSingleGradeData($institutionSiteId, $selectedYear);
					$multiGradeData = $this->getMultiGradeData($institutionSiteId, $selectedYear);
					$singleGradeData = $programmeGrades;
					$this->mergeSingleGradeData($singleGradeData, $singleGradeTeachers);
					
					$controller->set(compact('programmes', 'programmeGrades', 'fte', 'training', 'singleGradeData', 'multiGradeData', 'genderOptions', 'eduLevelOptions'));
				}
				
				$controller->set(compact('displayContent', 'selectedYear', 'yearList'));
			}
		} else {
			$yearId = $controller->data['CensusTeacher']['school_year_id'];
			unset($controller->request->data['CensusTeacher']['school_year_id']);
			$fte = $controller->data['CensusTeacherFte'];
			$training = $controller->data['CensusTeacherTraining'];
			$teachers = $controller->data['CensusTeacher'];
			$controller->CensusTeacherFte->saveCensusData($fte, $yearId, $institutionSiteId);
			$controller->CensusTeacherTraining->saveCensusData($training, $yearId, $institutionSiteId);
			$duplicate = false;
			$data = $this->clean($teachers, $yearId, $institutionSiteId, $duplicate);
			if ($duplicate) {
				$controller->Utility->alert($controller->Utility->getMessage('CENSUS_MULTI_DUPLICATE'), array('type' => 'warn'));
			}
			$this->saveCensusData($data);
			$controller->Utility->alert($controller->Utility->getMessage('CENSUS_UPDATED'));
			$controller->redirect(array('action' => 'teachers', $yearId));
		}
	}

	public function teachersAddMultiTeacher($controller, $params) {
		$controller->layout = 'ajax';
		//$this->render = false;

		$yearId = $controller->params['pass'][0];
		$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($controller->institutionSiteId, $yearId);
		$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($controller->institutionSiteId, $yearId, false);

		$i = $controller->params->query['index'];
		$body = $controller->params->query['tableBody'];
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		
		$controller->set(compact('i', 'body', 'programmes', 'programmeGrades', 'yearId', 'maleGenderId', 'femaleGenderId'));
	}

	public function teachersAddMultiGrade($controller, $params) {
		$this->render = false;

		$row = $controller->params->query['row'];
		$index = $controller->params->query['index'];
		$yearId = $controller->params['pass'][0];
		$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($controller->institutionSiteId, $yearId);
		$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($controller->institutionSiteId, $yearId, false);
		$grades = $programmeGrades[current($programmes)]['education_grades'];

		$option = '<option value="%d">%s</option>';
		$programmesHtml = sprintf('<div class="table_cell_row"><select class="form-control" index="%d" url="Census/loadGradeList" onchange="Census.loadGradeList(this)">', $index);
		foreach ($programmes as $id => $value) {
			$programmesHtml .= sprintf($option, $id, $value);
		}
		$programmesHtml .= '</select></div>';

		$gradesHtml = sprintf('<div class="table_cell_row"><select class="form-control" index="%d" name="data[CensusTeacher][%d][CensusTeacherGrade][%d]" onchange="CensusTeachers.updateHiddenGradeId(this)">', $index, $row, $index);
		
		foreach ($grades as $id => $value) {
			$gradesHtml .= sprintf($option, $id, $value['gradeName']);
			
			if(!isset($defaultGradeId)){
				$defaultGradeId = $id;
			}
		}
		
		$gradesHtml .= '</select>';
		$gradesHtml .= sprintf('<input type="hidden" id="education_grade_id" value="%d" class="hiddenGradeId" name="data[CensusTeacher][%d][CensusTeacherGrade][%d]">', $defaultGradeId, $row+1, $index);
		$gradesHtml .= '</div>';

		$data = array('programmes' => $programmesHtml, 'grades' => $gradesHtml);
		return json_encode($data);
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
			$maleGenderId = $this->Gender->getIdByName('Male');
			$femaleGenderId = $this->Gender->getIdByName('Female');
			$genderOptions = array(
				$maleGenderId => 'Male', 
				$femaleGenderId => 'Female'
			);
			//pr($genderOptions);die;
			
			$data = array();

			$headerFTE = array(__('Year'), __('Teacher Type'), __('Education Level'), __('Male'), __('Female'), __('Total'));
			$headerTraining = $headerFTE;
			$headerSingleGrade = array(__('Year'), __('Teacher Type'), __('Programme'), __('Grade'), __('Male'), __('Female'));
			$headerMultiGrade = $headerSingleGrade;

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataYears = $InstitutionSiteProgrammeModel->getYearsHaveProgrammes($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];
				
				$EducationLevel = ClassRegistry::init('EducationLevel');
				$eduLevelOptions = $EducationLevel->getInstitutionLevelsBySchoolYear($institutionSiteId, $yearId);

				// FTE teachers data start
				$CensusTeacherFteModel = ClassRegistry::init('CensusTeacherFte');
				$dataFTE = $CensusTeacherFteModel->getCensusData($institutionSiteId, $yearId);
				if (count($dataFTE) > 0) {
					$data[] = $headerFTE;
					$totalFTE = 0;
					foreach ($eduLevelOptions as $eduLevelId => $eduLevelName) {
						$maleFTE = 0;
						$femaleFTE = 0;
						
						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($dataFTE[$eduLevelId][$genderId])) {
								if ($genderName == 'Male') {
									$maleFTE = $dataFTE[$eduLevelId][$genderId]['value'];
								} else {
									$femaleFTE = $dataFTE[$eduLevelId][$genderId]['value'];
								}
							}
						}

						$data[] = array(
							$yearName,
							'Full Time Equivalent Teachers',
							$eduLevelName,
							$maleFTE,
							$femaleFTE,
							$maleFTE + $femaleFTE
						);

						$totalFTE += $maleFTE;
						$totalFTE += $femaleFTE;
					}

					$data[] = array('', '', '', '', __('Total'), $totalFTE);
					$data[] = array();
				}
				// FTE teachers data end
				// trained teachers data start
				$CensusTeacherTrainingModel = ClassRegistry::init('CensusTeacherTraining');
				$dataTraining = $CensusTeacherTrainingModel->getCensusData($institutionSiteId, $yearId);
				if (count($dataTraining) > 0) {
					$data[] = $headerTraining;
					$totalTraining = 0;
					foreach ($eduLevelOptions as $eduLevelId => $eduLevelName) {
						$maleTraining = 0;
						$femaleTraining = 0;
						
						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($dataTraining[$eduLevelId][$genderId])) {
								if ($genderName == 'Male') {
									$maleTraining = $dataTraining[$eduLevelId][$genderId]['value'];
								} else {
									$femaleTraining = $dataTraining[$eduLevelId][$genderId]['value'];
								}
							}
						}

						$data[] = array(
							$yearName,
							'Trained Teachers',
							$eduLevelName,
							$maleTraining,
							$femaleTraining,
							$maleTraining + $femaleTraining
						);

						$totalTraining += $maleTraining;
						$totalTraining += $femaleTraining;
					}

					$data[] = array('', '', '', '', __('Total'), $totalTraining);
					$data[] = array();
				}
				// trained teachers data end
				// single grade teachers data start
				$programmeGrades = $InstitutionSiteProgrammeModel->getProgrammeList($institutionSiteId, $yearId);
				$singleGradeData = $programmeGrades;
				$singleGradeTeachers = $this->getSingleGradeData($institutionSiteId, $yearId);
				$this->mergeSingleGradeData($singleGradeData, $singleGradeTeachers);

				if (count($singleGradeData) > 0) {
					$data[] = $headerSingleGrade;
					$totalMaleSingleGrade = 0;
					$totalFemaleSingleGrade = 0;
					foreach ($singleGradeData as $programmeName => $programmeData) {
						foreach ($programmeData['education_grades'] as $gradeId => $gradeData) {
							$maleSingleGrade = 0;
							$femaleSingleGrade = 0;
							
							foreach ($genderOptions AS $genderId => $genderName) {
								if(!empty($gradeData[$genderId])){
									if ($genderName == 'Male') {
										$maleSingleGrade = $gradeData[$genderId]['value'];
									} else {
										$femaleSingleGrade = $gradeData[$genderId]['value'];
									}
								}
							}

							$data[] = array(
								$yearName,
								'Single Grade Teachers Only',
								$programmeName,
								$gradeData['gradeName'],
								$maleSingleGrade,
								$femaleSingleGrade
							);

							$totalMaleSingleGrade += $maleSingleGrade;
							$totalFemaleSingleGrade += $femaleSingleGrade;
						}
					}

					$data[] = array('', '', '', __('Total'), $totalMaleSingleGrade, $totalFemaleSingleGrade);
					$data[] = array();
				}
				// single grade teachers data end
				// multi grades teachers data start
				$multiGradeData = $this->getMultiGradeData($institutionSiteId, $yearId);

				if (count($multiGradeData) > 0) {
					$data[] = $headerMultiGrade;
					$totalMaleMultiGrades = 0;
					$totalFemaleMultiGrades = 0;
					foreach ($multiGradeData as $obj){
						$maleMultiGrades = 0;
						$femaleMultiGrades = 0;
						$multiProgrammes = '';
						$multiProgrammeCount = 0;
						foreach ($obj['programmes'] AS $multiProgramme) {
							if ($multiProgrammeCount > 0) {
								$multiProgrammes .= "\n\r";
								$multiProgrammes .= $multiProgramme;
							} else {
								$multiProgrammes .= $multiProgramme;
							}
							$multiProgrammeCount++;
						}

						$multiGrades = '';
						$multiGradeCount = 0;
						foreach ($obj['grades'] AS $multiGrade) {
							if ($multiGradeCount > 0) {
								$multiGrades .= "\n\r";
								$multiGrades .= $multiGrade;
							} else {
								$multiGrades .= $multiGrade;
							}
							$multiGradeCount++;
						}
						
						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($obj['genders'][$genderId])) {
								if ($genderName == 'Male') {
									$maleMultiGrades = $obj['genders'][$genderId]['value'];
								} else {
									$femaleMultiGrades = $obj['genders'][$genderId]['value'];
								}
							}
						}

						$data[] = array(
							$yearName,
							'Multi Grade Teachers',
							$multiProgrammes,
							$multiGrades,
							$maleMultiGrades,
							$femaleMultiGrades
						);

						$totalMaleMultiGrades += $maleMultiGrades;
						$totalFemaleMultiGrades += $femaleMultiGrades;
					}

					$data[] = array('', '', '', __('Total'), $totalMaleMultiGrades, $totalFemaleMultiGrades);
					$data[] = array();
				}
				// multi grades teachers data end
			}
			//pr($data);
			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Teachers';
	}
}