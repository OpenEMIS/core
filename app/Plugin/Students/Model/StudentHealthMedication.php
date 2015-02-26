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

class StudentHealthMedication extends StudentsAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('openemis_no', 'first_name', 'last_name'))),
		'ControllerAction', 
		'DatePicker' => array('start_date', 'end_date')
	);

	public $belongsTo = array(
		'Students.Student',
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
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Name.'
			)
		),
		'start_date' => array(
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'end_date'),
				'message' => 'Commenced Date cannot be later than Ended Date'
			),
		)
	);

	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate >= $startDate;
	}

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name'),
				array('field' => 'dosage'),
				array('field' => 'start_date', 'type' => 'datepicker', 'labelKey' => 'HealthMedication.start_date'),
				array('field' => 'end_date', 'type' => 'datepicker', 'labelKey' => 'HealthMedication.end_date'),
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

	public function healthMedication($controller, $params) {
		$controller->Navigation->addCrumb('Health - Medications');
		$header = __('Health - Medications');
		$this->recursive = -1;
		$data = $this->findAllByStudentId($controller->Session->read('Student.id'));

		$controller->set(compact('header', 'data'));
	}

	public function healthMedicationView($controller, $params) {
		$controller->Navigation->addCrumb('Health - View Medication');

		$header = __('Health - View Medication');

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$data = $this->findById($id);

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action' => 'healthMedication'));
		}

		$controller->Session->write('StudentHealthMedication.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}

	public function healthMedicationDelete($controller, $params) {
		if ($controller->Session->check('StudentHealthMedication.id')) {
			$id = $controller->Session->read('StudentHealthMedication.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('StudentHealthMedication.id');
			$controller->redirect(array('action' => 'healthMedication'));
		}
	}

	public function healthMedicationAdd($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add Medication');
		$controller->set('header', __('Health - Add Medication'));
		$this->setup_add_edit_form($controller, $params);
	}

	public function healthMedicationEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Medication');
		$controller->set('header', __('Health - Edit Medication'));
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}

	function setup_add_edit_form($controller, $params) {
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];

		if ($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->name]['student_id'] = $controller->Session->read('Student.id');
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'healthMedication'));
			}
		} else {
			$this->recursive = -1;
			$data = $this->findById($id);
			if (!empty($data)) {
				$controller->request->data = $data;
			}
		}
	}

}
