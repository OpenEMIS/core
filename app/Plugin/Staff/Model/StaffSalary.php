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
	public $actsAs = array('ControllerAction2', 'DatePicker' => array('salary_date'));
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
				'rule' => array('money'),
				'required' => true,
				'message' => 'Please enter a valid Gross Salary'
			)
		),
		'net_salary' => array(
			'ruleRequired' => array(
				'rule' => array('money'),
				'required' => true,
				'message' => 'Please enter a valid Net Salary'
			)
		)
	);

	public function beforeAction() {
		parent::beforeAction();
		$this->fields['staff_id']['type'] = 'hidden';
		$this->setFieldOrder('salary_date', 1);
		$this->setFieldOrder('gross_salary', 2);
		$this->setFieldOrder('net_salary', 3);
		$this->setFieldOrder('additions', 4);
		$this->setFieldOrder('deductions', 5);
		$this->setFieldOrder('comment', 6);
		$this->fields['gross_salary']['type'] = 'string';
		$this->fields['gross_salary']['attr'] = array('data-compute-variable' => 'true', 'data-compute-operand' => 'plus', 'maxlength' => 9);
		$this->fields['net_salary']['attr'] = array('data-compute-target' => 'true', 'readonly' => true);
	}

	public function beforeSave($options=array()) {
		$totalAddition = 0;
		if (array_key_exists('StaffSalaryAddition', $this->data)) {
			foreach ($this->data['StaffSalaryAddition'] as $key => $value) {
				$totalAddition += $value['StaffSalaryAddition']['amount'];
			}
		}
		$totalDeduction = 0;
		if (array_key_exists('StaffSalaryDeduction', $this->data)) {
			foreach ($this->data['StaffSalaryDeduction'] as $key => $value) {
				$totalDeduction += $value['StaffSalaryDeduction']['amount'];
			}
		}
		if (array_key_exists($this->alias, $this->data)) {
			$this->data[$this->alias]['additions'] = $totalAddition;
			$this->data[$this->alias]['deductions'] = $totalDeduction;
		}
	}

	public function index() {
		$this->Navigation->addCrumb(__('Salary'));

		if ($this->Session->check('Staff.id')) {
			$staffId = $this->Session->read('Staff.id');
		}
		$this->recursive = -1;
		$data = $this->findAllByStaffId($staffId);
		$this->setVar(compact('header', 'data'));
	}

	public function view($staffSalaryId = null) {
		$this->Navigation->addCrumb(__('Salary'));

		parent::view($staffSalaryId);
		if ($this->exists($staffSalaryId)) {
			$data = $this->find('first',
				array(
					'recursive' => -1,
					'contain' => array('StaffSalaryAddition', 'StaffSalaryDeduction'),
					'conditions' => array($this->alias.'.id' => $staffSalaryId)
				)
			);
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias));
		}

		$SalaryAdditionType = ClassRegistry::init('SalaryAdditionType');
		$SalaryDeductionType = ClassRegistry::init('SalaryDeductionType');

		// data massage
		if (array_key_exists('StaffSalaryAddition', $data)) {
			foreach ($data['StaffSalaryAddition'] as $key => $value) {
				$data['StaffSalaryAddition'][$key]['amount'] = $value['amount'];
				$data['StaffSalaryAddition'][$key]['type_id'] = $value['salary_addition_type_id'];
			}
		}
		if (array_key_exists('StaffSalaryDeduction', $data)) {
			foreach ($data['StaffSalaryDeduction'] as $key => $value) {
				$data['StaffSalaryDeduction'][$key]['amount'] = $value['amount'];
				$data['StaffSalaryDeduction'][$key]['type_id'] = $value['salary_deduction_type_id'];
			}
		}

		$this->fields['additions'] = array(
				'type' => 'element',
				'element' => 'salaries/viewAddDeduct',
				'override' => true,
				'visible' => true,
				'data' => array(
					'title' => __('Additions'),
					'data' => (array_key_exists('StaffSalaryAddition', $data))? $data['StaffSalaryAddition']: array(),
					'options' => $SalaryAdditionType->getList(array('visibleOnly' => false, 'listOnly' => true)),
					'totalAmt' => $data['StaffSalary']['additions'],
				)	
			);

		$this->fields['deductions'] = array(
				'type' => 'element',
				'element' => 'salaries/viewAddDeduct',
				'override' => true,
				'visible' => true,
				'data' => array(
					'title' => __('Deductions'),
					'data' => (array_key_exists('StaffSalaryDeduction', $data))? $data['StaffSalaryDeduction']: array(),
					'options' => $SalaryDeductionType->getList(array('visibleOnly' => false, 'listOnly' => true)),
					'totalAmt' => $data['StaffSalary']['deductions']
				)
			);
	}

	private function _addDefaultValueForEmptyAmts($currData) {
		// cake seems unable to create new data entries with 'empty' values via saveAll and default them to MySQL table defaults... so....
		if (array_key_exists('StaffSalaryAddition', $currData)) {
			foreach ($currData['StaffSalaryAddition'] as $key => $value) {
				$currData['StaffSalaryAddition'][$key]['amount'] = (!empty($value['amount']))? $value['amount']: 0;
			}
		}
		if (array_key_exists('StaffSalaryDeduction', $currData)) {
			foreach ($currData['StaffSalaryDeduction'] as $key => $value) {
				$currData['StaffSalaryDeduction'][$key]['amount'] = (!empty($value['amount']))? $value['amount']: 0;
			}
		}
		
		return $currData;
	}

	public function add() {
		if ($this->Session->check('Staff.id')) {
			$staffId = $this->Session->read('Staff.id');
			$this->fields['staff_id']['value'] = $staffId;
		} else {
			return $this->redirect(array('action' => $this->alias));
		}
		$this->render = 'auto';
		$this->Navigation->addCrumb(__('Add Salary'));
		$this->setVar('contentHeader', __('Add Salary'));

		$SalaryAdditionType = ClassRegistry::init('SalaryAdditionType');
		$SalaryDeductionType = ClassRegistry::init('SalaryDeductionType');

		if ($this->request->is(array('post', 'put'))) {
			if (array_key_exists('submit', $this->request->data)) {
				if ($this->request->data['submit'] == 'Save') {
					$this->request->data = $this->_addDefaultValueForEmptyAmts($this->request->data);
					parent::add();
				}
				if ($this->request->data['submit'] == 'addition') {
					$newRow = array(
						'amount' => 0,
						'salary_addition_type_id' => 0
					);
					$this->request->data['StaffSalaryAddition'][] = $newRow;
				}
				if ($this->request->data['submit'] == 'deduction') {
					$newRow = array(
						'amount' => 0,
						'salary_deduction_type_id' => 0
					);
					$this->request->data['StaffSalaryDeduction'][] = $newRow;
				}
			}
		}
		$data = $this->request->data;

		$this->fields['gross_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';
		$this->fields['net_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';

		$tableHeaders = array(__('Type'), __('Amount'), '&nbsp;');
		$this->fields['additions'] = array(
				'type' => 'element',
				'element' => 'salaries/additional_info',
				'override' => true,
				'visible' => true,
				'data' => array(
					'title' => __('Additions'),
					'name' => 'addition',
					'data' => (array_key_exists('StaffSalaryAddition', $data))? $data['StaffSalaryAddition']: array(),
					'options' => $SalaryAdditionType->getList(array('visibleOnly' => false, 'listOnly' => true)),
					'tableHeaders' => $tableHeaders,
					'modelName' => 'StaffSalaryAddition',
					'foreignKeyName' => 'salary_addition_type_id',
				),
				'onkeyup' => 'jsForm.compute(this)'
			);

		$this->fields['deductions'] = array(
				'type' => 'element',
				'element' => 'salaries/additional_info',
				'override' => true,
				'visible' => true,
				'data' => array(
					'title' => __('Deductions'),
					'name' => 'deduction',
					'data' => (array_key_exists('StaffSalaryDeduction', $data))? $data['StaffSalaryDeduction']: array(),
					'options' => $SalaryDeductionType->getList(array('visibleOnly' => false, 'listOnly' => true)),
					'tableHeaders' => $tableHeaders,
					'modelName' => 'StaffSalaryDeduction',
					'foreignKeyName' => 'salary_deduction_type_id',
				),
				'onkeyup' => 'jsForm.compute(this)'
			);
	}

	public function edit($staffSalaryId = null) {
		$this->Navigation->addCrumb(__('Edit Salary'));
		$this->setVar('contentHeader', __('Edit Salary'));

		$this->render = 'auto';
		if ($this->exists($staffSalaryId)) {
			$data = $this->find('first',
				array(
					'recursive' => -1,
					'contain' => array('StaffSalaryAddition', 'StaffSalaryDeduction'),
					'conditions' => array($this->alias.'.id' => $staffSalaryId)
				)
			);
			
			if ($this->request->is(array('post', 'put'))) {
				if ($this->request->data['submit'] == 'Save') {
					$this->request->data = $this->_addDefaultValueForEmptyAmts($this->request->data);
					if ($this->saveAll($this->request->data)) {
						$this->Message->alert('general.edit.success');
						$action = array('action' => $this->alias, 'view', $staffSalaryId);
						return $this->redirect($action);
					} else {

						$this->log($this->validationErrors, 'debug');
						$this->Message->alert('general.edit.failed');
					}
				}
				if ($this->request->data['submit'] == 'addition') {
					$newRow = array(
						'amount' => 0,
						'salary_addition_type_id' => 0
					);
					$this->request->data['StaffSalaryAddition'][] = $newRow;
				}
				if ($this->request->data['submit'] == 'deduction') {
					$newRow = array(
						'amount' => 0,
						'salary_deduction_type_id' => 0
					);
					$this->request->data['StaffSalaryDeduction'][] = $newRow;
				}

				
			} else {
				$this->request->data = $data;
			}
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => $this->alias));
		}
		
		$SalaryAdditionType = ClassRegistry::init('SalaryAdditionType');
		$SalaryDeductionType = ClassRegistry::init('SalaryDeductionType');

		$this->fields['gross_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';
		$this->fields['net_salary']['attr']['onkeyup'] = 'jsForm.compute(this)';
		$tableHeaders = array(__('Type'), __('Amount'), '&nbsp;');
		$this->fields['additions'] = array(
				'type' => 'element',
				'element' => 'salaries/additional_info',
				'override' => true,
				'visible' => true,
				'data' => array(
					'title' => __('Additions'),
					'name' => 'addition',
					'data' => (array_key_exists('StaffSalaryAddition', $this->request->data))? $this->request->data['StaffSalaryAddition']: array(),
					'options' => $SalaryAdditionType->getList(array('visibleOnly' => false, 'listOnly' => true)),
					'tableHeaders' => $tableHeaders,
					'modelName' => 'StaffSalaryAddition',
					'foreignKeyName' => 'salary_addition_type_id',
					'onkeyup' => 'jsForm.compute(this)',
				)	
			);

		$this->fields['deductions'] = array(
				'type' => 'element',
				'element' => 'salaries/additional_info',
				'override' => true,
				'visible' => true,
				'data' => array(
					'title' => __('Deductions'),
					'name' => 'deduction',
					'data' => (array_key_exists('StaffSalaryDeduction', $this->request->data))? $this->request->data['StaffSalaryDeduction']: array(),
					'options' => $SalaryDeductionType->getList(array('visibleOnly' => false, 'listOnly' => true)),
					'tableHeaders' => $tableHeaders,
					'modelName' => 'StaffSalaryDeduction',
					'foreignKeyName' => 'salary_deduction_type_id',
					'onkeyup' => 'jsForm.compute(this)',
				)
			);

	}
}
