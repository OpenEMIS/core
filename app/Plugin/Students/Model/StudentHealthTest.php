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

class StudentHealthTest extends StudentsAppModel {
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction', 
		'DatePicker' => array('date')
	);

	public $belongsTo = array(
		'Students.Student',
		'HealthTestType',
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
		'health_test_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Test.'
			)
		)
	);

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'date', 'type' => 'datepicker'),
				array('field' => 'name', 'model' => 'HealthTestType', 'labelKey' => 'general.type'),
				array('field' => 'result'),
				array('field' => 'comment'),
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

	public function healthTest($controller, $params) {
		$controller->Navigation->addCrumb('Health - Tests');
		$header = __('Health - Tests');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStudentId($controller->Session->read('Student.id'));

		$controller->set(compact('header', 'data'));
	}

	public function healthTestView($controller, $params) {
		$controller->Navigation->addCrumb('Health - View Test');
		$header = __('Health - View Test');

		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];
		$data = $this->findById($id);

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action' => 'healthTest'));
		}

		$controller->Session->write('StudentHealthTest.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}

	public function healthTestDelete($controller, $params) {
		if ($controller->Session->check('StudentHealthTest.id')) {
			$id = $controller->Session->read('StudentHealthTest.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			$controller->Session->delete('StudentHealthTest.id');
			$controller->redirect(array('action' => 'healthTest'));
		}
	}

	public function healthTestAdd($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add Test');
		$controller->set('header', __('Health - Add Test'));
		$this->setup_add_edit_form($controller, $params);
	}

	public function healthTestEdit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit Test');
		$controller->set('header', __('Health - Edit Test'));
		$this->setup_add_edit_form($controller, $params);
		$this->render = 'add';
	}

	function setup_add_edit_form($controller, $params) {
		$id = empty($params['pass'][0]) ? 0 : $params['pass'][0];

		if ($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->name]['student_id'] = $controller->Session->read('Student.id');
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'healthTest'));
			}
		} else {
			$this->recursive = -1;
			$data = $this->findById($id);
			if (!empty($data)) {
				$controller->request->data = $data;
			}
		}

		if (!empty($controller->request->data)) {
			$healthTestsOptions = $this->HealthTestType->getList(array('value' => $controller->request->data['StudentHealthTest']['health_test_type_id']));
		} else {
			$healthTestsOptions = $this->HealthTestType->getList(array('value' => 0));
		}

		
		$controller->set(compact('healthTestsOptions'));
	}

}
