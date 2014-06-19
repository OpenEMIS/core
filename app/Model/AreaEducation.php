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

class AreaEducation extends AppModel {
	public $actsAs = array(
		'Tree',
		'Reorder',
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
	
	public $belongsTo = array('AreaEducationLevel');
	
	public function beforeAction($controller, $action) {
        parent::beforeAction($controller, $action);
		$controller->Navigation->addCrumb('Areas (Education)');
		$controller->set('header', __('Areas (Education)'));
    }
	
	public function getDisplayFields($controller) {
		$yesnoOptions = $controller->Option->get('yesno');
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
                array('field' => 'name'),
                array('field' => 'code'),
				array('field' => 'name', 'model' => 'AreaEducationLevel'),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public function areasEducation($controller, $params) {
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$area = end($paths);
		$data = array();
		$maxLevel = $this->AreaEducationLevel->field('level', null, 'level DESC');
		
		if($area !== false) {
			$data = $this->find('all', array(
				'conditions' => array('parent_id' => $area[$this->alias]['id']),
				'order' => array('order')
			));
		}
		$controller->set(compact('paths', 'data', 'parentId', 'maxLevel'));
	}
	
	public function areasEducationReorder($controller, $params) {
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
	
	public function areaEducationMove($controller, $params) {
		if ($controller->request->is('post') || $controller->request->is('put')) {
			$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
			$data = $controller->request->data;
			$conditions = array('parent_id' => $parentId);
			$this->moveOrder($data, $conditions);
			$redirect = array('action' => 'areasReorder', 'parent' => $parentId);
			return $controller->redirect($redirect);
		}
	}
	
	public function areasEducationAdd($controller, $params) {
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$paths = $parentId != 0 ? $this->getPath($parentId) : $this->findAllByParentId(-1);
		$area = end($paths);
		
		$pathList = array();
		foreach($paths as $item) {
			$pathList[] = $item[$this->alias]['name'];
		}
		$pathToString = implode(' / ', $pathList);
		$parentId = $area[$this->alias]['id'];
		$level = $this->AreaEducationLevel->field('level', array('id' => $area[$this->alias]['area_education_level_id']));
		$areaLevelOptions = $this->AreaEducationLevel->find('list', array('conditions' => array('level >' => $level)));
		
		if($controller->request->is('post') || $controller->request->is('put')) {
			$controller->request->data[$this->alias]['parent_id'] = $parentId;
			$controller->request->data[$this->alias]['order'] = $this->field('order', array('parent_id' => $parentId), 'order DESC') + 1;
			if ($this->save($controller->request->data)) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'areasEducationView', 'parent' => $parentId, $this->id));
			}
		}
		$controller->set(compact('data', 'fields', 'parentId', 'pathToString', 'areaLevelOptions'));
	}
	
	public function areasEducationView($controller, $params) {
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		$parentId = isset($params->named['parent']) ? $params->named['parent'] : 0;
		$data = $this->findById($id);
		
		$fields = $this->getDisplayFields($controller);
		$controller->set(compact('data', 'fields', 'parentId'));
	}
	
	public function areasEducationEdit($controller, $params) {
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
					return $controller->redirect(array('action' => 'areasEducationView', 'parent' => $parentId, $id));
				}
			} else {
				$controller->request->data = $data;
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'areasEducation', 'parent' => $parentId));
		}
	}

	public function fetchSubLevelList($parentId) {

		$children = $this->find('all', array(
			'conditions' => array('AreaEducation.parent_id' => $parentId ),
			'fields' => 'GROUP_CONCAT(AreaEducation.id) as children'
		));
		$data = $children[0][0]['children'];
		return $data;
	}

	public function getChildren($parentId, $str=null) {
		$children = $this->find('all', array('conditions' => array('AreaEducation.parent_id' => $parentId ), 'fields' => 'GROUP_CONCAT(AreaEducation.id) as children'));
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
		return $data['AreaEducation']['name'];
	}
}