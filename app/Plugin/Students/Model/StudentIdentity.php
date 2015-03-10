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

class StudentIdentity extends StudentsAppModel {
	public $useTable = 'user_identities';
	public $actsAs = array(
		'Excel' => array('header' => array('SecurityUser' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction2',
		'DatePicker' => array('issue_date', 'expiry_date')
	);

	public $belongsTo = array(
		'SecurityUser',
		'IdentityType',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	// please note that this is being used in student and staff add; validator()->remove('issue_location') for partial validation insertion
	public $validate = array(
		'identity_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Type'
			)
		),
		'number' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Number'
			)
		),
		'issue_location' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Issue Location'
			)
		),
		'issue_date' => array(
			'comparison' => array(
				'rule' => array('compareDate', 'expiry_date'),
				'allowEmpty' => true,
				'message' => 'Issue Date Should be Earlier Than Expiry Date'
			)
		),
		'expiry_date' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Expiry Date Is Required'
			)
		)
	);

	function field_comparison($check1, $operator, $field2) {
		foreach ($check1 as $key => $value1) {
			$value2 = $this->data[$this->alias][$field2];
			if (!Validation::comparison($value1, $operator, $value2))
				return false;
		}
		return true;
	}

	/* Excel Behaviour */
	public function excelGetConditions() {
		$conditions = array();
		if (CakeSession::check('Student.security_user_id')) {
			$id = CakeSession::read('Student.security_user_id');
			$conditions = array($this->alias.'.security_user_id' => $id);
		}
		return $conditions;
	}
	/* End Excel Behaviour */

	public function beforeAction() {
		parent::beforeAction();
		if (!$this->Session->check('Student.id')) {
			return $this->redirect(array('controller' => $this->controller->name, 'action' => 'index'));
		}
		$this->Navigation->addCrumb(__('Identities'));

		$this->fields['security_user_id']['type'] = 'hidden';
		$this->fields['security_user_id']['value'] = $this->Session->read('Student.security_user_id');
		$this->fields['identity_type_id']['type'] = 'select';
		$this->fields['identity_type_id']['options'] = $this->IdentityType->getList();
	}

	public function index() {
		$userId = $this->Session->read('Student.security_user_id');
		$this->contain(array('IdentityType' => array('id', 'name')));
		$data = $this->findAllBySecurityUserId($userId);
		$this->setVar(compact('data'));
	}
}
