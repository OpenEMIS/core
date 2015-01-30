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

class AreaAdministrative extends AppModel {
	public $actsAs = array(
		'Tree',
		'Reorder',
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'AreaAdministrativeLevel',
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
			$this->fields['area_administrative_level_id']['dataModel'] = 'AreaAdministrativeLevel';
			$this->fields['area_administrative_level_id']['dataField'] = 'name';
		}
		
		$this->Navigation->addCrumb('Areas (Administrative)');
		$this->setVar('contentHeader', __('Areas (Administrative)'));
    }
	
	public function index() {
		$params = $this->controller->params;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$area = end($paths);
		$data = array();
		$this->AreaAdministrativeLevel->contain();
		$maxLevels = $this->AreaAdministrativeLevel->find('all', array(
			'fields' => array(
				'AreaAdministrativeLevel.area_administrative_id',
				'MAX(AreaAdministrativeLevel.level) AS maxLevel'
			),
			'group' => array('AreaAdministrativeLevel.area_administrative_id')
		));
		$tmp = array();
		foreach ($maxLevels as $key => $obj) {
			$tmp[$obj['AreaAdministrativeLevel']['area_administrative_id']] = $obj[0]['maxLevel'];
		}
		$maxLevels = $tmp;

		if($area !== false) {
			$this->contain('AreaAdministrativeLevel');
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $area[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$this->setVar(compact('paths', 'data', 'parentId', 'maxLevels'));
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
			$itemName = $item[$this->alias]['parent_id'] == -1 ? __('All') : $item[$this->alias]['name'];
			$pathList[] = $itemName;
		}
		$pathToString = implode(' / ', $pathList);
		$parentId = $area[$this->alias]['id'];
		$level = $this->AreaAdministrativeLevel->field('level', array('id' => $area[$this->alias]['area_administrative_level_id']));
		$areaAdministrativeId = $this->AreaAdministrativeLevel->field('area_administrative_id', array('id' => $area[$this->alias]['area_administrative_level_id']));
		$areaAdministrativeId = $level < 1 ? $parentId : $areaAdministrativeId;	//-1 => World, 0 => Country
		$areaLevelOptions = $this->AreaAdministrativeLevel->find('list', array(
			'conditions' => array(
				'AreaAdministrativeLevel.level >' => $level,
				'AreaAdministrativeLevel.area_administrative_id' => $areaAdministrativeId
			)
		));

		if($level == -1) {	//-1 => World
			$Country = ClassRegistry::init('Country');
			$countryOptions = $Country->getOptions('name');
			$this->setVar(compact('countryOptions'));
		}
		
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
			if($data[$this->AreaAdministrativeLevel->alias]['level'] == 0) {
				$Country = ClassRegistry::init('Country');
				$countryOptions = $Country->getOptions('name');
				$this->setVar(compact('countryOptions'));
			}

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

	public function fetchSubLevelList($parentId) {

		$children = $this->find('all', array(
			'conditions' => array('AreaAdministrative.parent_id' => $parentId ),
			'fields' => 'GROUP_CONCAT(AreaAdministrative.id) as children'
		));
		$data = $children[0][0]['children'];
		return $data;
	}

	public function getChildren($parentId, $str=null) {
		$children = $this->find('all', array('conditions' => array('AreaAdministrative.parent_id' => $parentId ), 'fields' => 'GROUP_CONCAT(AreaAdministrative.id) as children'));
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

	/**
	 * get Area name based on Area Id
	 * @return string 	area name
	 */
	public function getName($id) {
		$data = $this->findById($id);	
		return $data['AreaAdministrative']['name'];
	}
}