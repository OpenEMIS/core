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

class StaffHealthAllergy extends StaffAppModel {
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		//'Staff',
		'HealthAllergyType',
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
		'description' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Description.'
			)
		),
		'health_allergy_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Type.'
			)
		)
	);
	
	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				//   array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'HealthAllergyType', 'labelKey' => 'general.type'),
				array('field' => 'description'),
				array('field' => 'severe', 'type' => 'select', 'options' => $controller->Option->get('yesno')),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function healthAllergy($controller, $params) {
		$controller->Navigation->addCrumb('Health - Allergies');
		$header = __('Health - Allergies');

		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->Session->read('Staff.id'));

		$controller->set(compact('header', 'data'));
	}

	public function healthAllergyView($controller, $params) {
		$controller->Navigation->addCrumb('Health - View Allergy');
		$header = __('Health - View Allergy');

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$data = $this->findById($id);
		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'healthAllergy'));
		}

		$controller->Session->write('StaffHealthAllergyType.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}

	public function healthAllergyDelete($controller, $params) {
		return $this->remove($controller, 'healthAllergy');
	}

	public function healthAllergyAdd($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add Allergy');
		$controller->set('header', __('Health - Add Allergy'));
		$this->setup_add_edit_form($controller, $params, 'add');
	}

	public function healthAllergyEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Allergy');
		$controller->set('header', __('Health - Edit Allergy'));
		$this->setup_add_edit_form($controller, $params, 'edit');
		$this->render = 'add';
	}

	function setup_add_edit_form($controller, $params, $type) {
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->alias]['staff_id'] = $controller->Session->read('Staff.id');
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.' . $type . '.success');
				return $controller->redirect(array('action' => 'healthAllergy'));
			}
		} else {
			$this->recursive = -1;
			$data = $this->findById($id);
			if (!empty($data)) {
				$controller->request->data = $data;
			}
		}

		$healthAllergiesOptions = $this->HealthAllergyType->getList(1);
		$yesnoOptions = $controller->Option->get('yesno');

		$controller->set(compact('healthAllergiesOptions', 'yesnoOptions'));
	}
}
