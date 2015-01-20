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

class StudentGuardian extends StudentsAppModel {
	public $actsAs = array('ControllerAction');
	public $belongsTo = array(
		'Students.GuardianRelation',
		'Students.Guardian',
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
		'guardian_relation_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Relationship'
			)
		)
	);

	
	public function getGuardian($guardianId, $studentId) {
		$this->unbindModel(array('belongsTo' => array('Guardian')));
		
		$data = $this->find('first', array(
			'recursive' => 0,
			'fields' => array('Guardian.*', 'StudentGuardian.*', 'GuardianRelation.*', 'GuardianEducationLevel.*', 'CreatedUser.*', 'ModifiedUser.*'),
			'joins' => array(
				array(
					'table' => 'guardians',
					'alias' => 'Guardian',
					'conditions' => array('Guardian.id = StudentGuardian.guardian_id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'GuardianEducationLevel',
					'conditions' => array('GuardianEducationLevel.id = Guardian.guardian_education_level_id')
				),
			),
			'conditions' => array(
				'StudentGuardian.guardian_id' => $guardianId,
				'StudentGuardian.student_id' => $studentId
			 )
		));
		return $data;
	}
	
	public function getGuardians($studentId) {
		$this->unbindModel(array('belongsTo' => array('Guardian')));
		$data = $this->find('all', array(
			'recursive' => 0,
			'fields' => array('Guardian.*', 'StudentGuardian.*', 'GuardianRelation.*', 'GuardianEducationLevel.*', 'CreatedUser.*', 'ModifiedUser.*'),
			'joins' => array(
				array(
					'table' => 'guardians',
					'alias' => 'Guardian',
					'conditions' => array('Guardian.id = StudentGuardian.guardian_id')
				),
				array(
					'table' => 'field_option_values',
					'alias' => 'GuardianEducationLevel',
					'conditions' => array('GuardianEducationLevel.id = Guardian.guardian_education_level_id')
				),
			),
			'conditions' => array(
				'StudentGuardian.student_id' => $studentId
			)
		));

		return $data;
	}
	
	public function guardians($controller, $params) {
		$controller->Navigation->addCrumb('Guardians');
		$header = __('Guardians');
		$data = $this->getGuardians($controller->Session->read('Student.id'));
		$controller->set(compact('header', 'data'));
	}
	
	public function guardiansAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add Guardian');
		$header = __('Add Guardian');
		if ($controller->request->is('post')) {
			$data = $controller->request->data;
			if (!empty($data['Guardian']['existing_id'])){
				$data['StudentGuardian']['guardian_id'] = $data['Guardian']['existing_id'];
				$data['StudentGuardian']['student_id'] = $controller->Session->read('Student.id');

				$this->create();
				$this->set($data);
				
				if ($this->validates()){
					if ($this->saveAll($data)){
						$controller->Message->alert('general.add.success');
						return $controller->redirect(array('action' => 'guardians'));
					}
				}
			} else {
				$Guardian = ClassRegistry::init('Students.Guardian');
				
				$Guardian->create();
				$Guardian->set($data);
				
				if ($Guardian->validates()){
					if ($Guardian->saveAll($data)){
						$guardianId = $Guardian->getInsertID();
						$data['StudentGuardian']['guardian_id'] = $guardianId;
						$data['StudentGuardian']['student_id'] = $controller->Session->read('Student.id');

						$this->create();
						$this->set($data);

						if ($this->validates()){
							if ($this->saveAll($data)){
								$controller->Message->alert('general.add.success');
								return $controller->redirect(array('action' => 'guardians'));
							}
						} else {
							$controller->request->data['Guardian']['existing_id'] = $guardianId;
						}
					}
				}
			}
		}
		
		$genderOptions = array('M' => __('Male'), 'F' => __('Female'));
		$relationshipOptions = $this->GuardianRelation->getList(array('value' => 0));
		//$GuardianEducationLevel = ClassRegistry::init('Students.GuardianEducationLevel');
		$educationOptions = $this->Guardian->GuardianEducationLevel->getList(array('value' => 0));

		$controller->set(compact('header', 'genderOptions', 'relationshipOptions', 'educationOptions'));
	}
	
	public function guardiansEdit($controller, $params) {
		$guardianId = $params['pass'][0];
		if ($controller->request->is('get')) {
			$guardianObj = $this->getGuardian($guardianId, $controller->Session->read('Student.id'));
			
			if (!empty($guardianObj)) {
				$controller->Navigation->addCrumb('Edit Guardian Details');
				$controller->request->data = $guardianObj;
			}
		} else {
			$data = $controller->request->data;
			$this->Guardian->set($data);
			
			if ($this->Guardian->validates()){
				if ($this->Guardian->saveAll($data) && $this->saveAll($data)){
					$controller->Message->alert('general.edit.success');
					$controller->redirect(array('action' => 'guardiansView', $guardianId));
				}
			}
		}

		$genderOptions = array('M' => __('Male'), 'F' => __('Female'));

		$relationshipOptions = $this->GuardianRelation->getList(array('value' => $controller->request->data['StudentGuardian']['guardian_relation_id']));
		$educationOptions = $this->Guardian->GuardianEducationLevel->getList(array('value' => $controller->request->data['Guardian']['guardian_education_level_id']));

		$controller->set('genderOptions', $genderOptions);
		$controller->set('relationshipOptions', $relationshipOptions);
		$controller->set('educationOptions', $educationOptions);

		$controller->set('guardianId', $guardianId);
	}
	
	public function guardiansView($controller, $params) {
		$guardianId = $controller->params['pass'][0];
		$guardianObj = $this->getGuardian($guardianId, $controller->Session->read('Student.id'));

		if (!empty($guardianObj)) {
			$controller->Navigation->addCrumb('Guardian Details');

			$controller->Session->write('StudentGuardian.id', $guardianId);
			$controller->set('guardianObj', $guardianObj);
		} else {
			$controller->redirect(array('action' => 'guardians'));
		}
	}
	
	public function guardiansDelete($controller, $params) {
		if ($controller->Session->check('Student.id') && $controller->Session->check('StudentGuardian.id')) {
			$guardianId = $controller->Session->read('StudentGuardian.id');
			$studentId = $controller->Session->read('Student.id');
			$guardianObj = $this->getGuardian($guardianId, $studentId);
			$guardianName = $guardianObj['Guardian']['first_name'] . ' ' . $guardianObj['Guardian']['last_name'];
			
			$this->deleteAll(array('StudentGuardian.guardian_id' => $guardianId, 'StudentGuardian.student_id' => $studentId));
			$controller->Message->alert('general.delete.success');
			$controller->redirect(array('action' => 'guardians'));
		}
	}
	
	public function guardiansAutoComplete($controller, $params) {
		$this->render = false;
		$search = $params->query['term'];
		$Guardian = ClassRegistry::init('Students.Guardian');
		$result = $Guardian->getAutoCompleteList($search, $controller->Session->read('Student.id'));
		return json_encode($result);
	}
}
