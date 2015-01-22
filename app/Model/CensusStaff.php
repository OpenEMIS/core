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

class CensusStaff extends AppModel {
	public $actsAs = array(
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $useTable = 'census_staff';
	
	public $belongsTo = array(
		'SchoolYear',
		'Gender',
		'InstitutionSite',
		'Staff.StaffPositionTitle'
	);
	
	public function getCensusData($siteId, $yearId) {
		$this->formatResult = true;
		$list = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'StaffPositionTitle.id AS positionTitleId',
				'StaffPositionTitle.name AS positionTitleName',
				'CensusStaff.id',
				'CensusStaff.value',
				'CensusStaff.source',
				'CensusStaff.gender_id'
			),
			'joins' => array(
				array(
					'table' => 'field_option_values',
					'alias' => 'StaffPositionTitle',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusStaff.staff_position_title_id = StaffPositionTitle.id'
					)
				)
			),
			'conditions' => array(
				'CensusStaff.institution_site_id' => $siteId,
				'CensusStaff.school_year_id' => $yearId
			)
		));
		//pr($list);
		
		$data = array();
		foreach($list AS $row){
			$censusId = $row['id'];
			$positionTitleId = $row['positionTitleId'];
			$genderId = $row['gender_id'];
			
			if(!empty($positionTitleId) && !empty($genderId)){
				$data[$positionTitleId][$genderId] = array(
					'censusId' => $censusId,
					'value' => $row['value'],
					'source' => $row['source']
				);
			}
		}
		//pr($data);die;
		
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			
			if($obj['value'] > 0 && $obj['staff_position_title_id'] > 0 && $obj['gender_id'] > 0) {
				if($obj['id'] == 0) {
					$this->create();
				}
				$save = $this->save(array('CensusStaff' => $obj));
			} else if($obj['id'] > 0 && $obj['value'] == 0) {
				$this->delete($obj['id']);
			}
		}
	}
	
	//Used by Yearbook
	public function getCountByCycleId($yearId, $cycleId) {
		$this->formatResult = true;
		
		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		
		$optionsMale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusStaff.value) AS M')
		);
		
		$optionsFemale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusStaff.value) AS F')
		);
		
		$joins = array(
			array(
				'table' => 'institution_site_programmes',
				'alias' => 'InstitutionSiteProgramme',
				'conditions' => array(
					'InstitutionSiteProgramme.institution_site_id = CensusStaff.institution_site_id',
					'InstitutionSiteProgramme.school_year_id = CensusStaff.school_year_id'
				)
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array(
					'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id',
					'EducationProgramme.education_cycle_id = ' . $cycleId
				)
			)
		);
		
		$optionsMale['joins'] = $joins;
		$optionsFemale['joins'] = $joins;
		
		$optionsMale['group'] =  array('EducationProgramme.education_cycle_id');
		$optionsFemale['group'] = array('EducationProgramme.education_cycle_id');
		
		$optionsMale['conditions'] = array(
			'CensusStaff.school_year_id' => $yearId,
			'CensusStaff.gender_id' => $maleGenderId
		);
		
		$optionsFemale['conditions'] = array(
			'CensusStaff.school_year_id' => $yearId,
			'CensusStaff.gender_id' => $femaleGenderId
		);
		
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
			'fields' => array('SUM(CensusStaff.value) AS M')
		);
		
		$optionsFemale = array(
			'recursive' => -1,
			'fields' => array('SUM(CensusStaff.value) AS F')
		);
		
		$joins = array(
			array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = CensusStaff.institution_site_id')
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
			'CensusStaff.school_year_id' => $yearId,
			'CensusStaff.gender_id' => $maleGenderId
		);
		
		$optionsFemale['conditions'] = array('
			CensusStaff.school_year_id' => $yearId,
			'CensusStaff.gender_id' => $femaleGenderId
		);
		
		$dataMale = $this->find('first', $optionsMale);
		$dataFemale = $this->find('first', $optionsFemale);
		
		$data = array(
			'M' => $dataMale['M'],
			'F' => $dataFemale['F']
		);
		
		return $data;
	}
		
	public function getYearsHaveData($institutionSiteId){
		$data = $this->find('all', array(
				'recursive' => -1,
				'fields' => array(
					'SchoolYear.id',
					'SchoolYear.name'
				),
				'joins' => array(
						array(
							'table' => 'school_years',
							'alias' => 'SchoolYear',
							'conditions' => array(
								'CensusStaff.school_year_id = SchoolYear.id'
							)
						)
				),
				'conditions' => array('CensusStaff.institution_site_id' => $institutionSiteId),
				'group' => array('CensusStaff.school_year_id'),
				'order' => array('SchoolYear.name DESC')
			)
		);
		
		return $data;
	}
	
	public function index($selectedYear='') {
		$this->Navigation->addCrumb('Staff');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearList = $this->SchoolYear->getYearList();
		if(empty($selectedYear)){
			$selectedYear = key($yearList);
		}
		
		$data = $this->getCensusData($institutionSiteId, $selectedYear);
		//pr($data);
		
		$positionTitles = $this->StaffPositionTitle->getInstitutionPositionTitles($institutionSiteId);
		//pr($positionTitles);
		
		$genderOptions = $this->Gender->getList();
		//pr($genderOptions);die;
		
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);

		$this->setVar(compact('selectedYear', 'yearList', 'data', 'isEditable', 'positionTitles', 'genderOptions'));
	}

	public function edit($selectedYear='') {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		if ($this->request->is('get')) {
			$this->Navigation->addCrumb('Edit Staff');
			
			$yearList = $this->SchoolYear->getYearList();
			//$selectedYear = $this->controller->getAvailableYearId($yearList);
			if(empty($selectedYear)){
				$selectedYear = key($yearList);
			}
			$editable = ClassRegistry::init('CensusVerification')->isEditable($institutionSiteId, $selectedYear);
			if (!$editable) {
				$this->redirect(array('model' => 'CensusStaff', 'index', $selectedYear));
			} else {
				$data = $this->getCensusData($institutionSiteId, $selectedYear);
				
				$positionTitles = $this->StaffPositionTitle->getInstitutionPositionTitles($institutionSiteId);			

				$genderOptions = $this->Gender->getList();
				//pr($genderOptions);die;

				$this->setVar(compact('selectedYear', 'yearList', 'data', 'positionTitles', 'genderOptions'));
			}
		} else {
			$data = $this->request->data['CensusStaff'];
			$yearId = $data['school_year_id'];
			$this->saveCensusData($data, $institutionSiteId);
			$this->Message->alert('general.edit.success');
			$this->redirect(array('controller' => 'Census', 'action' => 'CensusStaff', 'index', $yearId));
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

			$header = array(__('Year'), __('Position'), __('Male'), __('Female'), __('Total'));

			$dataYears = $this->getYearsHaveData($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$censusData = $this->getCensusData($institutionSiteId, $yearId);

				$positionTitles = $this->StaffPositionTitle->getInstitutionPositionTitles($institutionSiteId);

				$maleGenderId = $this->Gender->getIdByName('Male');
				$femaleGenderId = $this->Gender->getIdByName('Female');
				$genderOptions = array(
					$maleGenderId => 'Male',
					$femaleGenderId => 'Female'
				);

				if (count($censusData) > 0) {
					$data[] = $header;
					$total = 0;
					foreach ($positionTitles AS $positionId => $positionName) {
						$maleValue = 0;
						$femaleValue = 0;

						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($censusData[$positionId][$genderId])) {
								
								if ($genderName == 'Male') {
									$maleValue = $censusData[$positionId][$genderId]['value'];
								} else {
									$femaleValue = $censusData[$positionId][$genderId]['value'];
								}
							}
						}

						$subTotal = $maleValue + $femaleValue;
						$total += $subTotal;

						$data[] = array(
							$yearName,
							$positionName,
							$maleValue,
							$femaleValue,
							$subTotal
						);
					}

					$data[] = array('', '', '', __('Total'), $total);
					$data[] = array();
				}
			}
			//pr($data);
			return $data;
		}
	}

	public function reportsGetFileName($args) {
		//$institutionSiteId = $args[0];
		//$index = $args[1];
		return 'Report_Totals_Staff';
	}
}