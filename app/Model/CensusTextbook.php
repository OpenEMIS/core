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

class CensusTextbook extends AppModel {
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'AcademicPeriod',
		'EducationGradeSubject'
	);
	
	public function getCensusData($siteId, $academicPeriodId, $programmeId, $gradeId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $siteId,
			'InstitutionSiteProgramme.id' => $programmeId
		);
		$conditions = $InstitutionSiteProgramme->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);

		$InstitutionSiteProgramme->formatResult = true;
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgramme.institution_site_id',
				'EducationCycle.name AS education_cycle_name',
				'EducationProgramme.name AS education_programme_name',
				'EducationGrade.id AS education_grade_id',
				'EducationGrade.name AS education_grade_name',
				'EducationSubject.id AS education_subject_id',
				'EducationSubject.name AS education_subject_name',
				'EducationGradeSubject.id AS education_grade_subject_id',			
				'CensusTextbook.id',
				'CensusTextbook.source',
				'CensusTextbook.value'
			),
			'joins' => array(
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
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationGrade.id' => $gradeId
					)
				),
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array(
						'EducationGradeSubject.education_grade_id = EducationGrade.id',
						'EducationGradeSubject.visible' => 1
					)
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				),
				array(
					'table' => 'census_textbooks',
					'alias' => 'CensusTextbook',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusTextbook.institution_site_id = InstitutionSiteProgramme.institution_site_id',
						'CensusTextbook.education_grade_subject_id = EducationGradeSubject.id',
						'CensusTextbook.academic_period_id' => $academicPeriodId
					)
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order', 'EducationSubject.order')
		));
		
		$data = array();
		foreach($list as $obj) {
			$name = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
			if(!isset($name)) {
				$data[$name] = array();
			}
			
			$data[$name][] = array(
				'id' => $obj['id'],
				'education_grade_id' => $obj['education_grade_id'],
				'education_grade_name' => $obj['education_grade_name'],
				'education_subject_id' => $obj['education_subject_id'],
				'education_subject_name' => $obj['education_subject_name'],
				'institution_site_id' => $obj['institution_site_id'],
				'education_grade_subject_id' => $obj['education_grade_subject_id'],
				'source' => $obj['source'],
				'total' => is_null($obj['value']) ? 0 : $obj['value']
			);
		}
		
		return $data;
	}
	
	public function saveCensusData($data) {
		$academicPeriodId = $data['academic_period_id'];
		unset($data['academic_period_id']);
		unset($data['education_programme_id']);
		unset($data['education_grade_id']);
		
		foreach($data as $obj) {
			$obj['academic_period_id'] = $academicPeriodId;
			if($obj['id'] == 0) {
				if($obj['value'] > 0) {
					$this->create();
					$this->save(array('CensusTextbook' => $obj));
				}
			} else {
				$this->save(array('CensusTextbook' => $obj));
			}
		}
	}
		
	public function textbooks($controller, $params) {
		$controller->Navigation->addCrumb('Textbooks');

		$academicPeriodList = $this->AcademicPeriod->getAcademicPeriodList();
		$selectedAcademicPeriod = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($academicPeriodList);
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedAcademicPeriod);
		$programmeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeForSelection($institutionSiteId, $selectedAcademicPeriod);
		$selectedProgramme = isset($controller->params['pass'][1]) ? $controller->params['pass'][1] : key($programmeOptions);

		$gradeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeGrades($institutionSiteId, $selectedAcademicPeriod, $selectedProgramme);
		$selectedGrade = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($gradeOptions);

		$data = array();
		if (empty($programmes)) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		} else {
			$data = $this->getCensusData($institutionSiteId, $selectedAcademicPeriod, $selectedProgramme, $selectedGrade);
			if (empty($data)) {
				$controller->Message->alert('Census.noSubjects');
			}
		}
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedAcademicPeriod);
		
		$controller->set(compact('selectedAcademicPeriod', 'academicPeriodList', 'selectedProgramme', 'programmeOptions', 'selectedGrade', 'gradeOptions', 'data', 'isEditable'));
	}

	public function textbooksEdit($controller, $params) {
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Textbooks');

			$academicPeriodList = $this->AcademicPeriod->getAvailableAcademicPeriods(true, 'DESC');
			$selectedAcademicPeriod = $controller->getAvailableAcademicPeriodId($academicPeriodList);
			$editable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedAcademicPeriod);

			if (!$editable) {
				$controller->redirect(array('action' => 'textbooks', $selectedAcademicPeriod));
			} else {
				$programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedAcademicPeriod);
				$programmeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeForSelection($institutionSiteId, $selectedAcademicPeriod);
				$selectedProgramme = $controller->getAvailableprogrammeId($programmeOptions);
				$gradeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeGrades($institutionSiteId, $selectedAcademicPeriod, $selectedProgramme);
				$selectedGrade = $controller->getAvailableGradeId($gradeOptions);

				$data = array();
				if (empty($programmes)) {
					$controller->Message->alert('InstitutionSiteProgramme.noData');
				} else {
					$data = $this->getCensusData($institutionSiteId, $selectedAcademicPeriod, $selectedProgramme, $selectedGrade);
					if (empty($data)) {
						$controller->Message->alert('Census.noSubjects');
					}
				}
				
				$controller->set(compact('selectedAcademicPeriod', 'academicPeriodList', 'selectedProgramme', 'programmeOptions', 'selectedGrade', 'gradeOptions', 'data'));
			}
		} else {
			$data = $controller->data['CensusTextbook'];
			$academicPeriodId = $data['academic_period_id'];
			$programmeId = $data['education_programme_id'];
			$gradeId = $data['education_grade_id'];
			$this->saveCensusData($data);
			$controller->Message->alert('general.edit.success');
			$controller->redirect(array('action' => 'textbooks', $academicPeriodId, $programmeId, $gradeId));
		}
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
			$header = array(__('Academic Period'), __('Programme'), __('Grade'), __('Subject'), __('Total'));

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataAcademicPeriod = $InstitutionSiteProgrammeModel->getAcademicPeriodsHaveProgrammes($institutionSiteId);

			foreach ($dataAcademicPeriod AS $rowAcademicPeriod) {
				$academicPeriodId = $rowAcademicPeriod['AcademicPeriod']['id'];
				$academicPeriodName = $rowAcademicPeriod['AcademicPeriod']['name'];

				$dataCensus = $this->getCensusData($institutionSiteId, $academicPeriodId);

				if (count($dataCensus) > 0) {
					foreach ($dataCensus AS $programmeName => $dataByProgramme) {
						$data[] = $header;
						$totalByProgramme = 0;
						foreach ($dataByProgramme AS $rowCensus) {
							$gradeName = $rowCensus['education_grade_name'];
							$subjectName = $rowCensus['education_subject_name'];
							$total = $rowCensus['total'];

							$data[] = array(
								$academicPeriodName,
								$programmeName,
								$gradeName,
								$subjectName,
								$total
							);

							$totalByProgramme += $total;
						}
						$data[] = array('', '', '', __('Total'), $totalByProgramme);
						$data[] = array();
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
		return 'Report_Totals_Textbooks';
	}
}
