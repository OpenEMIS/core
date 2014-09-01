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
		'SchoolYear',
		'EducationGrade',
		'InstitutionSite',
		'Gender' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'gender_id'
		)
	);

	public function getCensusData($siteId, $yearId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');

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
						'CensusAttendance.school_year_id = ' . $yearId
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
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $siteId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			),
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
				$data[$programmeId]['programmeName'] = $cycleName . ' - ' . $programmeName;
				$data[$programmeId]['grades'] = array();
			}
			
			$data[$programmeName][$gradeId] = $gradeName;

		}
		
		$data = array(
			'programmeData' => $programmesData,
			'censusData' => $censusData
		);
	
		return $data;
	}

	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);

		foreach ($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			if ($obj['id'] == 0) {
				$this->create();
			}
			$save = $this->save(array($this->alias => $obj));
		}
	}

	public function attendance($controller, $params) {
		$controller->Navigation->addCrumb('Attendance');

		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$data = $this->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedYear);
		$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $selectedYear));

		if (empty($data)) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		}
		
		$genderOptions = $this->Gender->getList();
		//pr($genderOptions);die;
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($controller->Session->read('InstitutionSite.id'), $selectedYear);
		
		$controller->set(compact('genderOptions', 'data', 'selectedYear', 'yearList', 'schoolDays', 'isEditable'));
	}

	public function attendanceEdit($controller, $params) {
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Attendance');

			$yearList = $this->SchoolYear->getYearList();
			$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
			$data = $this->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedYear);
			$schoolDays = $this->SchoolYear->field('school_days', array('SchoolYear.id' => $selectedYear));

			$data = array();
			$editable = ClassRegistry::init('CensusVerification')->isEditable($controller->Session->read('InstitutionSite.id'), $selectedYear);
			if (!$editable) {
				$controller->redirect(array('action' => 'attendance', $selectedYear));
			} else {
				if (empty($programmes)) {
					$controller->Message->alert('InstitutionSiteProgramme.noData');
				}
				
				$controller->set('data', $data);
				$controller->set('selectedYear', $selectedYear);
				$controller->set('yearList', $yearList);
				$controller->set('schoolDays', $schoolDays);
			}
		} else {
			$data = $controller->data['CensusAttendance'];
			$yearId = $data['school_year_id'];
			$this->saveCensusData($data, $controller->Session->read('InstitutionSite.id'));
			$controller->Message->alert('general.edit.success');
			$controller->redirect(array('controller' => 'Census', 'action' => 'attendance', $yearId));
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
			$header = array(__('Year'), __('School Days'), __('Programme'), __('Grade'), __('Days Attended (Male)'), __('Days Attended (Female)'), __('Days Absent (Male)'), __('Days Absent (Female)'), __('Total'));

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataYears = $InstitutionSiteProgrammeModel->getYearsHaveProgrammes($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$programmes = $InstitutionSiteProgrammeModel->getSiteProgrammes($institutionSiteId, $yearId);
				
				$SchoolYearModel = ClassRegistry::init('SchoolYear');
				$schoolDays = $SchoolYearModel->field('school_days', array('SchoolYear.id' => $yearId));

				if (count($programmes) > 0) {
					foreach ($programmes as $obj) {
						$data[] = $header;
						$programmeId = $obj['education_programme_id'];
						$dataCensus = $this->getCensusData($institutionSiteId, $yearId, $programmeId);
						$programmeName = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
						$total = 0;
						foreach ($dataCensus AS $rowCensus) {
							$gradeName = $rowCensus['education_grade_name'];
							$attendedMale = empty($rowCensus['attended_male']) ? 0 : $rowCensus['attended_male'];
							$attendedFemale = empty($rowCensus['attended_female']) ? 0 : $rowCensus['attended_female'];
							$absentMale = empty($rowCensus['absent_male']) ? 0 : $rowCensus['absent_male'];
							$absentFemale = empty($rowCensus['absent_female']) ? 0 : $rowCensus['absent_female'];
							$totalRow = $attendedMale + $attendedFemale + $absentMale + $absentFemale;
							$data[] = array(
								$yearName,
								$schoolDays,
								$programmeName,
								$gradeName,
								$attendedMale,
								$attendedFemale,
								$absentMale,
								$absentFemale,
								$totalRow
							);

							$total += $totalRow;
						}
						$data[] = array('', '', '', '', '', '', '', __('Total'), $total);
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