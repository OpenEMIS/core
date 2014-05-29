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

class EducationCycle extends AppModel {
	public $actsAs = array('ControllerAction', 'Reorder');
	public $belongsTo = array('EducationLevel');
	public $hasMany = array('EducationProgramme');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		),
		'admission_age' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the admission age'
			),
			'numeric' => array(
				'rule' => 'numeric',
				'message' => 'Please enter a numeric value'
			)
		)
	);
	
	public $_action = 'cycles';
	public $_header = 'Education Cycles';
	public $_condition = 'education_level_id';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb($this->_header);
		$controller->set('header', __($this->_header));
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action);
		$controller->set('_condition', $this->_condition);
	}
	
	public function getDisplayFields($controller) {
		$yesnoOptions = $controller->Option->get('yesno');
		$levelOptions = $this->EducationLevel->find('list', array('order' => 'order'));
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name'),
				array('field' => 'admission_age'),
				array('field' => 'education_level_id', 'type' => 'select', 'options' => $levelOptions, 'edit' => false),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function cycles($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationLevel->exists($conditionId)) {
			$data = $this->findAllByEducationLevelId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$levelObj = $this->EducationLevel->findById($conditionId);
			$paths = array();
			$paths[] = array(
				'name' => $levelObj['EducationSystem']['name'],
				'url' => array('action' => 'systems')
			);
			$paths[] = array(
				'name' => $levelObj['EducationLevel']['name'],
				'url' => array('action' => 'levels', 'education_system_id' => $levelObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => '(' . __($this->_header) . ')'
			);
			$controller->set(compact('data', 'paths', 'conditionId'));
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function cyclesAdd($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationLevel->exists($conditionId)) {
			$levelName = $this->EducationLevel->field('name', array('EducationLevel.id' => $conditionId));
			$levelOptions = array($conditionId => $levelName);
			$controller->set(compact('levelOptions', 'conditionId'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data[$this->alias]['education_level_id'] = $conditionId;
				$controller->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => $this->_action, $this->_condition => $conditionId));
				}
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function cyclesView($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationLevel->exists($conditionId)) {
			$id = isset($params->pass[0]) ? $params->pass[0] : 0;
			$data = $this->findById($id);
			$fields = $this->getDisplayFields($controller);
			$controller->set(compact('data', 'fields', 'conditionId'));
			$this->render = '../template/view';
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function cyclesEdit($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationLevel->exists($conditionId)) {
			$id = isset($params->pass[0]) ? $params->pass[0] : 0;
			$data = $this->findById($id);
			
			if(!empty($data)) {
				$fields = $this->getDisplayFields($controller);
				$controller->set(compact('fields', 'conditionId'));
				if($controller->request->is('post') || $controller->request->is('put')) {
					if ($this->save($controller->request->data)) {
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('action' => $this->_action.'View', $this->_condition => $conditionId, $id));
					}
				} else {
					$controller->request->data = $data;
				}
				$this->render = '../template/edit';
			} else {
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => $this->_action, $this->_condition => $conditionId));
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function getOfficialAgeByGrade($gradeId) {
		$age = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('EducationCycle.admission_age', 'EducationGrade.order'),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.education_cycle_id = EducationCycle.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.education_programme_id = EducationProgramme.id',
						'EducationGrade.id = ' . $gradeId
					)
				)
			)
		));
		return $age['EducationCycle']['admission_age'] + $age['EducationGrade']['order'] - 1;
	}

	public function getCycles() {
		$this->unbindModel(array('hasMany' => array('EducationProgramme'), 'belongsTo' => array('EducationLevel')));
		// $records = $this->find('list', array('conditions' => array('EducationCycle.visible' => 1)));
		$records = $this->find('all', array('conditions' => array('EducationCycle.visible' => 1)));
		$records = $this->formatArray($records);
		return $records;
	}
}