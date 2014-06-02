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
		'ControllerAction'
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
		if ($controller->request->is('post')) {
			$yearId = $controller->data['CensusFinance']['school_year_id'];
			$controller->request->data['CensusFinance']['institution_site_id'] = $controller->institutionSiteId;
			$this->save($controller->request->data['CensusFinance']);

			$controller->redirect(array('action' => 'finances', $yearId));
		}

		$yearList = $this->SchoolYear->getYearList();
		$selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
		$data = $this->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $controller->institutionSiteId, 'CensusFinance.school_year_id' => $selectedYear)));
		$newSort = array();
		foreach ($data as $k => $arrv) {
			$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
		}
		
		$data = $newSort;
		
		$natures = ClassRegistry::init('FinanceNature')->find('list', array('recursive' => 2, 'conditions' => array('FinanceNature.visible' => 1)));
		$sources = $this->FinanceSource->find('list', array('conditions' => array('FinanceSource.visible' => 1)));
		
		$isEditable = ClassRegistry::init('CensusVerification')->isEditable($controller->institutionSiteId, $selectedYear);
		
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
				$controller->request->data[$this->alias]['institution_site_id'] = $controller->institutionSiteId;
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
		if ($controller->request->is('post')) {
			$data = $controller->data['CensusFinance'];
			$yearId = $data['school_year_id'];
			unset($data['school_year_id']);
			foreach ($data as &$val) {
				$val['institution_site_id'] = $controller->institutionSiteId;
				$val['school_year_id'] = $yearId;
			}
			//pr($controller->request->data);die;
			$this->saveMany($data);

			$controller->redirect(array('action' => $this->_action, $yearId));
		}

		$yearList = $this->SchoolYear->getAvailableYears();
		$selectedYear = $controller->getAvailableYearId($yearList);
		$editable = ClassRegistry::init('CensusVerification')->isEditable($controller->institutionSiteId, $selectedYear);
		if (!$editable) {
			$controller->redirect(array('action' => 'finances', $selectedYear));
		} else {
			$data = $this->find('all', array('recursive' => 3, 'conditions' => array('CensusFinance.institution_site_id' => $controller->institutionSiteId, 'CensusFinance.school_year_id' => $selectedYear)));
			$newSort = array();
			foreach ($data as $k => $arrv) {
				$arrv['CategoryTypes'] = $this->FinanceCategory->find('list', array(
					'conditions' => array('FinanceCategory.finance_type_id' => $arrv['FinanceCategory']['FinanceType']['id'])
				));
				$newSort[$arrv['FinanceCategory']['FinanceType']['FinanceNature']['name']][$arrv['FinanceCategory']['FinanceType']['name']][] = $arrv;
			}
			
			$data = $newSort;

			$natures = ClassRegistry::init('FinanceNature')->find('list', array('recursive' => 2, 'conditions' => array('FinanceNature.visible' => 1)));
			$sources = $this->FinanceSource->find('list', array('conditions' => array('FinanceSource.visible' => 1)));

			$controller->set(compact('data', 'selectedYear', 'yearList', 'natures', 'sources'));
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
}
