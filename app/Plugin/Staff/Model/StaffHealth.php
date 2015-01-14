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

class StaffHealth extends StaffAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Staff' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction'
	);

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

	/* Excel Behaviour */
	public function excelGetFieldLookup() {
		$alias = $this->alias;
		$lookup = array(
			"$alias.health_insurance" => array(0 => 'No', 1 => 'Yes')
		);
		return $lookup;
	}
	/* End Excel Behaviour */
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'blood_type'),
				array('field' => 'doctor_name'),
				array('field' => 'doctor_contact'),
				array('field' => 'medical_facility'),
				array('field' => 'health_insurance', 'type' => 'select', 'options' => $controller->Option->get('yesno')),
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
		if (!$controller->Session->check('Staff.id')) {
			return $controller->redirect(array('action' => 'index'));
		}
	}

	public function health($controller, $params) {
		$this->render = false;
		return $controller->redirect(array('action' => 'healthView'));
	}

	public function healthView($controller, $params) {
		$controller->Navigation->addCrumb('Health - Overview');
		$header = __('Health - Overview');
		$data = $this->findByStaffId($controller->Session->read('Staff.id'));

		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'fields', 'data'));
	}

	public function healthEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Overview');
		$header = __('Health - Edit Overview');

		$staffId = $controller->Session->read('Staff.id');
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->name]['staff_id'] = $staffId;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'healthView'));
			}
		} else {
			$this->recursive = -1;
			$data = $this->findByStaffId($staffId);
			if (!empty($data)) {
				$controller->request->data = $data;
			}
		}

		$yesnoOptions = $controller->Option->get('yesno');
		$bloodTypeOptions = $controller->Option->get('bloodtype');
		$controller->set(compact('header', 'yesnoOptions', 'bloodTypeOptions'));
	}
}
