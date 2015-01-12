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

class AcademicPeriod extends AppModel {
	public $actsAs = array(
		'Tree',
		'Reorder',
		'CustomReport',
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'AcademicPeriodLevel',
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
	
	public $hasMany = array(
	);
	
	public $validate = array(
		// fix validate
	);
	
	public function beforeAction() {
        parent::beforeAction();
		
		$this->fields['parent_id']['type'] = 'hidden';
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
		$this->fields['order']['visible'] = false;
		$this->fields['visible']['type'] = 'select';
		$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
		
		if ($this->action == 'view') {
			$this->fields['academic_period_level_id']['dataModel'] = 'AcademicPeriodLevel';
			$this->fields['academic_period_level_id']['dataField'] = 'name';
		}
		
		$this->Navigation->addCrumb('Academic Periods');
		$this->setVar('contentHeader', __('Academic Periods'));
    }
	
	public function index() {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$academicPeriod = end($paths);
		$data = array();
		$maxLevel = $this->AcademicPeriodLevel->field('level', null, 'level DESC');
		
		if($academicPeriod !== false) {
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $academicPeriod[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$this->setVar(compact('paths', 'data', 'parentId', 'maxLevel'));
	}
	
	public function reorder() {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$academicPeriod = end($paths);
		$data = array();
		
		if($academicPeriod !== false) {
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $academicPeriod[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$this->setVar(compact('paths', 'data', 'parentId'));
	}
	
	public function move() {
		if ($this->request->is(array('post', 'put'))) {
			$params = $this->controller->params;
			$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
			$data = $this->request->data;
			if ($parentId == 0) {
				$parentId = 1;
			}
			$conditions = array('parent_id' => $parentId);
			$this->moveOrder($data, $conditions);
			$redirect = array('action' => get_class($this), 'reorder', 'parent' => $parentId);
			return $this->redirect($redirect);
		}
	}
	
	public function add() {
		$this->fields['visible']['visible'] = false;
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$academicPeriod = end($paths);
		
		$pathList = array();
		foreach($paths as $item) {
			$pathList[] = $item[$this->alias]['name'];
		}
		$pathToString = implode(' / ', $pathList);
		$parentId = $academicPeriod[$this->alias]['id'];
		$level = $this->AcademicPeriodLevel->field('level', array('id' => $academicPeriod[$this->alias]['academic_period_level_id']));
		$academicPeriodLevelOptions = $this->AcademicPeriodLevel->find('list', array('conditions' => array('level >' => $level)));
		
		if($this->request->is(array('post', 'put'))) {
			$this->request->data[$this->alias]['parent_id'] = $parentId;
			$this->request->data[$this->alias]['order'] = $this->field('order', array('parent_id' => $parentId), 'order DESC') + 1;
			if ($this->save($this->request->data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => get_class($this), 'view', 'parent' => $parentId, $this->id));
			}
		}
		$this->setVar(compact('data', 'fields', 'parentId', 'pathToString', 'academicPeriodLevelOptions'));
	}
	
	public function view($id=0) {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		
		$this->setVar(compact('data', 'parentId'));
	}
	
	public function edit($id=0) {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		
		$yesnoOptions = $this->controller->Option->get('yesno');
		
		if(!empty($data)) {
			$this->setVar(compact('yesnoOptions', 'parentId'));
			if($this->request->is(array('post', 'put'))) {
				if ($this->save($this->request->data)) {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => get_class($this), 'view', 'parent' => $parentId, $id));
				}
			} else {
				$this->request->data = $data;
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => get_class($this), 'parent' => $parentId));
		}
	}

	public function getName($id) {
		$data = $this->findById($id);	
		return $data['AcademicPeriod']['name'];
	}
}