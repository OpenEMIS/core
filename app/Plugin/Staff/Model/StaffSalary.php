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

class StaffSalary extends StaffAppModel {
	public $actsAs = array('ControllerAction', 'DatePicker' => array('salary_date'));
	public $belongsTo = array(
		'Staff.Staff',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	/**
	 * hasMany associations
	 *
	 * @var array
	 */
	public $hasMany = array(
		'StaffSalaryAddition',
		'StaffSalaryDeduction'
	);
	public $validate = array(
		'salary_date' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Salary Date'
			)
		),
		'gross_salary' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Gross Salary'
			)
		),
		'net_salary' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Net Salary'
			)
		)
	);

	public function beforeAction($controller, $action) {
		$controller->set('model', $this->alias);
	}

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'EmploymentType', 'labelKey' => 'general.type'),
				array('field' => 'employment_date'),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function salaries($controller, $params) {
		$controller->Navigation->addCrumb(__('Salary'));
		$header = __('Salary');
		$this->recursive = -1;
		$data = $this->findAllByStaffId($controller->staffId);
		$controller->set(compact('header', 'data'));
	}

	public function salariesAdd($controller, $params) {
		$controller->Navigation->addCrumb(__('Add Salary'));
		$controller->set('header', __('Add Salary'));
		$this->setup_add_edit_form($controller, $params);
	}

	function setup_add_edit_form($controller, $params) {
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		if ($controller->request->is(array('post', 'put'))) {
			$controller->request->data['StaffSalary']['staff_id'] = $controller->staffId;
			
			$deletedAdditionId = array();
			$deletedDeductionId = array();
			
			//Calculate total addition amount
			$totalAdditionAmount = 0;
			if (!empty($controller->request->data['StaffSalaryAddition'])) {
				foreach ($controller->request->data['StaffSalaryAddition'] as $key => $additionAmount) {
					$totalAdditionAmount += $additionAmount['addition_amount'];
					
					if(empty($additionAmount['addition_amount'])){
						unset($controller->request->data['StaffSalaryAddition'][$key] );
						continue;
					}
					
					if(!empty($additionAmount['id'])){
						$deletedAdditionId[] =$additionAmount['id'];
					}
					
				}
			}
			$controller->request->data['StaffSalary']['additions'] = empty($totalAdditionAmount)? NULL : $totalAdditionAmount;

			//Calculate total deduction amount
			$totalDeductionAmount = 0;
			if (!empty($controller->request->data['StaffSalaryDeduction'])) {
				foreach ($controller->request->data['StaffSalaryDeduction'] as $key => $deductionAmount) {
					$totalDeductionAmount += $deductionAmount['deduction_amount'];
					if(empty($deductionAmount['deduction_amount'])){
						unset($controller->request->data['StaffSalaryDeduction'][$key] );	
						continue;
					}
					
					if(!empty($deductionAmount['id'])){
						$deletedDeductionId[] = $deductionAmount['id'];
					}
				}
			}
			$controller->request->data['StaffSalary']['deductions'] = empty($totalDeductionAmount)? NULL : $totalDeductionAmount;
			
			//Check which item has been remove from the view
			$currentAdditionList = $this->StaffSalaryAddition->find('list', array('conditions' => array('StaffSalaryAddition.staff_salary_id' => $id)));
			$currentDeductionList = $this->StaffSalaryDeduction->find('list', array('conditions' => array('StaffSalaryDeduction.staff_salary_id' => $id)));
			
			foreach($currentAdditionList as $itemId){
				if(!in_array($itemId, $deletedAdditionId )){
					$this->StaffSalaryAddition->delete($itemId);
				}
			}
			
			foreach($currentDeductionList as $itemId){
				if(!in_array($itemId, $deletedDeductionId )){
					$this->StaffSalaryDeduction->delete($itemId);
				}
			}
			
			if ($this->saveAll($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'salaries'));
			} else {
				
			}
		} else {
			//$this->recursive = -1;
			$this->unbindModel(array('belongsTo' => array('Staff', 'ModifiedUser', 'CreatedUser')));
			$data = $this->findById($id);
			if (!empty($data)) {
				$controller->request->data = $data;
			}
		}

		$visible = true;
		$SalaryAdditionType = ClassRegistry::init('SalaryAdditionType');
		$SalaryDeductionType = ClassRegistry::init('SalaryDeductionType');
		$additionOptions = $SalaryAdditionType->getList();
		$deductionOptions = $SalaryDeductionType->getList();

		$controller->set(compact('additionOptions', 'deductionOptions'));
	}

	public function salariesView($controller, $params) {
		$controller->Navigation->addCrumb('Salary Details');
		$header = __('Salary Details');
		$id = $params['pass'][0];
		$salaryObj = $this->findById($id); //('all', array('conditions' => array('StaffSalary.id' => $salaryId)));
		if (empty($salaryObj)) {
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action' => 'employments'));
		}

		$SalaryAdditionType = ClassRegistry::init('SalaryAdditionType');
		$SalaryDeductionType = ClassRegistry::init('SalaryDeductionType');
		$visible = true;
		$additionOptions = $SalaryAdditionType->getList();
		$deductionOptions = $SalaryDeductionType->getList();

		$controller->Session->write('StaffSalaryId', $id);
		$controller->set(compact('header', 'salaryObj', 'id', 'additionOptions', 'deductionOptions'));
	}

	public function salariesEdit($controller, $params) {
		$controller->Navigation->addCrumb(__('Edit Salary'));
		$controller->set('header', __('Edit Salary'));
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}

	public function salariesAjaxAdditionAdd($controller, $params) {
		$index = $controller->request->data['index'];

		$SalaryAdditionType = ClassRegistry::init('SalaryAdditionType');
		$categories = $SalaryAdditionType->getList();

		$controller->set(compact('categories', 'index'));
	}

	public function salariesAjaxDeductionAdd($controller, $params) {
		$index = $controller->request->data['index'];
		
		$SalaryDeductionType = ClassRegistry::init('SalaryDeductionType');
		$categories = $SalaryDeductionType->getList();
		
		$controller->set(compact('categories', 'index'));
	}

	public function salariesDelete($controller, $params) {
		if ($controller->Session->check('StaffId') && $controller->Session->check('StaffSalaryId')) {
			$id = $controller->Session->read('StaffSalaryId');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('StaffSalaryId');
			return $controller->redirect(array('action' => 'salaries'));
		}
	}

}
