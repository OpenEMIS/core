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

class EducationLevel extends AppModel {
	public $actsAs = array('ControllerAction', 'Reorder');
	public $belongsTo = array('EducationSystem', 'EducationLevelIsced');
	public $hasMany = array('EducationCycle');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		)
	);
	
	public $_action = 'levels';
	public $_header = 'Education Levels';
	public $_condition = 'education_system_id';
	
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
		$iscedOptions = $this->EducationLevelIsced->find('list', array('order' => 'order'));
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'id', 'type' => 'hidden'),
				array('field' => 'name'),
				array('field' => 'education_level_isced_id', 'type' => 'select', 'options' => $iscedOptions),
				array('field' => 'name', 'model' => 'EducationSystem', 'edit' => false),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		return $fields;
	}
	
	public function levels($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		if($this->EducationSystem->exists($conditionId)) {
			$data = $this->findAllByEducationSystemId($conditionId, array(), array($this->alias.'.order' => 'ASC'));
			$paths = array();
			$paths[] = array(
				'name' => $this->EducationSystem->field('name', array('EducationSystem.id' => $conditionId)),
				'url' => array('action' => 'systems')
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
	
	public function levelsAdd($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		
		if($this->EducationSystem->exists($conditionId)) {
			$systemName = $this->EducationSystem->field('name', array('EducationSystem.id' => $conditionId));
			$systemOptions = array($conditionId => $systemName);
			$iscedOptions = $this->EducationLevelIsced->find('list', array('order' => 'order'));
			$controller->set(compact('systemOptions', 'iscedOptions', 'conditionId'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				$controller->request->data[$this->alias]['education_level_id'] = $conditionId;
				$controller->request->data[$this->alias]['order'] = $this->field('order', array(), 'order DESC') + 1;
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => $this->_action, $this->_condition => $conditionId));
				}
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'systems'));
		}
	}
	
	public function levelsView($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		
		if($this->EducationSystem->exists($conditionId)) {
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
	
	public function levelsEdit($controller, $params) {
		$conditionId = isset($params->named[$this->_condition]) ? $params->named[$this->_condition] : 0;
		
		if($this->EducationSystem->exists($conditionId)) {
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
}
