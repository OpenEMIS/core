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

App::uses('AppController', 'Controller'); 

class FinanceController extends AppController {
	public $uses = Array(
		'Area',
		'AreaLevel',
		'PublicExpenditure',
		'PublicExpenditureEducationLevel'
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Finance', array('controller' => 'Finance', 'action' => 'index'));
	}
	
	public function index() {
		$this->Navigation->addCrumb('Total Public Expenditure');
		
		$currentYear = intval(date('Y'));
		$selectedYear = isset($this->params->pass[0])? intval($this->params->pass[0]) : $currentYear;
		
		$yearList = $this->DateTime->generateYear();
		krsort($yearList);
		
		$areaId = isset($this->params->pass[1])? intval($this->params->pass[1]) : 0;
		$parentAreaId = $areaId;
		
		$data = $this->PublicExpenditure->getPublicExpenditureData($selectedYear, $parentAreaId, $areaId);
		//pr($data);
		
		$gnpData = $this->PublicExpenditure->find('first', array(
			'conditions' => array('year' => $selectedYear),
			'fields' => array('gross_national_product'),
			'order' => array('gross_national_product DESC')
		));
		
		$gnp = isset($gnpData['PublicExpenditure']['gross_national_product']) ? $gnpData['PublicExpenditure']['gross_national_product'] : '';
		
		$this->set(compact('areaId', 'data', 'selectedYear', 'yearList', 'gnp'));
	}

	public function edit($id = null) {
		$this->bodyTitle = 'National Denominators';

		$currentYear = intval(date('Y'));
		$selectedYear = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : $currentYear;

		$yearList = $this->DateTime->generateYear();
		krsort($yearList);

		$selectedAreaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;

		$gnpData = $this->PublicExpenditure->find('first', array(
			'conditions' => array('year' => $selectedYear),
			'fields' => array('gross_national_product'),
			'order' => array('gross_national_product DESC')
		));

		$gnp = isset($gnpData['PublicExpenditure']['gross_national_product']) ? $gnpData['PublicExpenditure']['gross_national_product'] : '';

		$parentAreaId = $selectedAreaId;
		$data = $this->PublicExpenditure->getPublicExpenditureData($selectedYear, $parentAreaId, $selectedAreaId);

		//pr($data);die;
		if ($this->request->is('post')) {
			if (!empty($this->request->data['PublicExpenditure'])) {
				$expenditureData = $this->request->data['PublicExpenditure'];
				$financeData = $this->request->data['Finance'];
				$inputYear = $financeData['year'];

				$gnpInput = intval($financeData['gnp']);
				//pr($this->request->data);die;

				if (!empty($gnpInput)) {
					foreach ($expenditureData AS $group) {
						foreach ($group AS $row) {
							$row['year'] = $inputYear;
							$row['gross_national_product'] = $gnpInput;

							$id = intval($row['id']);
							$areaId = intval($row['area_id']);

							if (!empty($areaId) && (!empty($row['total_public_expenditure']) || !empty($row['total_public_expenditure_education']))) {
								$existingRecords = $this->PublicExpenditure->getRecordsCount($inputYear, $areaId);

								if (empty($id)) {
									if ($existingRecords == 0) {
										$this->PublicExpenditure->create();

										$this->PublicExpenditure->save(array('PublicExpenditure' => $row));
									}
								} else {
									if ($existingRecords == 1) {
										$this->PublicExpenditure->save(array('PublicExpenditure' => $row));
									}
								}
							}
						}
					}

					return $this->redirect(array('action' => 'index', $selectedYear, $selectedAreaId));
				} else {
					$this->Message->alert('NationalDenominators.finance.gnpEmpty');
					if (!empty($this->request->data['PublicExpenditure'])) {
						$expenditure = $this->request->data['PublicExpenditure'];
						$data['parent'][0] = array_merge($data['parent'][0], $expenditure['parent'][0]);
						foreach ($data['children'] as $i => $obj) {
							$data['children'][$i] = array_merge($data['children'][$i], $expenditure['children'][$i]);
						}
					}

					$gnp = '';
				}
			}
		}

		$this->set(compact('selectedAreaId', 'data', 'selectedYear', 'yearList', 'gnp'));
	}

	public function viewGNP($year = null, $countryId = 0) {
		$this->autoRender = false;
		$year = (is_null($year))? intval(date('Y')): $year ;

		$gnpData = $this->PublicExpenditure->find('first', array(
				'conditions' => array('year' => $year),
        		'fields' => array('gross_national_product'),
        		'order' => array('gross_national_product DESC')
			));
		$gnpData['PublicExpenditure']['currency'] = $this->Session->read('configItem.currency');

		if (!$gnpData || $countryId == 0) {
			$gnpData['PublicExpenditure']['gross_national_product'] = null;
		}
		echo json_encode($gnpData['PublicExpenditure']);
	}
	
	public function loadData() {
		$this->layout = false;
		
		$year = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : date('Y');
		$areaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		$parentAreaId = $areaId;
		
        $data = $this->PublicExpenditure->getPublicExpenditureData($year, $parentAreaId, $areaId);
		$currency = "({$this->Session->read('configItem.currency')})";
		
        $this->set(compact('data', 'currency'));
    }
	
