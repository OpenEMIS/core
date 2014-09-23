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

class SecurityGroup extends AppModel {
	public $actsAs = array('ControllerAction2');
	
	public $belongsTo = array(
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
		'SecurityGroupUser',
		'SecurityGroupArea',
		'SecurityGroupInstitutionSite',
		'SecurityRole'
	);
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		)
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Groups');
		
		$this->fields['SecurityGroupArea'] = array(
			'type' => 'element',
			'element' => '../Security/SecurityGroup/area',
			'class' => 'col-md-8',
			'order' => 1,
			'visible' => true
		);
		$this->fields['SecurityGroupInstitutionSite'] = array(
			'type' => 'element',
			'element' => '../Security/SecurityGroup/institution_site',
			'class' => 'col-md-8',
			'order' => 2,
			'visible' => true
		);
		$this->fields['SecurityGroupUser'] = array(
			'type' => 'element',
			'element' => '../Security/SecurityGroup/security_user',
			'class' => 'col-md-8',
			'order' => 3,
			'visible' => true
		);
		$this->setFieldOrder('SecurityGroupArea', 2);
		$this->setFieldOrder('SecurityGroupInstitutionSite', 3);
		$this->setFieldOrder('SecurityGroupUser', 4);
	}
	
	public function ajaxGetAccessOptionsRow($type, $id=0) {
		$this->controller->layout = 'ajax';
		$params = $this->controller->params;
		$index = $params->query['index'];
		$exclude = isset($params->query['exclude']) ? $params->query['exclude'] : array();
		
		$models = array(
			array('SecurityGroupArea', 'area_id'),
			array('SecurityGroupInstitutionSite', 'institution_site_id'),
			array('SecurityGroupUser', 'security_user_id')
		);
		
		$attr = $models[$type];
		$this->Session->write($this->alias.'.autocomplete.exclude.'.$attr[0], $exclude);
		
		if ($attr[0] == 'SecurityGroupUser') {
			$groupIds = array(-1, 0);
			if ($id != 0) {
				$groupIds[] = $id;
			}
			$roleOptions = $this->SecurityRole->find('list', array('conditions' => array('security_group_id' => $groupIds)));
			$this->setVar('roleOptions', $roleOptions);
		}
		
		$this->setVar(compact('attr', 'index', 'type'));
	}
	
	public function autocomplete($model) {
		$this->render = false;
		$search = $this->controller->params->query['term'];
		$search = sprintf('%%%s%%', $search);
		$exclude = array();
		if ($this->Session->check($this->alias.'.autocomplete.exclude.'.$model)) {
			$exclude = $this->Session->read($this->alias.'.autocomplete.exclude.'.$model);
		}
		$data = $this->{$model}->autocomplete($search, $exclude);
		return json_encode($data);
	}
	
	public function index() {
		App::uses('Sanitize', 'Utility');
		
		$page = isset($this->controller->params->named['page']) ? $this->controller->params->named['page'] : 1;
		
		$selectedYear = "";
		$selectedProgramme = "";
		$searchField = "";
		$orderBy = 'SecurityGroup.name';
		$order = 'asc';
		$prefix = 'SecurityGroup.Search.%s';
		if($this->request->is('post')) {
			$searchField = Sanitize::escape(trim($this->request->data['SecurityGroup']['SearchField']));
			if(isset($this->request->data['SecurityGroup']['orderBy'])) {
				$orderBy = $this->request->data['SecurityGroup']['orderBy'];
			}
			if(isset($this->request->data['SecurityGroup']['order'])) {
				$order = $this->request->data['SecurityGroup']['order'];
			}
		}
		$conditions = array(
			'search' => $searchField, 
			'super_admin' => $this->controller->Auth->user('super_admin')==1,
			'user_id' => $this->controller->Auth->user('id')
		);
		
		$this->controller->paginate = array('order' => sprintf('%s %s', $orderBy, $order), 'limit' => 15, 'maxLimit' => 15);
		$data = $this->controller->paginate('SecurityGroup', $conditions);
		
		foreach($data as &$group) {
			$obj = $group['SecurityGroup'];
			$count = $this->SecurityGroupUser->find('count', array('conditions' => array('SecurityGroupUser.security_group_id' => $obj['id'])));
			$group['SecurityGroup']['count'] = $count;
		}
		
		$this->setVar('searchField', $searchField);
		$this->setVar('page', $page);
		$this->setVar('orderBy', $orderBy);
		$this->setVar('order', $order);
		$this->setVar('data', $data);
		$this->setVar('groupCount', $this->paginateCount($conditions));
	}
	
	public function view($id) {
		if ($this->exists($id)) {
			$this->recursive = 0;
			$data = $this->findById($id);
			$data[$this->alias]['SecurityGroupArea'] = $this->SecurityGroupArea->findAllBySecurityGroupId($id, null, array('Area.order'));
			$data[$this->alias]['SecurityGroupInstitutionSite'] = $this->SecurityGroupInstitutionSite->findAllBySecurityGroupId($id, null, array('InstitutionSite.name'));
			$data[$this->alias]['SecurityGroupUser'] = $this->SecurityGroupUser->findAllBySecurityGroupId($id, null, array('SecurityUser.first_name'));
			$levels = $this->SecurityGroupArea->Area->AreaLevel->find('list');
			
			$this->Session->write($this->alias.'.id', $id);
			$this->setVar(compact('data', 'levels'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => get_class($this)));
		}
	}
	
	public function add() {
		if($this->request->is(array('post', 'put'))) {
			$models = array(
				'SecurityGroupArea' => 'area_id',
				'SecurityGroupInstitutionSite' => 'institution_site_id',
				'SecurityGroupUser' => 'security_user_id'
			);
			
			foreach ($models as $model => $attr) {
				if (isset($this->request->data[$model])) {
					$data = $this->request->data[$model];
					foreach ($data as $i => $obj) {
						if (empty($obj[$attr])) {
							unset($this->request->data[$model][$i]);
						}
					}
				}
			}
			
			$data = array();
			$models[$this->alias] = 'id';
			foreach ($models as $model => $attr) {
				if (!empty($this->request->data[$model])) {
					$data[$model] = $this->request->data[$model];
				}
			}
			
			if ($this->saveAll($data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => get_class($this), 'view', $this->getLastInsertID()));
			} else {
				$this->Message->alert('general.add.failed');
			}
		}
	}
	
	public function edit($id) {
		if ($this->exists($id)) {
			$this->recursive = 0;
			$data = $this->findById($id);
			$data[$this->alias]['SecurityGroupArea'] = $this->SecurityGroupArea->findAllBySecurityGroupId($id, null, array('Area.order'));
			$data[$this->alias]['SecurityGroupInstitutionSite'] = $this->SecurityGroupInstitutionSite->findAllBySecurityGroupId($id, null, array('InstitutionSite.name'));
			$data[$this->alias]['SecurityGroupUser'] = $this->SecurityGroupUser->findAllBySecurityGroupId($id, null, array('SecurityUser.first_name'));
			
			if ($this->request->is(array('post', 'put'))) {
				$models = array(
					'SecurityGroupArea' => 'area_id',
					'SecurityGroupInstitutionSite' => 'institution_site_id',
					'SecurityGroupUser' => 'security_user_id'
				);
				
				foreach ($models as $model => $attr) {
					if (isset($this->request->data[$model])) {
						$data = $this->request->data[$model];
						foreach ($data as $i => $obj) {
							if (empty($obj[$attr])) {
								unset($this->request->data[$model][$i]);
							}
						}
					}
				}
				
				$data = array();
				$models[$this->alias] = 'id';
				foreach ($models as $model => $attr) {
					if (!empty($this->request->data[$model])) {
						$data[$model] = $this->request->data[$model];
					}
				}
				
				// remove all related records from groups and re-insert
				foreach ($models as $model => $attr) {
					if ($this->alias == $model) continue;
					$this->{$model}->recursive = -1;
					$this->{$model}->deleteAll(array("$model.security_group_id" => $id), false);
				}
				if ($this->saveAll($data)) {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => get_class($this), 'view', $id));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$this->request->data = $data;
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => get_class($this)));
		}
	}
	
	public function remove() {
		if ($this->Session->check($this->alias . '.id')) {
			$id = $this->Session->read($this->alias . '.id');
			if($this->delete($id)) {
				$hasMany = $this->hasMany;
				foreach ($hasMany as $model => $attr) {
					$this->{$model}->recursive = -1;
					$this->{$model}->deleteAll(array("$model.security_group_id" => $id), false);
				}
				$this->Message->alert('general.delete.success');
			} else {
				$this->Message->alert('general.delete.failed');
			}
			$this->Session->delete($this->alias . '.id');
			return $this->redirect(array('action' => get_class($this)));
		}
	}
	
	public function getGroupOptions($userId=false) {
		$options = array(
			'recursive' => -1,
			'fields' => array('SecurityGroup.id', 'SecurityGroup.name'),
			'order' => array('SecurityGroup.name')
		);
		
		if(!is_bool($userId) && $userId > 0) {
			$options['joins'] = array(
				array(
					'table' => 'security_group_users',
					'alias' => 'SecurityGroupUser',
					'conditions' => array(
						'SecurityGroupUser.security_group_id = SecurityGroup.id',
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				)
			);
		}
		$data = $this->find('list', $options);
		return $data;
	}
	
	public function getGroupsByUser($userId) {
		$data = $this->find('all', array(
			'fields' => array('SecurityGroup.name'),
			'joins' => array(
				array(
					'table' => 'security_group_users',
					'alias' => 'SecurityGroupUser',
					'conditions' => array(
						'SecurityGroupUser.security_group_id = SecurityGroup.id',
						'SecurityGroupUser.security_user_id = ' . $userId
					)
				)
			),
			'order' => array('SecurityGroup.name'),
			'group' => array('SecurityGroup.id')
		));
		return $data;
	}
	
	public function paginateJoins(&$conditions) {
		$joins = array();
		
		if($conditions['super_admin'] == false) {
			$joins[] = array(
				'table' => 'security_group_users',
				'alias' => 'SecurityGroupUser',
				'type' => 'LEFT',
				'conditions' => array('SecurityGroupUser.security_group_id = SecurityGroup.id')
			);
		}
		unset($conditions['super_admin']);
		unset($conditions['user_id']);
		return $joins;
	}
	
	public function paginateConditions(&$conditions) {
		$or = array();
		if(isset($conditions['search'])) {
			if(!empty($conditions['search'])) {
				$search = '%' . $conditions['search'] . '%';
				$or['SecurityGroup.name LIKE'] = $search;
			}
			unset($conditions['search']);
		}
		if($conditions['super_admin'] == false) {
			$or['SecurityGroup.created_user_id'] = $conditions['user_id'];
			$or['SecurityGroupUser.security_user_id'] = $conditions['user_id'];
		}
		if(!empty($or)) {
			$conditions['OR'] = $or;
		}
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		$this->paginateConditions($conditions);
		$data = $this->find('all', array(
			'fields' => array('SecurityGroup.id', 'SecurityGroup.name'),
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $conditions,
			'group' => array('SecurityGroup.id'),
			'limit' => $limit,
			'offset' => (($page-1)*$limit),
			'order' => $order
		));
		return $data;
	}
	
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		$this->paginateConditions($conditions);
		$count = $this->find('count', array(
			'joins' => $this->paginateJoins($conditions),
			'conditions' => $conditions
		));
		return $count;
	}
}