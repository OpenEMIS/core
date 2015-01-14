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
		'AcademicPeriod'
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
			$academicPeriodId = $controller->data['CensusFinance']['academic_period_id'];
			$controller->request->data['CensusFinance']['institution_site_id'] = $institutionSiteId;
			$this->save($controller->request->data['CensusFinance']);

			$controller->redirect(array('action' => 'finances', $academicPeriodId));
		}

		$academicPeriodList = $this->AcademicPeriod->getAcademicPeriodList();
		$selectedAcademicPeriod = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($academicPeriodList);
		$data = $this->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId, 'CensusFinance.academic_period_id' => $selectedAcademicPeriod)));
		$newSort = array();
		foreach ($data as $k => $arrv) {
			$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
		}
		
		$data = $newSort;
		
		$natures = ClassRegistry::init('FinanceNature')->find('list', array('recursive' => 2, 'conditions' => array('FinanceNature.visible' => 1)));
		$sources = $this->FinanceSource->find('list', array('conditions' => array('FinanceSource.visible' => 1)));
		
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($controller->Session->read('InstitutionSite.id'), $selectedAcademicPeriod);
		
		$controller->set(compact('data', 'selectedAcademicPeriod', 'academicPeriodList', 'natures', 'sources', 'isEditable'));
	}
	
	public function financesAdd($controller, $params) {
		$selectedAcademicPeriod = isset($params->pass[0]) ? $params->pass[0] : null;
		if(!is_null($selectedAcademicPeriod)) {
			$academicPeriod = $this->AcademicPeriod->field('name', array('id' => $selectedAcademicPeriod));
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
			$controller->set(compact('selectedAcademicPeriod', 'academicPeriod', 'natureOptions', 'natureId', 'typeId', 'typeOptions', 'categoryId', 'categoryOptions', 'sourceOptions'));
			
			if($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data[$this->alias]['academic_period_id'] = $selectedAcademicPeriod;
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
			$academicPeriodList = $this->AcademicPeriod->getAcademicPeriodList();
			$selectedAcademicPeriod = $data['AcademicPeriod']['id'];
			$academicPeriod = $data['AcademicPeriod']['name'];
			$financeType = ClassRegistry::init('FinanceType')->findById($data['FinanceCategory']['finance_type_id']);
			$financeNature = $financeType['FinanceNature'];
			$financeCategory = $data['FinanceCategory'];
			$sourceOptions = ClassRegistry::init('FinanceSource')->find('list', array('conditions' => array('visible' => 1)));
			
			if($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => $this->_action, $selectedAcademicPeriod));
				}
			} else {
				$controller->Session->write('Census.finance.id', $id);
				$controller->request->data = $data;
			}
			$controller->set(compact('data', 'selectedAcademicPeriod', 'academicPeriod', 'academicPeriodList', 'financeType', 'financeNature', 'financeCategory', 'sourceOptions'));
		} else {
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	public function financesDelete($controller, $params) {
		$selectedAcademicPeriod = isset($params->pass[0]) ? $params->pass[0] : null;
		if($controller->Session->check('Census.finance.id')) {
			$id = $controller->Session->read('Census.finance.id');
			if($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			return $controller->redirect(array('action' => $this->_action, $selectedAcademicPeriod)); 
		}
	}
	
	public function getAcademicPeriodsHaveData($institutionSiteId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'AcademicPeriod.id',
				'AcademicPeriod.name'
			),
			'joins' => array(
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => array(
						'CensusFinance.academic_period_id = AcademicPeriod.id'
					)
				)
			),
			'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId),
			'group' => array('CensusFinance.academic_period_id'),
			'order' => array('AcademicPeriod.name DESC')
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

			$header = array(__('Academic Period'), __('Nature'), __('Type'), __('Source'), __('Category'), __('Description'), __('Amount (PM)'));

			$dataAcademicPeriods = $this->getAcademicPeriodsHaveData($institutionSiteId);

			foreach ($dataAcademicPeriods AS $rowAcademicPeriod) {
				$academicPeriodId = $rowAcademicPeriod['AcademicPeriod']['id'];
				$academicPeriodName = $rowAcademicPeriod['AcademicPeriod']['name'];

				$dataFinances = $this->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $institutionSiteId, 'CensusFinance.academic_period_id' => $academicPeriodId)));
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
									$academicPeriodName,
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