	public function loadForm() {
		$this->layout = false;
		
		$year = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : date('Y');
		$areaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		$parentAreaId = $areaId;
		
        $data = $this->PublicExpenditure->getPublicExpenditureData($year, $parentAreaId, $areaId);
		$currency = "({$this->Session->read('configItem.currency')})";
		
        $this->set(compact('data', 'currency'));
    }

	public function financePerEducationLevel() {
		$this->Navigation->addCrumb('Total Public Expenditure Per Education Level');
		
		$currentYear = intval(date('Y'));
		$selectedYear = isset($this->params->pass[0])? intval($this->params->pass[0]) : $currentYear;
		
		$yearList = $this->DateTime->generateYear();
		krsort($yearList);
		
		$areaId = isset($this->params->pass[1])? intval($this->params->pass[1]) : 0;
		$parentAreaId = $areaId;
		
		$educationLevels = $this->PublicExpenditureEducationLevel->getEducationLevelOptions();
		$selectedEduLevel = isset($this->params->pass[2]) ? intval($this->params->pass[2]) : key($educationLevels);
		
		$data = $this->PublicExpenditureEducationLevel->getPublicExpenditureData($selectedYear, $parentAreaId, $areaId, $selectedEduLevel);
		//pr($data);
		
		$this->set(compact('areaId', 'data', 'selectedYear', 'yearList', 'educationLevels', 'selectedEduLevel'));
	}

	public function financePerEducationLevelEdit() {
		$this->Navigation->addCrumb('Total Public Expenditure Per Education Level');

		$currentYear = intval(date('Y'));
		$selectedYear = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : $currentYear;

		$yearList = $this->DateTime->generateYear();
		krsort($yearList);

		$selectedAreaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		$parentAreaId = $selectedAreaId;
		
		$educationLevels = $this->PublicExpenditureEducationLevel->getEducationLevelOptions();
		$selectedEduLevel = isset($this->params->pass[2]) ? intval($this->params->pass[2]) : key($educationLevels);

		$data = $this->PublicExpenditureEducationLevel->getPublicExpenditureData($selectedYear, $parentAreaId, $selectedAreaId, $selectedEduLevel);
		//pr($data);

		if ($this->request->is('post')) {
			//pr($this->request->data);die;
			if (!empty($this->request->data['PublicExpenditureEducationLevel'])) {
				$expenditureData = $this->request->data['PublicExpenditureEducationLevel'];
				$financeData = $this->request->data['Finance'];
				$inputYear = $financeData['year'];
				$eduLevelId = $financeData['education_level'];

				foreach ($expenditureData AS $row) {
					$row['year'] = $inputYear;
					$row['education_level_id'] = $eduLevelId;

					$id = intval($row['id']);
					$value = intval($row['value']);
					$areaId = intval($row['area_id']);
					$educationLevelId = intval($row['education_level_id']);

					if (!empty($value) && !empty($areaId) && !empty($educationLevelId)) {
						$existingRecords = $this->PublicExpenditureEducationLevel->getRecordsCount($inputYear, $areaId, $educationLevelId);

						if (empty($id)) {
							if ($existingRecords == 0) {
								$this->PublicExpenditureEducationLevel->create();

								$this->PublicExpenditureEducationLevel->save(array('PublicExpenditureEducationLevel' => $row));
							}
						} else {
							if ($existingRecords == 1) {
								$this->PublicExpenditureEducationLevel->save(array('PublicExpenditureEducationLevel' => $row));
							}
						}
					}
				}

				return $this->redirect(array('action' => 'financePerEducationLevel', $selectedYear, $selectedAreaId, $selectedEduLevel));
			}
		}

		$this->set(compact('selectedAreaId', 'data', 'selectedYear', 'yearList', 'educationLevels', 'selectedEduLevel'));
	}
	
	public function loadPerEducationData() {
		$this->layout = false;
		
		$year = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : date('Y');
		$areaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		$parentAreaId = $areaId;
		$eduLevelId = isset($this->params->pass[2]) ? intval($this->params->pass[2]) : 0;
		
        $data = $this->PublicExpenditureEducationLevel->getPublicExpenditureData($year, $parentAreaId, $areaId, $eduLevelId);
		$currency = "({$this->Session->read('configItem.currency')})";
		
        $this->set(compact('data', 'currency'));
    }
	
	public function loadPerEducationForm() {
		$this->layout = false;
		
		$year = isset($this->params->pass[0]) ? intval($this->params->pass[0]) : date('Y');
		$areaId = isset($this->params->pass[1]) ? intval($this->params->pass[1]) : 0;
		$parentAreaId = $areaId;
		$eduLevelId = isset($this->params->pass[2]) ? intval($this->params->pass[2]) : 0;
		
        $data = $this->PublicExpenditureEducationLevel->getPublicExpenditureData($year, $parentAreaId, $areaId, $eduLevelId);
		$currency = "({$this->Session->read('configItem.currency')})";
		
        $this->set(compact('data', 'currency'));
    }
}
