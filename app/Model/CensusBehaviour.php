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

class CensusBehaviour extends AppModel {
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	public $belongsTo = array(
		'SchoolYear',
		'Students.StudentBehaviourCategory',
		'InstitutionSite',
		'Gender' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'gender_id'
		)
	);
	
	public function getCensusData($siteId, $yearId) {
		$this->StudentBehaviourCategory->formatResult = true;
		
		$list = $this->StudentBehaviourCategory->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'CensusBehaviour.id', 'CensusBehaviour.value',  'CensusBehaviour.gender_id',  
				'CensusBehaviour.source', 'StudentBehaviourCategory.name', 'StudentBehaviourCategory.id AS student_behaviour_category_id'
			),
			'joins' => array(
				array(
					'table' => 'census_behaviours',
					'alias' => 'CensusBehaviour',
					'type' => 'LEFT',
					'conditions' => array(
						'CensusBehaviour.institution_site_id = ' . $siteId,
						'CensusBehaviour.school_year_id = ' . $yearId,
						'CensusBehaviour.student_behaviour_category_id = StudentBehaviourCategory.id'
					)
				)
			),
			'conditions' => array('StudentBehaviourCategory.visible' => 1),
			'order' => array('StudentBehaviourCategory.order')
		));
		
		$data = array();
		foreach($list AS $row){
			$censusId = $row['id'];
			$behaviourCatId = $row['student_behaviour_category_id'];
			$genderId = $row['gender_id'];
			
			if(!empty($censusId) && !empty($genderId)){
				$data[$behaviourCatId][$genderId] = array(
					'censusId' => $censusId,
					'value' => $row['value'],
					'source' => $row['source']
				);
			}
		}
		
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$yearId = $data['school_year_id'];
		unset($data['school_year_id']);
		
		foreach($data as $obj) {
			$obj['school_year_id'] = $yearId;
			$obj['institution_site_id'] = $institutionSiteId;
			if($obj['id'] == 0) {
				if($obj['value'] > 0) {
					$this->create();
					$this->save(array('CensusBehaviour' => $obj));
				}
			}else{
				$this->save(array('CensusBehaviour' => $obj));
			}
			
		}
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
									'CensusBehaviour.school_year_id = SchoolYear.id'
								)
							)
					),
					'conditions' => array('CensusBehaviour.institution_site_id' => $institutionSiteId),
					'group' => array('CensusBehaviour.school_year_id'),
					'order' => array('SchoolYear.name DESC')
				)
			); 
			
			return $data;
		}
		
	public function behaviour($controller, $params) {
		$controller->Navigation->addCrumb('Behaviour');

		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$data = $this->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedYear);
		
		$behaviourCategories = $this->StudentBehaviourCategory->getCategoryList();
		//pr($staffCategories);die;

		$maleGenderId = $this->Gender->getIdByName('Male');
		$femaleGenderId = $this->Gender->getIdByName('Female');
		$genderOptions = array(
			$maleGenderId => 'Male', 
			$femaleGenderId => 'Female'
		);
		//pr($genderOptions);die;
		
		$isEditable = $controller->CensusVerification->isEditable($controller->Session->read('InstitutionSite.id'), $selectedYear);
		
		$controller->set(compact('selectedYear', 'yearList', 'data', 'isEditable', 'genderOptions', 'behaviourCategories'));
	}

	public function behaviourEdit($controller, $params) {
		if ($controller->request->is('get')) {
			$controller->Navigation->addCrumb('Edit Behaviour');

			$yearList = $this->SchoolYear->getAvailableYears();
			$selectedYear = $controller->getAvailableYearId($yearList);
			$data = $this->getCensusData($controller->Session->read('InstitutionSite.id'), $selectedYear);
			$editable = $controller->CensusVerification->isEditable($controller->Session->read('InstitutionSite.id'), $selectedYear);
			if (!$editable) {
				$controller->redirect(array('action' => 'behaviour', $selectedYear));
			} else {
				$behaviourCategories = $this->StudentBehaviourCategory->getCategoryList();
				//pr($staffCategories);die;

				$maleGenderId = $this->Gender->getIdByName('Male');
				$femaleGenderId = $this->Gender->getIdByName('Female');
				$genderOptions = array(
					$maleGenderId => 'Male', 
					$femaleGenderId => 'Female'
				);
				//pr($genderOptions);die;
				
				$controller->set(compact('selectedYear', 'yearList', 'data', 'genderOptions', 'behaviourCategories'));
			}
		} else {
			$data = $controller->data['CensusBehaviour'];
			$yearId = $data['school_year_id'];
			$this->saveCensusData($data, $controller->Session->read('InstitutionSite.id'));
			$controller->Message->alert('general.edit.success');
			$controller->redirect(array('controller' => 'Census', 'action' => 'behaviour', $yearId));
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
			
			$behaviourCategories = $this->StudentBehaviourCategory->getCategoryList();
			
			$maleGenderId = $this->Gender->getIdByName('Male');
			$femaleGenderId = $this->Gender->getIdByName('Female');
			$genderOptions = array(
				$maleGenderId => 'Male',
				$femaleGenderId => 'Female'
			);

			$header = array(__('Year'), __('Category'), __('Male'), __('Female'), __('Total'));

			$dataYears = $this->getYearsHaveData($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$dataBehaviour = $this->getCensusData($institutionSiteId, $yearId);

				if (count($dataBehaviour) > 0) {
					$data[] = $header;
					$total = 0;
					
					foreach ($behaviourCategories AS $catId => $catName){
						$maleValue = 0;
						$femaleValue = 0;
						
						foreach ($genderOptions AS $genderId => $genderName) {
							if (!empty($dataBehaviour[$catId][$genderId])) {
								if ($genderName == 'Male') {
									$maleValue = $dataBehaviour[$catId][$genderId]['value'];
								} else {
									$femaleValue = $dataBehaviour[$catId][$genderId]['value'];
								}
							}
						}
						
						if($maleValue > 0 || $femaleValue > 0){
							$rowTotal = $maleValue + $femaleValue;
							
							$data[] = array(
								$yearName,
								$catName,
								$maleValue,
								$femaleValue,
								$rowTotal
							);
							
							$total += $rowTotal;
						}
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
		return 'Report_Totals_Behaviour';
	}
}