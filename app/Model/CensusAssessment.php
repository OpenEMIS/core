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

class CensusAssessment extends AppModel {
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'AcademicPeriod',
		'InstitutionSite'
	);
	
	public function getCensusData($siteId, $academicPeriodId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
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
				'CensusAssessment.id',
				'CensusAssessment.source',
				'CensusAssessment.value'
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
					'conditions' => array('EducationGrade.education_programme_id = EducationProgramme.id')
				),
				array(
					'table' => 'education_grades_subjects',
					'alias' => 'EducationGradeSubject',
					'conditions' => array('EducationGradeSubject.education_grade_id = EducationGrade.id')
				),
				array(
					'table' => 'education_subjects',
					'alias' => 'EducationSubject',
					'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
				),
				array(
					'table' => 'census_assessments',
					'alias' => 'CensusAssessment',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusAssessment.institution_site_id = InstitutionSiteProgramme.institution_site_id',
						'CensusAssessment.education_grade_subject_id = EducationGradeSubject.id',
						'CensusAssessment.academic_period_id = InstitutionSiteProgramme.academic_period_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $siteId,
				'InstitutionSiteProgramme.academic_period_id' => $academicPeriodId,
				'InstitutionSiteProgramme.status' => 1
			),
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
		
		foreach($data as $obj) {
			$obj['academic_period_id'] = $academicPeriodId;
			if($obj['id'] == 0) {
				if($obj['value'] > 0) {
					$this->create();
					$this->save(array('CensusAssessment' => $obj));
				}
			} else {
				$this->save(array('CensusAssessment' => $obj));
			}
		}
	}
		
	public function assessments($controller, $params) {
		$controller->Navigation->addCrumb('Results');

		$academicPeriodList = $controller->AcademicPeriod->getAcademicPeriodList();
		$selectedAcademicPeriod = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($academicPeriodList);

		$programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
		$data = array();
		if (empty($programmes)) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		} else {
			$data = $controller->CensusAssessment->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
			if (empty($data)) {
				$controller->Message->alert('Census.noSubjects');
			}
		}
		
		$isEditable = $controller->CensusVerification->isEditable($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
		
		$controller->set(compact('selectedAcademicPeriod', 'academicPeriodList', 'data', 'isEditable'));
	}

	public function assessmentsEdit($controller, $params) {
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Results');

			$academicPeriodList = $controller->AcademicPeriod->getAvailableAcademicPeriods();
			$selectedAcademicPeriod = $controller->getAvailableAcademicPeriodId($academicPeriodList);
			$editable = $controller->CensusVerification->isEditable($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
			if (!$editable) {
				$controller->redirect(array('action' => 'assessments', $selectedAcademicPeriod));
			} else {
				$programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
				$data = array();
				if (empty($programmes)) {
					$controller->Message->alert('InstitutionSiteProgramme.noData');
				} else {
					$data = $controller->CensusAssessment->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
					if (empty($data)) {
						$controller->Message->alert('Census.noSubjects');
					}
				}
				
				$controller->set(compact('selectedAcademicPeriod', 'academicPeriodList', 'data'));
			}
		} else {
			$data = $controller->data['CensusAssessment'];
			$academicPeriodId = $data['academic_period_id'];
			$controller->CensusAssessment->saveCensusData($data);
			$controller->Message->alert('general.edit.success');
			$controller->redirect(array('action' => 'assessments', $academicPeriodId));
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
			$header = array(__('Academic Period'), __('Programme'), __('Grade'), __('Subject'), __('Score'));

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataAcademicPeriods = $InstitutionSiteProgrammeModel->getAcademicPeriodsHaveProgrammes($institutionSiteId);

			foreach ($dataAcademicPeriods AS $rowAcademicPeriod) {
				$academicPeriodId = $rowAcademicPeriod['AcademicPeriod']['id'];
				$academicPeriodName = $rowAcademicPeriod['AcademicPeriod']['name'];

				$dataCensus = $this->getCensusData($institutionSiteId, $academicPeriodId);

				if (count($dataCensus) > 0) {
					foreach ($dataCensus AS $programmeName => $dataByProgramme) {
						$data[] = $header;
						foreach ($dataByProgramme AS $rowCensus) {
							$gradeName = $rowCensus['education_grade_name'];
							$subjectName = $rowCensus['education_subject_name'];
							$score = $rowCensus['total'];

							$data[] = array(
								$academicPeriodName,
								$programmeName,
								$gradeName,
								$subjectName,
								$score
							);
						}
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
		return 'Report_Totals_Results';
	}
}
