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
	public $actsAs = array('ControllerAction');
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
		$controller->Navigation->addCrumb('Roles');
		$controller->set('header', __('Roles'));
		$controller->set('fields', $this->getDisplayFields($controller));
    }
	
	public function roles($controller, $params) {
		$systemRoles = $this->getRoles(array(0, -1));
		$isSuperUser = $controller->Auth->user('super_admin')==1;
		$userId = $controller->Auth->user('id');
		$groupOptions = ClassRegistry::init('SecurityGroup')->getGroupOptions($isSuperUser ? false : $userId);
		$userRoles = array();
		$selectedGroup = 0;
		
		if(!empty($groupOptions)) {
			if(isset($params['pass'][0])) {
				$groupId = $params['pass'][0];
				$selectedGroup = array_key_exists($groupId, $groupOptions) ? $groupId : key($groupOptions);
			} else {
				$selectedGroup = key($groupOptions);
			}
			$userRoles = $this->getRoles($selectedGroup);
		}
		
		$controller->set('isSuperUser', $isSuperUser);
		$controller->set('systemRoles', $systemRoles);
		$controller->set('userRoles', $userRoles);
		$controller->set('groupOptions', $groupOptions);
		$controller->set('selectedGroup', $selectedGroup);
	}
	
	public function rolesView($controller, $params) {
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
		if ($controller->request->is(array('post', 'put'))) {
			$data = $controller->request->data;
			$data[$this->alias]['order'] = $this->find('count');
			$result = $this->save($data);
			if ($result) {
				$controller->Message->alert('general.add.success');
				return $controller->redirect(array('action' => 'rolesView', $result[$this->alias]['id']));
			}
		}
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
}
