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

class StudentNationality extends StudentsAppModel {
	public $actsAs = array('Excel', 'ControllerAction');
	public $belongsTo = array(
		'Students.Student',
		'Country',
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
		'country_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Country'
			)
		)
	);

	/* Excel Behaviour */
	public function excelGetConditions() {
		$id = CakeSession::read('Student.id');
		$conditions = array($this->alias . '.student_id' => $id);
		return $conditions;
	}
	/* End Excel Behaviour */

	public function getDisplayFields($controller) {
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name', 'model' => 'Country'),
				array('field' => 'comments'),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function nationalities($controller, $params) {
		$controller->Navigation->addCrumb('Nationalities');
		$header = __('Nationalities');
		$this->unbindModel(array('belongsTo' => array('Student', 'ModifiedUser','CreatedUser')));
		$data = $this->findAllByStudentId($controller->Session->read('Student.id'));
		$controller->set(compact('header', 'data'));
	}
	
	public function nationalitiesAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Nationalities');
		$header = __('Add Nationalities');
		
		if ($controller->request->is('post')) {
			$data = $controller->request->data[$this->alias];

			$this->create();
			$data['student_id'] = $controller->Session->read('Student.id');

			if ($this->save($data)) {
				$id = $this->getLastInsertId();
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'nationalities'));
			}
		}
		$ConfigItem = ClassRegistry::init('ConfigItem');
		$defaultCountryId = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'country_id'));
		
		$countryOptions = $this->Country->getOptions();
		$controller->set(compact('header', 'countryOptions','defaultCountryId'));
	}

	public function nationalitiesView($controller, $params) {
		$id = isset($params['pass'][0]) ?$params['pass'][0] : 0;
		$controller->Navigation->addCrumb('Nationality Details');
		$header = __('Nationality Details');
		$data = $this->findById($id);

		if (empty($data)) {
			$controller->Message->alert('general.noData');
			return $controller->redirect(array('action' => 'nationalities'));
		}
		$controller->Session->write('StudentNationality.id', $id);
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('header', 'data', 'fields', 'id'));
	}

	public function nationalitiesEdit($controller, $params)  {
		$id = isset($params['pass'][0]) ?$params['pass'][0] : 0;
		$controller->Navigation->addCrumb('Edit Nationality');
		$header = __('Edit Nationality');
		
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$nationalityData = $controller->request->data[$this->alias];
			$nationalityData['student_id'] = $controller->Session->read('Student.id');

			if ($this->save($nationalityData)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'nationalitiesView', $id));
			}
		}
		else{
			$this->recursive = -1;
			$data = $this->findById($id);

			if (empty($data)) {
				return $controller->redirect(array('action' => 'nationalities'));
			}
			$controller->request->data = $data;
		}
		$countryOptions = $this->Country->getOptions();
		
		$controller->set(compact('id', 'header', 'countryOptions'));
	}

	public function nationalitiesDelete($controller, $params) {
		return $this->remove($controller, 'nationalities');
	}

}
