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

class InstitutionSiteBankAccount extends AppModel {
	public $actsAs = array(
		'Excel',
		'ControllerAction'
	);

	public $belongsTo = array(
		'BankBranch',
		'InstitutionSite',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
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
			"$alias.active" => array(0 => 'Inactive', 1 => 'Active')
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

	public function bankAccounts($controller, $params) {
		$controller->Navigation->addCrumb('Bank Accounts');

		$data = $this->findAllByInstitutionSiteId($controller->institutionSiteId);
		$bankList = $this->BankBranch->Bank->find('list');
		$header = __('Bank Accounts');

		$controller->set(compact('data', 'bank', 'bankList', 'header'));
	}

	public function bankAccountsView($controller, $params) {
		$controller->Navigation->addCrumb('Bank Account Details');
		$bankAccountId = $controller->params['pass'][0];
		$data = $this->findById($bankAccountId);

		if (!empty($data)) {
			$controller->Session->write('InstitutionSiteBankAccount.id', $bankAccountId);
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'bankAccounts'));
		}
		$fields = $this->getDisplayFields($controller);
		$header = __('Bank Accounts');
		$controller->set(compact('data', 'fields', 'header'));
	}

	public function bankAccountsAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Bank Account');
		$Bank = ClassRegistry::init('Bank');
		$Branch = ClassRegistry::init('BankBranch');
		$bankList = $Bank->find('list', array('conditions' => array('Bank.visible' => 1), 'order' => array('order')));
		$branchList = array();
		$bankId = 0;
		$yesnoOptions = $controller->Option->get('yesno');
		$header = __('Add Bank Account');

		
		
		$institutionSiteId = $controller->institutionSiteId;
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$this->create();
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'bankAccounts'));
			}
		}
		$bankId = (isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : (isset($controller->request->data['InstitutionSiteBankAccount']['bank_id']) ? $controller->request->data['InstitutionSiteBankAccount']['bank_id'] : key($bankList)));
		$branchList = $Branch->find('list', array('conditions' => array('BankBranch.visible' => 1, 'bank_id' => $bankId), 'order' => array('BankBranch.order')));
		$controller->set(compact('bankList', 'branchList', 'bankId', 'institutionSiteId', 'yesnoOptions', 'header'));
	}

	public function bankAccountsEdit($controller, $params) {
		$controller->Navigation->addCrumb('Bank Account Details');

		$bankAccountId = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : 0;
		$data = $this->findById($bankAccountId);
		$yesnoOptions = $controller->Option->get('yesno');
		$header = __('Bank Account Details');
		if (!empty($data)) {
			$Bank = ClassRegistry::init('Bank');
			$Branch = ClassRegistry::init('BankBranch');
			$bankObj = $Bank->findById($data['BankBranch']['bank_id']);
			$branchList = $Branch->find('list', array('conditions' => array('BankBranch.visible' => 1, 'bank_id' => $bankObj['Bank']['id']), 'order' => array('BankBranch.order')));
			$controller->set(compact('bankObj', 'branchList', 'yesnoOptions', 'header'));
			if ($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => 'bankAccountsView', $bankAccountId));
				}
			} else {
				$controller->request->data = $data;
			}
		} else {
			return $controller->redirect(array('action' => 'bankAccounts'));
		}
	}

	public function bankAccountsDelete($controller, $params) {
		if ($controller->Session->check('InstitutionSiteBankAccount.id')) {
			$id = $controller->Session->read('InstitutionSiteBankAccount.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			return $controller->redirect(array('action' => 'bankAccounts'));
		}
	}
}
