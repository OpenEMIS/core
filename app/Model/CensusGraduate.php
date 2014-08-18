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

class CensusGraduate extends AppModel {
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'SchoolYear',
		'Gender' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'gender_id'
		)
	);
	
	public function getCensusData($siteId, $yearId) {
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
		$InstitutionSiteProgramme->formatResult = true;
		$list = $InstitutionSiteProgramme->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'EducationLevel.name AS education_level_name',
				'EducationCycle.name AS education_cycle_name',
				'EducationProgramme.id AS education_programme_id',
				'EducationProgramme.name AS education_programme_name',
				'EducationCertification.id AS education_certification_id',
				'EducationCertification.name AS education_certification_name',
				'InstitutionSiteProgramme.institution_site_id',
				'CensusGraduate.id AS census_id',
				'CensusGraduate.gender_id',
				'CensusGraduate.value',
				'CensusGraduate.source'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_certifications',
					'alias' => 'EducationCertification',
					'conditions' => array(
						'EducationCertification.id = EducationProgramme.education_certification_id',
						'EducationCertification.id != 1'
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
					'table' => 'census_graduates',
					'alias' => 'CensusGraduate',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusGraduate.education_programme_id = InstitutionSiteProgramme.education_programme_id',
						'CensusGraduate.institution_site_id = InstitutionSiteProgramme.institution_site_id',
						'CensusGraduate.school_year_id = InstitutionSiteProgramme.school_year_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $siteId,
				'InstitutionSiteProgramme.school_year_id' => $yearId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		
		$programmesData = array();
		$censusData = array();
		
		foreach ($list as $obj) {
			$level = $obj['education_level_name'];
			$genderId = $obj['gender_id'];
			$programmeId = $obj['education_programme_id'];
			
			if(!empty($programmeId) && !empty($genderId)){
				$censusData[$programmeId][$genderId] = array(
					'census_id' =>  $obj['census_id'],
					'value' => $obj['value'],
					'source' => $obj['source']
				);
			}
			
			$programmesData[$level][$programmeId] = array(
				'programmeName' => $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'],
				'certificationName' => $obj['education_certification_name']
			);
		}
		
		$data = array(
			'programmeData' => $programmesData,
			'censusData' => $censusData
		);
		
		// pr($data);die;
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		//pr($data);die;
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			if($obj['id'] == 0) {
				if($obj['value'] > 0) {
					$this->create();
					$this->save(array('CensusGraduate' => $obj));
				}
			} else {
				$this->save(array('CensusGraduate' => $obj));
			}
		}
	}
		
	public function graduates($controller, $params) {
		$controller->Navigation->addCrumb('Graduates');
		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammes($institutionSiteId, $selectedYear);
		
		$censusData = array();
		$programmeData = array();
		
		if (empty($programmes)) {
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		} else {
			$data = $this->getCensusData($institutionSiteId, $selectedYear);
			$censusData = $data['censusData'];
			$programmeData = $data['programmeData'];
			
			if (empty($censusData)) {
				$controller->Message->alert('CensusGraduate.notRequired');
			}
		}

		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		$genderOptions = array(
			$maleGenderId => 'Male',
			$femaleGenderId => 'Female'
		);

		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);

		$controller->set(compact('selectedYear', 'yearList', 'genderOptions', 'isEditable', 'censusData', 'programmeData'));
	}

	public function graduatesEdit($controller, $params) {
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		
		if ($controller->request->is('post')) {
			$data = $controller->data['CensusGraduate'];
			$yearId = $data['school_year_id'];
			$this->saveCensusData($data, $institutionSiteId);
			$controller->Message->alert('general.edit.success');
			$controller->redirect(array('action' => 'graduates', $yearId));
		}
		$controller->Navigation->addCrumb('Edit Graduates');
		$yearList = $this->SchoolYear->getAvailableYears();
		$selectedYear = $controller->getAvailableYearId($yearList);
		$programmes = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammes($institutionSiteId, $selectedYear);
		
		$censusData = array();
		$programmeData = array();
		
		$editable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);
		if (!$editable) {
			$controller->redirect(array('action' => 'graduates', $selectedYear));
		} else {
			if (empty($programmes)) {
				$controller->Message->alert('InstitutionSiteProgramme.noData');
			} else {
				$data = $this->getCensusData($institutionSiteId, $selectedYear);
				$censusData = $data['censusData'];
				$programmeData = $data['programmeData'];
			
				if (empty($censusData)) {
					$controller->Message->alert('CensusGraduate.notRequired');
				}
			}
		}
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		$genderOptions = array(
			$maleGenderId => 'Male',
			$femaleGenderId => 'Female'
		);
		
		$controller->set(compact('selectedYear', 'yearList', 'genderOptions', 'censusData', 'programmeData'));
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
			$reportData = array();
			
			$maleGenderId = $this->Gender->getIdByName('Male');
			$femaleGenderId = $this->Gender->getIdByName('Female');
			$genderOptions = array(
				$maleGenderId => 'Male',
				$femaleGenderId => 'Female'
			);
			
			$header = array(__('Year'), __('Education Level'), __('Education Programme'), __('Certification'), __('Male'), __('Female'), __('Total'));

			$InstitutionSiteProgrammeModel = ClassRegistry::init('InstitutionSiteProgramme');
			$dataYears = $InstitutionSiteProgrammeModel->getYearsHaveProgrammes($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$data = $this->getCensusData($institutionSiteId, $yearId);
				$censusData = $data['censusData'];
				$programmeData = $data['programmeData'];

				if (count($censusData) > 0) {
					foreach ($programmeData as $cycleName => $programmes) {
						$total = 0;
						$reportData[] = $header;
						foreach ($programmes as $programmeId => $programme){
							$maleValue = 0;
							$femaleValue = 0;
							
							foreach ($genderOptions AS $genderId => $genderName){
								if (!empty($censusData[$programmeId][$genderId])){
									if ($genderName == 'Male'):
										$maleValue = $censusData[$programmeId][$genderId]['value'];
									else:
										$femaleValue = $censusData[$programmeId][$genderId]['value'];
									endif;
								}
							}
							
							if($maleValue > 0 || $femaleValue > 0){
								$rowTotal = $maleValue + $femaleValue;
							
								$reportData[] = array(
									$yearName,
									$cycleName,
									$programme['programmeName'],
									$programme['certificationName'],
									$maleValue,
									$femaleValue,
									$rowTotal
								);
								
								$total += $rowTotal;
							}
						}
						$reportData[] = array('', '', '', '', '', __('Total'), $total);
						$reportData[] = array();
					}
				}
			}
			return $reportData;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Graduates';
	}
}
