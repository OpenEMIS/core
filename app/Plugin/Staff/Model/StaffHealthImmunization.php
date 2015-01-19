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

class StaffHealthImmunization extends StaffAppModel {

	//public $useTable = 'staff_health_histories';
	public $actsAs = array('ControllerAction', 'DatePicker' => 'date');
	public $belongsTo = array(
		//'Staff',
		'HealthImmunization',
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
		'dosage' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Dosage.'
			)
		),
		'health_immunization_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Immunization.'
			)
		)
	);

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'date', 'type' => 'datepicker'),
				array('field' => 'name', 'model' => 'HealthImmunization'),
				array('field' => 'dosage'),
				array('field' => 'comment'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}

	public function beforeAction($controller, $action) {
		$controller->set('model', $this->alias);
	}

	public function healthImmunization($controller, $params) {
		$controller->Navigation->addCrumb('Health - Immunizations');
		$header = __('Health - Immunizations');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->staffId);

		$controller->set(compact('header', 'data'));
	}

	public function healthImmunizationView($controller, $params) {
		$controller->Navigation->addCrumb('Health - View Immunization');
		$header = __('Health - View Immunization');

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$data = $this->findById($id); //('first',array('conditions' => array($this->name.'.id' => $id)));

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action' => 'healthImmunization'));
		}

		$controller->Session->write('StaffHealthImmunizationId', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}

	public function healthImmunizationDelete($controller, $params) {
		if ($controller->Session->check('StaffId') && $controller->Session->check('StaffHealthImmunizationId')) {
			$id = $controller->Session->read('StaffHealthImmunizationId');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('StaffHealthImmunizationId');
			$controller->redirect(array('action' => 'healthImmunization'));
		}
	}

	public function healthImmunizationAdd($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add Immunization');
		$controller->set('header', 'Health - Add Immunization');
		$this->setup_add_edit_form($controller, $params);
	}

	public function healthImmunizationEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Immunization');
		$controller->set('header', 'Health - Edit Immunization');
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}

	function setup_add_edit_form($controller, $params) {
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'healthImmunization'));
			}
		} else {

			$this->recursive = -1;
			$data = $this->findById($id);
			if (!empty($data)) {
				$controller->request->data = $data;
			}
		}

		if (!empty($controller->request->data)) {
			$healthImmunizationsOptions = $this->HealthImmunization->getList(array('value' => $controller->request->data['StaffHealthImmunization']['health_immunization_id']));
		} else {
			$healthImmunizationsOptions = $this->HealthImmunization->getList(array('value' => 0));
		}

		$controller->set(compact('healthImmunizationsOptions'));
	}

}
