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
	public $actsAs = array('ControllerAction2', 'Reorder');
	public $belongsTo = array(
		'EducationLevel',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
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
	
	public $_condition = 'education_level_id';
	
	public function beforeAction() {
		parent::beforeAction();
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->setVar('conditionId', $conditionId);
		
		$this->fields['order']['visible'] = false;
		$this->fields['education_level_id']['type'] = 'disabled';
		
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
			$this->fields['education_level_id']['type'] = 'hidden';
			$this->fields['education_level_id']['value'] = $conditionId;
			$this->fields['education_level'] = array(
				'visible' => true,
				'type' => 'disabled',
				'value' => $this->EducationLevel->field('name', array('EducationLevel.id' => $conditionId))
			);
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
			$this->fields['education_level_id']['dataModel'] = 'EducationLevel';
			$this->fields['education_level_id']['dataField'] = 'name';
		}
		
		$this->Navigation->addCrumb('Education Cycles');
		$this->setVar('selectedAction', 'EducationSystem');
		$this->setVar('_condition', $this->_condition);
	}
	
	public function index() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		
		if($this->EducationLevel->exists($conditionId)) {
			$data = $this->findAllByEducationLevelId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$levelObj = $this->EducationLevel->findById($conditionId);
			$paths = array();
			$paths[] = array(
				'name' => $levelObj['EducationSystem']['name'],
				'url' => array('action' => 'EducationSystem')
			);
			$paths[] = array(
				'name' => $levelObj['EducationLevel']['name'],
				'url' => array('action' => 'EducationLevel', 'education_system_id' => $levelObj['EducationSystem']['id'])
			);
			$paths[] = array(
				'name' => '(' . __('Education Cycles') . ')'
			);
			$this->setVar(compact('data', 'paths'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function add() {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		$this->render = '../template/add';
		if($this->EducationLevel->exists($conditionId)) {
			$this->setVar(compact('levelOptions'));
			if ($this->request->is(array('post', 'put'))) {
				$this->request->data[$this->alias]['education_level_id'] = $conditionId;
				$this->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
				if ($this->save($this->request->data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
				}
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function view($id) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationLevel->exists($conditionId)) {
			$data = $this->findById($id);
			$this->setVar(compact('data'));
			$this->render = '../template/view';
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
		}
	}
	
	public function edit($id) {
		$params = $this->controller->params;
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationLevel->exists($conditionId)) {
			$data = $this->findById($id);
			
			if(!empty($data)) {
				if ($this->request->is(array('post', 'put'))) {
					if ($this->save($this->request->data)) {
						$this->Message->alert('general.edit.success');
						return $this->redirect(array('action' => get_class($this), 'view', $this->_condition => $conditionId, $id));
					}
				} else {
					$this->request->data = $data;
				}
				$this->render = '../template/edit';
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), $this->_condition => $conditionId));
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => 'EducationSystem'));
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