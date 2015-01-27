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

class Area extends AppModel {
	public $actsAs = array(
		'Tree',
		'Reorder',
		'CustomReport',
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'AreaLevel',
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
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'area_id',
			'dependent' => true
		)
	);
	
	public $validate = array(
		'code' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the code for the Area.'
			),
			'isUnique' => array(
				'rule' => 'isUnique',
				'message' => 'There are duplicate area code.'
			)
		),
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				 'message' => 'Please enter the name for the Area.'
			)
		)
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
			$this->fields['area_level_id']['dataModel'] = 'AreaLevel';
			$this->fields['area_level_id']['dataField'] = 'name';
		}
		
		$this->Navigation->addCrumb('Areas (Education)');
		$this->setVar('contentHeader', __('Areas (Education)'));
    }
	
	public function index() {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$area = end($paths);
		$data = array();
		$maxLevel = $this->AreaLevel->field('level', null, 'level DESC');
		
		if($area !== false) {
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $area[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$this->setVar(compact('paths', 'data', 'parentId', 'maxLevel'));
	}
	
	public function reorder() {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$area = end($paths);
		$data = array();
		
		if($area !== false) {
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $area[$this->alias]['id']),
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
		$area = end($paths);
		
		$pathList = array();
		foreach($paths as $item) {
			$pathList[] = $item[$this->alias]['name'];
		}
		$pathToString = implode(' / ', $pathList);
		$parentId = $area[$this->alias]['id'];
		$level = $this->AreaLevel->field('level', array('id' => $area[$this->alias]['area_level_id']));
		$areaLevelOptions = $this->AreaLevel->find('list', array('conditions' => array('level >' => $level)));
		
		if($this->request->is(array('post', 'put'))) {
			$this->request->data[$this->alias]['parent_id'] = $parentId;
			$this->request->data[$this->alias]['order'] = $this->field('order', array('parent_id' => $parentId), 'order DESC') + 1;
			if ($this->save($this->request->data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => get_class($this), 'view', 'parent' => $parentId, $this->id));
			}
		}
		$this->setVar(compact('data', 'fields', 'parentId', 'pathToString', 'areaLevelOptions'));
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
	
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('Area.id', 'Area.code', 'Area.name', 'AreaLevel.name'),
			'joins' => array(
				array(
					'table' => 'area_levels',
					'alias' => 'AreaLevel',
					'conditions' => array('AreaLevel.id = Area.area_level_id')
				)
			),
			'conditions' => array(
				'OR' => array(
					'Area.name LIKE' => $search,
					'Area.code LIKE' => $search,
					'AreaLevel.name LIKE' => $search
				)
			),
			'order' => array('AreaLevel.level', 'Area.order')
		));
		
		$data = array();
		foreach($list as $obj) {
			$area = $obj['Area'];
			$level = $obj['AreaLevel'];
			$data[] = array(
				'label' => sprintf('%s - %s (%s)', $level['name'], $area['name'], $area['code']),
				'value' => $area['id']
			);
		}
		return $data;
	}
	
	// Used by Yearbook
	public function getAreasByLevel($level) {
		$AreaLevel = ClassRegistry::init('AreaLevel');
		$levels = $AreaLevel->find('all', array('order' => array('AreaLevel.level')));
		$areas = array();
		if(count($levels) >= $level) {
			$levelId = $levels[$level-1]['AreaLevel']['id'];
			$this->formatResult = true;
			$areas = $this->find('all', array(
				'recursive' => -1,
				'conditions' => array('Area.area_level_id' => $levelId),
				'order' => array('Area.order')
			));
		}
		return $areas;
	}

	public function fetchSubLevelList($parentId) {

		$children = $this->find('all', array(
			'conditions' => array('Area.parent_id' => $parentId ),
			'fields' => 'GROUP_CONCAT(Area.id) as children'
		));
		$data = $children[0][0]['children'];
		return $data;
	}

	public function getChildren($parentId, $str=null) {
		$children = $this->find('all', array('conditions' => array('Area.parent_id' => $parentId ), 'fields' => 'GROUP_CONCAT(Area.id) as children'));
		$childrenId = $children[0][0]['children'];

		if ($childrenId == "") { return $str; }

		$children = explode(",", $childrenId);
		$str .= $childrenId.",";

		$data = "";
		foreach ($children as $value) {
			$data .= $this->getChildren($value, $str);
		}

		$data = substr($data, 0, strlen($data)-1);
		$values = array_unique(explode(",",$data));
		return implode(",",$values);

	}

	public function getName($id) {
		$data = $this->findById($id);	
		return $data['Area']['name'];
	}

    public function getAreaLevelId($id) {
        $data = $this->findById($id);
        return $data['Area']['area_level_id'];
    }
	
	// Used by SecurityController
	public function getGroupAccessList($exclude) {
		$conditions = array('Area.visible' => 1);
		if(!empty($exclude)) {
			$conditions['Area.id NOT'] = $exclude;
		}
		
		$data = $this->find('list', array(
			'fields' => array('AreaLevel.id', 'AreaLevel.name'),
			'joins' => array(
				array(
					'table' => 'area_levels',
					'alias' => 'AreaLevel',
					'conditions' => array('AreaLevel.id = Area.area_level_id')
				)
			),
			'conditions' => $conditions,
			'group' => array('AreaLevel.id HAVING COUNT(Area.id) > 0'),
			'order' => array('AreaLevel.level')
		));
		return $data;
	}
	
	public function getGroupAccessValueList($parentId, $exclude) {
		$conditions = array('Area.area_level_id' => $parentId, 'Area.visible' => 1);
		if(!empty($exclude)) {
			$conditions['Area.id NOT'] = $exclude;
		}
		
		$data = $this->find('list', array(
			'fields' => array('Area.id', 'Area.name'),
			'conditions' => $conditions,
			'order' => array('Area.order')
		));
		return $data;
	}
	
	public function getParentId($areaId){
		$result = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('Area.parent_id'),
			'conditions' => array(
				'OR' => array(
					'Area.id' => $areaId
				)
			)
		));
		
		if(!empty($result)){
			return $result['Area']['parent_id'];
		}else{
			return NULL;
		}
	}
}