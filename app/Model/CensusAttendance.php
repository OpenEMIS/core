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

class CensusAttendance extends AppModel {

	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	public $belongsTo = array(
		'AcademicPeriod',
		'EducationGrade',
		'InstitutionSite',
		'Gender'
	);

	public function getCensusData($siteId, $academicPeriodId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $siteId
		);
		$conditions = $InstitutionSiteProgramme->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);

		$sourceData = $InstitutionSiteProgramme->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'CensusAttendance.id', 
				'CensusAttendance.source',
				'CensusAttendance.gender_id', 
				'CensusAttendance.value',
				'CensusAttendance.education_grade_id',
				'EducationProgramme.id AS education_programme_id',
				'EducationProgramme.name AS education_programme_name',
				'EducationCycle.name AS education_cycle_name',
				'EducationGrade.id AS education_grade_id', 
				'EducationGrade.name AS education_grade_name'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id',
						'EducationGrade.visible' => 1
					)
				),
				array(
					'table' => 'census_attendances',
					'alias' => 'CensusAttendance',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusAttendance.education_grade_id = EducationGrade.id',
						'CensusAttendance.institution_site_id = ' . $siteId,
						'CensusAttendance.academic_period_id = ' . $academicPeriodId
					)
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
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));

		$programmesData = array();
		$censusData = array();
		
		foreach($sourceData AS $row){
			$programmeId = $row['EducationProgramme']['education_programme_id'];
			$programmeName = $row['EducationProgramme']['education_programme_name'];
			$cycleName = $row['EducationCycle']['education_cycle_name'];
			$gradeId =  $row['EducationGrade']['education_grade_id'];
			$gradeName =  $row['EducationGrade']['education_grade_name'];
			$genderId = $row['CensusAttendance']['gender_id'];
			
			if(!isset($programmesData[$programmeId])){
				$programmesData[$programmeId]['programmeName'] = $cycleName . ' - ' . $programmeName;
				$programmesData[$programmeId]['grades'] = array();
			}
			
			$programmesData[$programmeId]['grades'][$gradeId] = $gradeName;
			
			$censusData[$programmeId][$gradeId][$genderId] = $row['CensusAttendance'];
		}
		
		$data = array(
			'programmeData' => $programmesData,
			'censusData' => $censusData
		);
	
		return $data;
	}

	public function saveCensusData($data, $institutionSiteId) {
		$academicPeriodId = $data['academic_period_id'];
		unset($data['academic_period_id']);

		foreach ($data as $obj) {
			$obj['academic_period_id'] = $academicPeriodId;
			$obj['institution_site_id'] = $institutionSiteId;
			if ($obj['id'] == 0) {
				$this->create();
			}
			$save = $this->save(array($this->alias => $obj));
		}
	}

	public function attendance($controller, $params) {
		$controller->Navigation->addCrumb('Attendance');

		$academicPeriodList = $this->AcademicPeriod->getAcademicPeriodList();
		$selectedAcademicPeriod = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($academicPeriodList);
		$data = $this->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
		$schoolDays = $this->AcademicPeriod->field('school_days', array('AcademicPeriod.id' => $selectedAcademicPeriod));

		if (empty($data['censusData'])) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		}
		
		$genderOptions = $this->Gender->getListOnly();
		//pr($genderOptions);die;
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
		
		$controller->set(compact('genderOptions', 'data', 'selectedAcademicPeriod', 'academicPeriodList', 'schoolDays', 'isEditable'));
	}

	public function attendanceEdit($controller, $params) {
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Attendance');

			$academicPeriodList = $this->AcademicPeriod->getAcademicPeriodList();
			$selectedAcademicPeriod = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($academicPeriodList);
			$data = $this->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
			$schoolDays = $this->AcademicPeriod->field('school_days', array('AcademicPeriod.id' => $selectedAcademicPeriod));

			$editable = ClassRegistry::init('CensusVerification')->isEditable($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
			if (!$editable) {
				$controller->redirect(array('action' => 'attendance', $selectedAcademicPeriod));
			} else {
				if (empty($data['censusData'])) {
					$controller->Message->alert('InstitutionSiteProgramme.noData');
				}
				
				$genderOptions = $this->Gender->getListOnly();
				//pr($genderOptions);die;
				
				$controller->set(compact('genderOptions', 'data', 'selectedAcademicPeriod', 'academicPeriodList', 'schoolDays'));
			}
		} else {
			$data = $controller->data['CensusAttendance'];
			$academicPeriodId = $data['academic_period_id'];
			$this->saveCensusData($data, $controller->Session->read('InstitutionSite.id'));
			$controller->Message->alert('general.edit.success');
			$controller->redirect(array('controller' => 'Census', 'action' => 'attendance', $academicPeriodId));
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
			$header = array(__('Academic Period'), __('School Days'), __('Programme'), __('Grade'), __('Days Absent (Male)'), __('Days Absent (Female)'), __('Days Attended (Male)'), __('Days Attended (Female)'));

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataAcademicPeriods = $InstitutionSiteProgrammeModel->getAcademicPeriodsHaveProgrammes($institutionSiteId);
			$genderOptions = $this->Gender->getListOnly();

			foreach ($dataAcademicPeriods AS $rowAcademicPeriod) {
				$academicPeriodId = $rowAcademicPeriod['AcademicPeriod']['id'];
				$academicPeriodName = $rowAcademicPeriod['AcademicPeriod']['name'];

				$dataReturned = $this->getCensusData($institutionSiteId, $academicPeriodId);
				$programmeData = $dataReturned['programmeData'];
				$censusData = $dataReturned['censusData'];
				
				$AcademicPeriodModel = ClassRegistry::init('AcademicPeriod');
				$schoolDays = $AcademicPeriodModel->field('school_days', array('AcademicPeriod.id' => $academicPeriodId));

				if (!empty($censusData)) {
					foreach ($programmeData as $programmeId => $programmeData) {
						$data[] = $header;
						$programmeName = $programmeData['programmeName'];
						
						foreach($programmeData['grades'] as $gradeId => $gradeName){
							$maleValue = 0;
							$femaleValue = 0;
							
							foreach ($genderOptions AS $genderId => $genderName){
								if (isset($censusData[$programmeId][$gradeId][$genderId])){
									$value = $censusData[$programmeId][$gradeId][$genderId]['value'];
									
									if($genderName == 'Male'){
										$maleValue = $value;
									}else{
										$femaleValue = $value;
									}
								}
							}
							
							$maleAttended = ($schoolDays - $maleValue) >= 0 ? ($schoolDays - $maleValue) : 0;
							$femaleAttended = ($schoolDays - $femaleValue) >= 0 ? ($schoolDays - $femaleValue) : 0;
							
							$data[] = array(
								$academicPeriodName,
								$schoolDays,
								$programmeName,
								$gradeName,
								$maleValue,
								$femaleValue,
								$maleAttended,
								$femaleAttended
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
		return 'Report_Totals_Attendance';
	}

}