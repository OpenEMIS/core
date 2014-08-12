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
		'InstitutionSite'
	);
	
	public function getTeacherId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusTeacher.id'),
			'conditions' => array('CensusTeacher.institution_site_id' => $institutionSiteId, 'CensusTeacher.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			foreach($obj['education_grades'] as $gradeId => &$grade) {
				$male = 0;
				$female = 0;
				$source = 0;
				foreach($data as $value) {
					if($value['education_grade_id'] == $gradeId 
					&& $value['education_programme_id'] == $obj['education_programme_id']) {
						$male = $value['male'];
						$female = $value['female'];
						$source = $value['source'];
						break;
					}
				}
				$grade = array('name' => $grade, 'male' => $male, 'female' => $female,'source' => $source);
			}
		}
	}
	
	public function getSingleGradeData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusTeacher.id',
				'CensusTeacher.male',
				'CensusTeacher.female',
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
			'group' => array('CensusTeacher.id HAVING COUNT(CensusTeacherGrade.census_teacher_id) <= 1'),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getMultiGradeData($institutionSiteId, $yearId) {
		$list = $this->find('list' , array(
			'recursive' => -1,
			'fields' => array('CensusTeacher.id'),
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
		
		$gradeList = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusTeacher.id',
				'CensusTeacher.male',
				'CensusTeacher.female',
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
			'conditions' => array('CensusTeacher.id' => $list),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$data = array();
		foreach($gradeList as $obj) {
			$programme = $obj['EducationProgramme'];
			$grade = $obj['EducationGrade'];
			$teacher = $obj['CensusTeacher'];
			
			if(!isset($data[$teacher['id']])) {
				$data[$teacher['id']] = array(
					'male' => $teacher['male'],
					'female' => $teacher['female'],
					'source' => $teacher['source'],
					'programmes' => array(),
					'grades' => array()
				);
			}
			$data[$teacher['id']]['programmes'][] = $obj[0]['education_programme_name'];
			$data[$teacher['id']]['grades'][$grade['id']] = $grade['name'];
		}
		return $data;
	}
	
	public function clean($data, $yearId, $institutionSiteId, &$duplicate) {
		$clean = array();
		$gradeList = array();
		// get the current list of census teacher record ids from the database
		$ids = $this->getTeacherId($institutionSiteId, $yearId);		
		foreach($data as $obj) {
			// remove duplicate grades per record
			$grades = array_unique($obj['CensusTeacherGrade']);
			if(array_search($grades, $gradeList, true) === false) { // the multi grade combination must not exists
				$gradeList[] = $grades;
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
				
				if ((int)$obj['male'] < 0){ $obj['male'] =0; }
				if ((int)$obj['female'] < 0){ $obj['female'] =0; }
				
				$clean[] = array(
					'id' => $id,
					'male' => $obj['male'],
					'female' => $obj['female'],
					'institution_site_id' => $institutionSiteId,
					'school_year_id' => $yearId,
					'CensusTeacherGrade' => $grades
				);
			} else {
				if(!$duplicate) $duplicate = true;
			}
		}
		// Reset all values of male and female for the existing ids
		$this->unbindModel(array('belongsTo' => array_keys($this->belongsTo)), true);
		$this->updateAll(
			array('CensusTeacher.male' => 0, 'CensusTeacher.female' => 0),
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
		$options = array('recursive' => -1, 'fields' => array('SUM(CensusTeacher.male) AS M', 'SUM(CensusTeacher.female) AS F'));
		
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
//			$joins[] = array(
//				'table' => 'institutions',
//				'alias' => 'Institution',
//				'conditions' => array(
//					'Institution.id = InstitutionSite.institution_id',
//					'Institution.institution_provider_id = ' . $extras['providerId']
//				)
//			);
		}
		$options['joins'] = $joins;
		$options['conditions'] = array('CensusTeacher.school_year_id' => $yearId);
		$options['group'] = array('EducationProgramme.education_cycle_id');
		$data = $this->find('first', $options);
		return $data;
	}
	
	public function getCountByAreaId($yearId, $areaId) {
		$this->formatResult = true;
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('SUM(CensusTeacher.male) AS M', 'SUM(CensusTeacher.female) AS F'),
			'joins' => array(
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
			),
			'conditions' => array('CensusTeacher.school_year_id' => $yearId)
		));
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
			$fte = $controller->CensusTeacherFte->getCensusData($institutionSiteId, $selectedYear);
			$training = $controller->CensusTeacherTraining->getCensusData($institutionSiteId, $selectedYear);
			$singleGradeTeachers = $this->getSingleGradeData($institutionSiteId, $selectedYear);
			$multiGradeData = array();// $this->getMultiGradeData($institutionSiteId, $selectedYear);
			$singleGradeData = $programmeGrades;
			$this->mergeSingleGradeData($singleGradeData, $singleGradeTeachers);
			
			$controller->set(compact('fte', 'training', 'singleGradeData', 'multiGradeData'));
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
					$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $selectedYear, false);
					$fte = $controller->CensusTeacherFte->getCensusData($institutionSiteId, $selectedYear);
					$training = $controller->CensusTeacherTraining->getCensusData($institutionSiteId, $selectedYear);
					$singleGradeTeachers = $this->getSingleGradeData($institutionSiteId, $selectedYear);
					$multiGradeData = $this->getMultiGradeData($institutionSiteId, $selectedYear);
					$singleGradeData = $programmeGrades;
					$this->mergeSingleGradeData($singleGradeData, $singleGradeTeachers);
					
					$controller->set(compact('programmes', 'programmeGrades', 'fte', 'training', 'singleGradeData', 'multiGradeData'));
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
				$controller->Utility->alert($controller->Utility->getMessage('CENSUS_MULTI_DUPLICATE'), array('type' => 'warn', 'dismissOnClick' => false));
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
		
		$controller->set(compact('i', 'body', 'programmes', 'programmeGrades', 'yearId'));
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
		$programmesHtml = sprintf('<div class="table_cell_row"><select class="form-control" index="%d" url="Census/loadGradeList" onchange="CensusTeacher.loadGradeList(this)">', $index);
		foreach ($programmes as $id => $value) {
			$programmesHtml .= sprintf($option, $id, $value);
		}
		$programmesHtml .= '</select></div>';

		$gradesHtml = sprintf('<div class="table_cell_row"><select class="form-control" index="%d" name="data[CensusTeacher][%d][CensusTeacherGrade][%d]">', $index, $row, $index);
		foreach ($grades as $id => $value) {
			$gradesHtml .= sprintf($option, $id, $value);
		}
		$gradesHtml .= '</select></div>';

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

				// FTE teachers data start
				$CensusTeacherFteModel = ClassRegistry::init('CensusTeacherFte');
				$dataFTE = $CensusTeacherFteModel->getCensusData($institutionSiteId, $yearId);
				if (count($dataFTE) > 0) {
					$data[] = $headerFTE;
					$totalFTE = 0;
					foreach ($dataFTE AS $rowFTE) {
						$maleFTE = empty($rowFTE['male']) ? 0 : $rowFTE['male'];
						$femaleFTE = empty($rowFTE['female']) ? 0 : $rowFTE['female'];

						$data[] = array(
							$yearName,
							'Full Time Equivalent Teachers',
							$rowFTE['education_level_name'],
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
					foreach ($dataTraining AS $rowTraining) {
						$maleTraining = empty($rowTraining['male']) ? 0 : $rowTraining['male'];
						$femaleTraining = empty($rowTraining['female']) ? 0 : $rowTraining['female'];

						$data[] = array(
							$yearName,
							'Trained Teachers',
							$rowTraining['education_level_name'],
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
					foreach ($singleGradeData AS $programmeName => $programmeData) {
						foreach ($programmeData['education_grades'] AS $gradeId => $gradeData) {
							$maleSingleGrade = empty($gradeData['male']) ? 0 : $gradeData['male'];
							$femaleSingleGrade = empty($gradeData['female']) ? 0 : $gradeData['female'];

							$data[] = array(
								$yearName,
								'Single Grade Teachers Only',
								$programmeName,
								$gradeData['name'],
								$gradeData['male'],
								$gradeData['female']
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
				$multiGradesData = $this->getMultiGradeData($institutionSiteId, $yearId);

				if (count($multiGradesData) > 0) {
					$data[] = $headerMultiGrade;
					$totalMaleMultiGrades = 0;
					$totalFemaleMultiGrades = 0;
					foreach ($multiGradesData AS $rowMultiGrades) {
						$maleMultiGrades = empty($rowMultiGrades['male']) ? 0 : $rowMultiGrades['male'];
						$femaleMultiGrades = empty($rowMultiGrades['female']) ? 0 : $rowMultiGrades['female'];
						$multiProgrammes = '';
						$multiProgrammeCount = 0;
						foreach ($rowMultiGrades['programmes'] AS $multiProgramme) {
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
						foreach ($rowMultiGrades['grades'] AS $multiGrade) {
							if ($multiGradeCount > 0) {
								$multiGrades .= "\n\r";
								$multiGrades .= $multiGrade;
							} else {
								$multiGrades .= $multiGrade;
							}
							$multiGradeCount++;
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