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
	public $actsAs = array('ControllerAction', 'DatePicker' => array('issue_date', 'expiry_date'));
	public $belongsTo = array(
		'Students.Student',
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
		'expiry_date' => array(
			'comparison' => array(
				//'rule' => array('field_comparison', '>', 'issue_date'),
				'rule' => array('compareDate', 'issue_date'),
				'allowEmpty' => true,
				'message' => 'Expiry Date must be greater than Issue Date'
			)
		)
	);

	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate < $startDate;
	}
	
	function field_comparison($check1, $operator, $field2) {
		foreach ($check1 as $key => $value1) {
			$value2 = $this->data[$this->alias][$field2];
			if (!Validation::comparison($value1, $operator, $value2))
				return false;
		}
		return true;
	}

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'IdentityType', 'labelKey' => 'general.type'),
				array('field' => 'number'),
				array('field' => 'issue_date'),
				array('field' => 'expiry_date'),
				array('field' => 'issue_location', 'labelKey' =>'Identities.issue_location'),
				array('field' => 'comments'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function identities($controller, $params) {
		$controller->Navigation->addCrumb(__('Identities'));
		$header = __('Identities');
		$this->unbindModel(array('belongsTo' => array('Student', 'ModifiedUser','CreatedUser')));
		$studentId = $controller->Session->read('Student.id');
		$data = $this->find('all', array('conditions' => array('StudentIdentity.student_id' => $studentId)));
		$controller->set(compact('header', 'data'));
	}

	public function identitiesAdd($controller, $params) {
		$controller->Navigation->addCrumb(__('Add Identity'));
		$header = __('Add Identity');
		if ($controller->request->is(array('post', 'put'))) {
			$data = $controller->request->data[$this->alias];

			$this->create();
			$data['student_id'] = $controller->Session->read('Student.id');

			if ($this->save($data)) {
				$id = $this->getLastInsertId();
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'identities'));
			}
		}
		$identityTypeOptions = $this->IdentityType->getList(1);
		$controller->set('identityTypeOptions', $identityTypeOptions);

		$controller->set(compact('header', 'identityTypeOptions'));
	}

	public function identitiesView($controller, $params) {
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0; //Identity Id
		
		$controller->Navigation->addCrumb(__('Identity Details'));
		$header = __('Identity Details');
		$data = $this->findById($id);

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'identities'));
		}

		$controller->Session->write('StudentIdentity.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'header', 'fields', 'id'));
	}

	public function identitiesEdit($controller, $params) {
		$controller->Navigation->addCrumb(__('Edit Identity'));
		$header = 'Edit Identity';
		$id = isset($params['pass'][0]) ? $params['pass'][0] : 0; // <<--- Identity Id
		$data = $this->findById($id);

		if ($controller->request->is(array('post', 'put'))) {
			$identityData = $controller->request->data[$this->alias];
			$identityData['student_id'] = $controller->Session->read('Student.id');

			if ($this->save($identityData)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'identitiesView', $id));
			}
		} else {
			if (empty($data)) {
				return $controller->redirect(array('action' => 'identities'));
			}
			$controller->request->data = $data;
		}
		$identityTypeOptions = $this->IdentityType->getOptions();
		$controller->set(compact('id', 'header', 'identityTypeOptions'));
	}

	public function identitiesDelete($controller, $params) {
		return $this->remove($controller, 'identities');
	}
}
