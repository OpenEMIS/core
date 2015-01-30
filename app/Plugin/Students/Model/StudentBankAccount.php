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

class StudentBankAccount extends AppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction'
	);

	public $belongsTo = array(
		'BankBranch',
		'Students.Student',
		'ModifiedUser' => array('foreignKey' => 'modified_user_id', 'className' => 'SecurityUser'),
		'CreatedUser' => array('foreignKey' => 'created_user_id', 'className' => 'SecurityUser'),
	);

	public $validate = array(
		'account_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter an Account name'
			)
		),
		'account_number' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter an Account number'
			)
		),
		'bank_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Bank'
			)
		),
		'bank_branch_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Bank Branch'
			)
		)
	);

	/* Excel Behaviour */
	public function excelGetFieldLookup() {
		$alias = $this->alias;
		$lookup = array(
			"$alias.active" => array(0 => 'No', 1 => 'Yes')
		);
		return $lookup;
	}
	/* End Excel Behaviour */

	public function getDisplayFields($controller) {
		$Bank = ClassRegistry::init('Bank');
		$bankOptions = $Bank->findList();
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'bank_id', 'model' => 'BankBranch', 'type' => 'select', 'options' => $bankOptions),
				array('field' => 'name', 'model' => 'BankBranch'),
				array('field' => 'account_name'),
				array('field' => 'account_number'),
				array('field' => 'active', 'type' => 'select', 'options' => $controller->Option->get('yesno')),
				array('field' => 'remarks', 'type' => 'textarea'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function beforeAction($controller, $params) {
		parent::beforeAction($controller, $params);
		if (!$controller->Session->check('Student.id')) {
			return $controller->redirect(array('action' => 'index'));
		}
	}

	public function bankAccounts($controller, $params) {
		$controller->Navigation->addCrumb('Bank Accounts');
		$header = __('Bank Accounts');
		$this->recursive = 2;
		$this->unbindModel(array('belongsTo' => array('Student', 'ModifiedUser', 'CreatedUser')));
		$this->contain(array('BankBranch' => array(
			'Bank' => array(
				'name'
			),
			'fields' => array( 'name')
		)));
		$data = $this->findAllByStudentId($controller->Session->read('Student.id'));
	   
		$controller->set(compact('data', 'header'));
	}

	public function bankAccountsView($controller, $params) {
		$bankAccountId = $params['pass'][0];
		$data = $this->findById($bankAccountId);
		
		$header = __('Bank Accounts');
		$controller->Navigation->addCrumb('Bank Account Details');

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'bankAccounts'));
		}

		$controller->Session->write('StudentBankAccount.id', $bankAccountId);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields'));
	}

	public function bankAccountsAdd($controller, $params) {
		$header = __('Add Bank Accounts');
		$controller->Navigation->addCrumb(__('Add Bank Accounts'));
		if ($controller->request->is(array('post', 'put'))) {
			$controller->request->data['StudentBankAccount']['student_id'] = $controller->Session->read('Student.id');
			$this->create();
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'bankAccounts'));
			}
			else{
				$bankId = $controller->request->data[$this->alias]['bank_id'];
			}
		}
		$Bank = ClassRegistry::init('Bank');

		$bankOptions = $Bank->find('list', array('conditions' => Array('Bank.visible' => 1)));

		$bankId = empty($bankId)?key($bankOptions): $bankId;
		$bankId = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : $bankId;

		if (!empty($bankId)) {
			$bankBranchesOptions = $this->BankBranch->find('list', array('conditions' => array('bank_id' => $bankId, 'visible' => 1), 'recursive' => -1));
		} else {
			$bankBranchesOptions = array();
		}

		$studentId = $controller->Session->read('Student.id');
		$yesnoOptions = $controller->Option->get('yesno');
		$controller->set(compact('bankId', 'studentId', 'bankOptions', 'bankBranchesOptions', 'yesnoOptions', 'header'));
	}

	public function bankAccountsEdit($controller, $params) {
		$header = __('Bank Account Details');
		$controller->Navigation->addCrumb('Edit Bank Account Details');
		$yesnoOptions = $controller->Option->get('yesno');

		$bankAccountId = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : 0;
		$data = $this->findById($bankAccountId);

		if (!empty($data)) {
			if ($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data['StudentBankAccount']['student_id'] = $controller->Session->read('Student.id');
				
				$this->unbindModel(array('validate' => array('bank_id')));
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => 'bankAccountsView', $bankAccountId));
				}
			} else {
				$controller->request->data = $data;
			}
			$Bank = ClassRegistry::init('Bank');
			$bankBranchOptions = $this->BankBranch->find('list', array('conditions' => array('bank_id' => $data['BankBranch']['bank_id'], 'visible' => 1), 'recursive' => -1));
			$bankObj = $Bank->findById($data['BankBranch']['bank_id']);

			$controller->set(compact('bankObj', 'bankBranchOptions', 'header', 'yesnoOptions'));
		} else {
			return $controller->redirect(array('action' => 'bankAccounts'));
		}
	}

	public function bankAccountsDelete($controller, $params) {
		return $this->remove($controller, 'bankAccounts');
	}
}
