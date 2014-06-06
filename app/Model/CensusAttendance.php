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
		'InstitutionSite'
	);

	public function getCensusData($siteId, $yearId, $programmeId) {
		$EducationGrade = ClassRegistry::init('EducationGrade');
		$EducationGrade->formatResult = true;

		$data = $EducationGrade->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'CensusAttendance.id', 'CensusAttendance.source',
				'CensusAttendance.attended_male', 'CensusAttendance.attended_female',
				'CensusAttendance.absent_male', 'CensusAttendance.absent_female',
				'EducationGrade.id AS education_grade_id', 'EducationGrade.name AS education_grade_name'
			),
			'joins' => array(
				array(
					'table' => 'census_attendances',
					'alias' => 'CensusAttendance',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusAttendance.education_grade_id = EducationGrade.id',
						'CensusAttendance.institution_site_id = ' . $siteId,
						'CensusAttendance.school_year_id = ' . $yearId
					)
				)
			),
			'conditions' => array(
				'EducationGrade.education_programme_id' => $programmeId,
				'EducationGrade.visible' => 1
			),
			'order' => array('EducationGrade.order')
		));
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

		$yearList = $controller->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($controller->institutionSiteId, $selectedYear);
		$schoolDays = $controller->SchoolYear->field('school_days', array('SchoolYear.id' => $selectedYear));

		$data = array();
		if (empty($programmes)) {
			$controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
		} else {
			foreach ($programmes as $obj) {
				$programmeId = $obj['education_programme_id'];
				$list = $controller->CensusAttendance->getCensusData($controller->institutionSiteId, $selectedYear, $programmeId);
				$data[$programmeId] = array(
					'name' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
					'data' => $list
				);
			}
		}
		$controller->set('data', $data);
		$controller->set('selectedYear', $selectedYear);
		$controller->set('yearList', $yearList);
		$controller->set('schoolDays', $schoolDays);
		$controller->set('isEditable', $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear));
	}

	public function attendanceEdit($controller, $params) {
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Attendance');

			$yearList = $controller->SchoolYear->getYearList();
			$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
			$programmes = $controller->InstitutionSiteProgramme->getSiteProgrammes($controller->institutionSiteId, $selectedYear);
			$schoolDays = $controller->SchoolYear->field('school_days', array('SchoolYear.id' => $selectedYear));

			$data = array();
			$editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
			if (!$editable) {
				$controller->redirect(array('action' => 'attendance', $selectedYear));
			} else {
				if (empty($programmes)) {
					$controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
				} else {
					foreach ($programmes as $obj) {
						$programmeId = $obj['education_programme_id'];
						$list = $controller->CensusAttendance->getCensusData($controller->institutionSiteId, $selectedYear, $programmeId);
						$data[$programmeId] = array(
							'name' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
							'data' => $list
						);
					}
				}
				$controller->set('data', $data);
				$controller->set('selectedYear', $selectedYear);
				$controller->set('yearList', $yearList);
				$controller->set('schoolDays', $schoolDays);
			}
		} else {
			$data = $controller->data['CensusAttendance'];
			$yearId = $data['school_year_id'];
			$controller->CensusAttendance->saveCensusData($data, $controller->institutionSiteId);
			$controller->Utility->alert($controller->Utility->getMessage('CENSUS_UPDATED'));
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