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

class SecurityRole extends AppModel {
	public $actsAs = array('ControllerAction', 'Reorder');
	public $belongsTo = array('SecurityGroup');
	public $hasMany = array('SecurityRoleFunction', 'SecurityGroupUser');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		),
		'security_group_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a group'
			)
		)
	);
	
	public function getDisplayFields($controller) {
		$isSuperUser = $controller->Auth->user('super_admin')==1;
		$userId = $controller->Auth->user('id');
		$groupList = ClassRegistry::init('SecurityGroup')->getGroupOptions($isSuperUser ? false : $userId);
		$yesnoOptions = $controller->Option->get('yesno');
		$groupOptions = array();
		
		if ($isSuperUser) {
			$system = __('System Defined');
			$user = __('User Defined');
			
			$groupOptions[0] = $system;
			$groupOptions[$user] = $groupList;
			$groupList[0] = $system;
		} else {
			$groupOptions = $groupList;
		}
		
		$fields = array(
			'model' => $this->alias,
			'fields' => array(
				array('field' => 'name'),
				array('field' => 'security_group_id', 'type' => 'select', 'options' => $groupList),
				array('field' => 'visible', 'type' => 'select', 'options' => $yesnoOptions),
				array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
				array('field' => 'modified', 'edit' => false),
				array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
				array('field' => 'created', 'edit' => false)
			)
		);
		$controller->set('groupOptions', $groupOptions);
		$controller->set('yesnoOptions', $yesnoOptions);
		return $fields;
	}
	
	public function beforeAction($controller, $action) {
        parent::beforeAction($controller, $action);
		$controller->set('header', __('Roles'));
		$controller->set('fields', $this->getDisplayFields($controller));
    }
	
	public function roles($controller, $params) {
		$controller->Navigation->addCrumb('Roles');
		
		$systemRoles = $this->getRoles(array(0, -1));
		$isSuperUser = $controller->Auth->user('super_admin')==1;
		if(!$isSuperUser){
			return $controller->redirect(array('action' => 'rolesUserDefined'));
		}
		
		$currentTab = 'System Defined Roles';
		$controller->set(compact('isSuperUser', 'systemRoles', 'currentTab'));
	}
	
	public function rolesUserDefined($controller, $params) {
		$controller->Navigation->addCrumb('Roles');
		
		$isSuperUser = $controller->Auth->user('super_admin')==1;
		$userId = $controller->Auth->user('id');
		$groupOptions = ClassRegistry::init('SecurityGroup')->getGroupOptions($isSuperUser ? false : $userId);
		$userRoles = array();
		$selectedGroup = 0;
		
		if(!empty($groupOptions)) {
			if(!empty($params['pass'][0])) {
				$groupId = $params['pass'][0];
				$selectedGroup = array_key_exists($groupId, $groupOptions) ? $groupId : key($groupOptions);
			} else {
				$selectedGroup = key($groupOptions);
			}
			$userRoles = $this->getRoles($selectedGroup);
		}
		
		$currentTab = 'User Defined Roles';
		$controller->set(compact('isSuperUser', 'userRoles', 'groupOptions', 'selectedGroup', 'currentTab'));
	}
	
	public function rolesView($controller, $params) {
		$controller->Navigation->addCrumb('Roles');
		
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		if ($this->exists($id)) {
			$data = $this->findById($id);
			$selectedGroup = $data[$this->alias]['security_group_id'];
			if ($selectedGroup == -1) {
				$controller->Message->alert('general.notEditable');
				return $controller->redirect(array('action' => 'roles'));
			} else {
				$controller->set(compact('data', 'selectedGroup'));
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'roles'));
		}
	}
	
	public function rolesEdit($controller, $params) {
		$controller->Navigation->addCrumb('Roles');
		
		$id = isset($params->pass[0]) ? $params->pass[0] : 0;
		if ($this->exists($id)) {
			$data = $this->findById($id);
			$selectedGroup = $data[$this->alias]['security_group_id'];
			if ($selectedGroup == -1) {
				$controller->Message->alert('general.notEditable');
				return $controller->redirect(array('action' => 'roles'));
			} else {
				if ($controller->request->is(array('post', 'put'))) {
					$data = $controller->request->data;
					if ($this->save($data)) {
						$controller->Message->alert('general.edit.success');
						return $controller->redirect(array('action' => 'rolesView', $id));
					} else {
						$controller->Message->alert('general.edit.failed');
					}
				} else {
					$controller->request->data = $data;
				}
			}
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'roles'));
		}
	}
	
	public function rolesAdd($controller, $params) {
		$controller->Navigation->addCrumb('Roles');
		
		$roleType = 'system_defined';
		$selectedGroup = 0;
		
		if(isset($params['pass'][0])) {
			$type = $params['pass'][0];
			if($type == 'user_defined'){
				$roleType = $type;
			}
			
			if(isset($params['pass'][1])) {
				$selectedGroup = $params['pass'][1];
			}
		}
		
		if ($controller->request->is(array('post', 'put'))) {
			$data = $controller->request->data;
			$dataGroupId = $data['SecurityRole']['security_group_id'];
			
			if($dataGroupId == 0){
				$maxOrderArr = $this->find('first', array(
					'fields' => array('MAX(SecurityRole.order) AS max_order'), 
					'conditions' => array('SecurityRole.security_group_id' => array(-1, 0)),
					'order' => array('SecurityRole.order DESC')
				));
				$newOrder = (empty($maxOrderArr[0]['max_order']) ? 0 : $maxOrderArr[0]['max_order']) + 1;
			}else if($dataGroupId > 0){
				$maxOrderArr = $this->find('first', array(
					'fields' => array('MAX(SecurityRole.order) AS max_order'), 
					'conditions' => array('SecurityRole.security_group_id' => $dataGroupId),
					'order' => array('SecurityRole.order DESC')
				));
				$newOrder = (empty($maxOrderArr[0]['max_order']) ? 0 : $maxOrderArr[0]['max_order']) + 1;
			}else{
				$newOrder = $this->find('count');
			}
			
			$data[$this->alias]['order'] = $newOrder;
			$result = $this->save($data);
			if ($result) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'rolesView', $result[$this->alias]['id']));
			}
		}
		$controller->set(compact('roleType', 'selectedGroup'));
	}
	
	public function getGroupAdministratorRole() {
		$roleId = 1; // Role Id for Group Administrator is always 1
		$data = $this->find('first', array('SecurityRole.id' => $roleId));
		return $data;
	}
	
	public function getGroupName($roleId, $userId=false) {
		$this->formatResult = true;
		$joins = array(
			array(
				'table' => 'security_groups',
				'alias' => 'SecurityGroup',
				'conditions' => array('SecurityGroup.id = SecurityRole.security_group_id')
			)
		);
		
		if($userId != false) {
			$joins[] = array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'conditions' => array(
					'SecurityGroupUser.security_group_id = SecurityGroup.id',
					'SecurityGroupUser.security_user_id = ' . $userId
				)
			);
		}
		
		$data = $this->find('first', array(
			'recursive' => -1,
			'fields' => array('SecurityGroup.id', 'SecurityGroup.name'),
			'joins' => $joins,
			'conditions' => array('SecurityRole.id' => $roleId)
		));
		return $data;
	}
	
	public function getRoles($groupId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'conditions' => array('SecurityRole.security_group_id' => $groupId),
			'order' => array('SecurityRole.order')
		));
		return $data;
	}
	
	public function getRoleOptions($groupId, $userId=false, $optGroup=false) {
		$this->formatResult = true;
		$fields = array('SecurityRole.id', 'SecurityRole.name');
		$conditions = array();
		$type = 'list';
		
		if($userId!==false) {
			$conditions = array(
				'OR' => array(
					'SecurityRole.security_group_id' => $groupId, 
					'SecurityRole.security_group_id <=' => 0
				),
				'AND' => array('SecurityRole.visible' => 1)
			);
			$conditions['AND'][] = sprintf('NOT EXISTS (
				SELECT id FROM security_group_users
				WHERE security_role_id = SecurityRole.id
				AND security_group_id = %d
				AND security_user_id = %d)', $groupId, $userId);
		} else {
			$conditions = array('SecurityRole.security_group_id' => $groupId, 'SecurityRole.visible' => 1);
		}
		
		if($optGroup) {
			$type = 'all';
			$fields[] = 'SecurityRole.security_group_id';
		}
		
		$list = $this->find($type, array(
			'recursive' => -1,
			'fields' => $fields,		
			'conditions' => $conditions,
			'order' => array('SecurityRole.order')
		));
		
		$data = array();
		if($optGroup) {
			$systemDefined = __('System Defined Roles');
			$userDefined = __('User Defined Roles');
			foreach($list as $obj) {
				$roleType = $obj['security_group_id'] > 0 ? $userDefined : $systemDefined;
				if(!array_key_exists($roleType, $data)) {
					$data[$roleType] = array();
				}
				$data[$roleType][$obj['id']] = $obj['name'];
			}
		} else {
			$data = $list;
		}
		return $data;
	}
	
	public function getRubricRoleOptions(){
		$this->formatResult = true;
		$options['fields'] = array('SecurityRole.id', 'SecurityRole.name');
		$options['conditions'] = array('SecurityRole.name' => array('QA Assessors','ECE Assessors'), 'SecurityRole.visible' => 1);
		
		$data = $this->find('list', $options);
		
		return $data;
	}
	
	public function getAllRoleOptions(){
		$this->formatResult = true;
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('id', 'name', 'security_group_id'),
			'conditions' => array(
				'visible' => 1
			),
			'order' => array('security_group_id', 'order')
		));
		
		$data = array();
		foreach($list AS $row){
			$id = $row['id'];
			if($row['security_group_id'] == -1){
				$data[$id] = __('System') . ' - ' . $row['name'];
			}else{
				$data[$id] = $row['name'];
			}
		}
		
		return $data;
	}
	
	public function getUsersByRole($roleId){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT SecurityUser.id', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.email'),
			'joins' => array(
				array(
					'table' => 'security_groups',
					'alias' => 'SecurityGroup',
					'conditions' => array('SecurityRole.security_group_id = SecurityGroup.id')
				),
				array(
					'table' => 'security_group_users',
					'alias' => 'SecurityGroupUser',
					'conditions' => array('SecurityGroup.id = SecurityGroupUser.security_group_id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('SecurityGroupUser.security_user_id = SecurityUser.id')
				)
			),
			'conditions' => array(
				'SecurityRole.id' => $roleId
			)
		));
		
		return $data;
	}
	
	public function rolesReorder($controller, $params) {
		$type = $params['pass'][0];
		$isSuperUser = $controller->Auth->user('super_admin')==1;
		$userId = $controller->Auth->user('id');
		$roles = array();
		$groupOptions = array();
		$selectedGroup = 0;
		
		if($type == 'user_defined'){
			$groupOptions = ClassRegistry::init('SecurityGroup')->getGroupOptions($isSuperUser ? false : $userId);

			if(!empty($groupOptions)) {
				if(!empty($params['pass'][1])) {
					$groupId = $params['pass'][1];
					$selectedGroup = array_key_exists($groupId, $groupOptions) ? $groupId : key($groupOptions);
				} else {
					$selectedGroup = key($groupOptions);
				}
				$roles = $this->getRoles($selectedGroup);
			}
			
			$controller->Navigation->addCrumb('User Defined Roles', array('action' => 'rolesUserDefined', $selectedGroup));
			
			$contentHeader = 'User Defined Roles';
			$currentTab = 'User Defined Roles';
		}else{
			if(!$isSuperUser){
				return $controller->redirect(array('action' => 'rolesReorder', 'user_defined', $selectedGroup));
			}
			
			$controller->Navigation->addCrumb('User Defined Roles', array('action' => 'roles'));
			
			$contentHeader = 'System Defined Roles';
			$roles = $this->getRoles(array(0, -1));
			$currentTab = 'System Defined Roles';
		}
		
		$controller->Navigation->addCrumb('Reorder');
		
		$page = 'reorder';

		$controller->set(compact('isSuperUser', 'roles', 'groupOptions', 'selectedGroup', 'contentHeader', 'page', 'currentTab'));
	}
	
	public function rolesMove($controller, $params) {
		$controller->autoRender = false;
		if ($controller->request->is(array('post', 'put'))) {
			$data = $controller->request->data;
			if(!empty($controller->params->named['security_group_id'])){
				$conditions = array('SecurityRole.security_group_id' => $controller->params->named['security_group_id']);
				$redirect = array('action' => 'rolesReorder', 'user_defined', $controller->params->named['security_group_id']);
			}else{
				$conditions = array('SecurityRole.security_group_id' => array(-1, 0));
				$redirect = array('action' => 'rolesReorder', 'system_defined');
			}
			
			$this->moveOrder($data, $conditions);
			
			return $controller->redirect($redirect);
		}
	}
}
