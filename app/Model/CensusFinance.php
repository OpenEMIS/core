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

class CensusFinance extends AppModel {
	public $actsAs = array(
		'ControllerAction',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
	);
	
	public $belongsTo = array(
		'FinanceSource',
		'FinanceCategory',
		'SchoolYear'
	);
	
	public $validate = array(
		'finance_category_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a category'
			)
		),
		'finance_source_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a source'
			)
		),
		'amount' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter an amount'
			),
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Please enter a numeric value'
			)
		)
	);
	
	public $_action = 'finances';
	public $_header = 'Finances';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb($this->_header);
		$controller->set('header', __($this->_header));
		$controller->set('_action', $this->_action);
	}
	
	public function finances($controller, $params) {
		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		if ($controller->request->is('post')) {
			$yearId = $controller->data['CensusFinance']['school_year_id'];
			$controller->request->data['CensusFinance']['institution_site_id'] = $institutionSiteId;
			$this->save($controller->request->data['CensusFinance']);

			$controller->redirect(array('action' => 'finances', $yearId));
		}

		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$data = $this->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId, 'CensusFinance.school_year_id' => $selectedYear)));
		$newSort = array();
		foreach ($data as $k => $arrv) {
			$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
		}
		
		$data = $newSort;
		
		$natures = ClassRegistry::init('FinanceNature')->find('list', array('recursive' => 2, 'conditions' => array('FinanceNature.visible' => 1)));
		$sources = $this->FinanceSource->find('list', array('conditions' => array('FinanceSource.visible' => 1)));
		
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($controller->Session->read('InstitutionSite.id'), $selectedYear);
		
		$controller->set(compact('data', 'selectedYear', 'yearList', 'natures', 'sources', 'isEditable'));
	}
	
	public function financesAdd($controller, $params) {
		$selectedYear = isset($params->pass[0]) ? $params->pass[0] : null;
		if(!is_null($selectedYear)) {
			$year = $this->SchoolYear->field('name', array('id' => $selectedYear));
			$natureId = 0;
			$typeId = 0;
			$categoryId = 0;
			$natureOptions = ClassRegistry::init('FinanceNature')->find('list', array('conditions' => array('visible' => 1)));
			$typeOptions = array();
			$categoryOptions = array();
			if(!empty($natureOptions)) {
				$natureId = isset($params->pass[1]) ? $params->pass[1] : key($natureOptions);
				$typeOptions = ClassRegistry::init('FinanceType')->find('list', array('conditions' => array('visible' => 1, 'finance_nature_id' => $natureId)));
				if(!empty($typeOptions)) {
					$typeId = isset($params->pass[2]) ? $params->pass[2] : key($typeOptions);
					$categoryOptions = ClassRegistry::init('FinanceCategory')->find('list', array('conditions' => array('visible' => 1, 'finance_type_id' => $typeId)));
				}
			}
			$sourceOptions = ClassRegistry::init('FinanceSource')->find('list', array('conditions' => array('visible' => 1)));
			$controller->set(compact('selectedYear', 'year', 'natureOptions', 'natureId', 'typeId', 'typeOptions', 'categoryId', 'categoryOptions', 'sourceOptions'));
			
			if($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data[$this->alias]['school_year_id'] = $selectedYear;
				$controller->request->data[$this->alias]['institution_site_id'] = $controller->Session->read('InstitutionSite.id');
				$controller->request->data[$this->alias]['source'] = 0;
				
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => $this->_action));
				}
			}
		} else {
			return $controller->redirect(array('action' => $this->_action));
		}
	}

	public function financesEdit($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : null;
		$data = $this->findById($id);
		if(!empty($data)) {
			$yearList = $this->SchoolYear->getYearList();
			$selectedYear = $data['SchoolYear']['id'];
			$year = $data['SchoolYear']['name'];
			$financeType = ClassRegistry::init('FinanceType')->findById($data['FinanceCategory']['finance_type_id']);
			$financeNature = $financeType['FinanceNature'];
			$financeCategory = $data['FinanceCategory'];
			$sourceOptions = ClassRegistry::init('FinanceSource')->find('list', array('conditions' => array('visible' => 1)));
			
			if($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => $this->_action, $selectedYear));
				}
			} else {
				$controller->Session->write('Census.finance.id', $id);
				$controller->request->data = $data;
			}
			$controller->set(compact('data', 'selectedYear', 'year', 'yearList', 'financeType', 'financeNature', 'financeCategory', 'sourceOptions'));
		} else {
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	public function financesDelete($controller, $params) {
		$selectedYear = isset($params->pass[0]) ? $params->pass[0] : null;
		if($controller->Session->check('Census.finance.id')) {
			$id = $controller->Session->read('Census.finance.id');
			if($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			return $controller->redirect(array('action' => $this->_action, $selectedYear)); 
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
						'CensusFinance.school_year_id = SchoolYear.id'
					)
				)
			),
			'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId),
			'group' => array('CensusFinance.school_year_id'),
			'order' => array('SchoolYear.name DESC')
		));
		
		return $data;
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

			$header = array(__('Year'), __('Nature'), __('Type'), __('Source'), __('Category'), __('Description'), __('Amount (PM)'));

			$dataYears = $this->getYearsHaveData($institutionSiteId);

			foreach ($dataYears AS $rowYear) {
				$yearId = $rowYear['SchoolYear']['id'];
				$yearName = $rowYear['SchoolYear']['name'];

				$dataFinances = $this->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId, 'CensusFinance.school_year_id' => $yearId)));
				$newSort = array();
				foreach ($dataFinances as $k => $arrv) {
					$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
				}

				if (count($newSort) > 0) {
					foreach ($newSort as $nature => $dataNature) {
						foreach ($dataNature as $type => $dataType) {
							$totalByType = 0;
							$data[] = $header;
							foreach ($dataType as $arrValues) {
								$financeNature = $nature;
								$financeType = $type;
								$financeSource = $arrValues['FinanceSource']['name'];
								$financeCategory = $arrValues['FinanceCategory']['name'];
								$financeDescription = $arrValues['CensusFinance']['description'];
								$financeAmount = $arrValues['CensusFinance']['amount'];

								$data[] = array(
									$yearName,
									$financeNature,
									$financeType,
									$financeSource,
									$financeCategory,
									$financeDescription,
									$financeAmount
								);

								$totalByType += $financeAmount;
							}
							$data[] = array('', '', '', '', '', __('Total'), $totalByType);
							$data[] = array();
						}
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
		return 'Report_Totals_Finances';
	}
}
