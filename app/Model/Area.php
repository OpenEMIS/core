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
		'ControllerAction'
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
	
	public $belongsTo = array('AreaLevel');
	
	public function beforeAction($controller, $action) {
        parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb('Areas');
		$controller->set('header', __('Areas'));
    }
	
	public function getDisplayFields($controller) {
		$yesnoOptions = $controller->Option->get('yesno');
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'name'),
                array('field' => 'code'),
				array('field' => 'name', 'model' => 'AreaLevel'),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public function areas($controller, $params) {
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
		$controller->set(compact('paths', 'data', 'parentId', 'maxLevel'));
	}
	
	public function areasReorder($controller, $params) {
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
		$controller->set(compact('paths', 'data', 'parentId'));
	}
	
	public function areasMove($controller, $params) {
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
			$data = $controller->request->data;
			$conditions = array('parent_id' => $parentId);
			$this->moveOrder($data, $conditions);
			$redirect = array('action' => 'areasReorder', 'parent' => $parentId);
			return $controller->redirect($redirect);
		}
	}
	
	public function areasAdd($controller, $params) {
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
		
		if($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->alias]['parent_id'] = $parentId;
			$controller->request->data[$this->alias]['order'] = $this->field('order', array('parent_id' => $parentId), 'order DESC') + 1;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.edit.success');
				return $controller->redirect(array('action' => 'areasView', 'parent' => $parentId, $this->id));
			}
		}
		$controller->set(compact('data', 'fields', 'parentId', 'pathToString', 'areaLevelOptions'));
	}
	
	public function areasView($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'fields', 'parentId'));
	}
	
	public function areasEdit($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		$fields = $this->getDisplayFields($controller);
		$yesnoOptions = $controller->Option->get('yesno');
		
		if(!empty($data)) {
			$controller->set(compact('fields', 'yesnoOptions', 'parentId'));
			if($controller->request->is('post') || $controller->request->is('put')) {
				if ($this->save($controller->request->data)) {
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => 'areasView', 'parent' => $parentId, $id));
				}
			} else {
				$controller->request->data = $data;
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'areas', 'parent' => $parentId));
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
}