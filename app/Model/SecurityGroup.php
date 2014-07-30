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
		'SecurityGroupInstitutionSite'
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Groups');
	}
	
	public function ajaxGetAccessOptionsRow($type) {
		$this->controller->layout = 'ajax';
		$params = $this->controller->params;
		$index = $params->query['index'];
		$exclude = isset($params->query['exclude']) ? $params->query['exclude'] : array();
		
		$models = array(
			array('SecurityGroupArea', 'area_id'),
			array('SecurityGroupInstitutionSite', 'institution_site_id')
		);
		
		$attr = $models[$type];
		
		$this->Session->write($this->alias.'.autocomplete.exclude.'.$attr[0], $exclude);
		
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
		if ($model == 'SecurityGroupArea') {
			$list = $this->SecurityGroupArea->Area->find('all', array(
				'fields' => array('Area.id', 'Area.code', 'Area.name', 'AreaLevel.name'),
				'conditions' => array(
					'OR' => array(
						'Area.name LIKE' => $search,
						'Area.code LIKE' => $search,
						'AreaLevel.name LIKE' => $search
					),
					'Area.id NOT' => $exclude
				),
				'order' => array('AreaLevel.level', 'Area.order')
			));
			
			$data = array();
			foreach($list as $obj) {
				$area = $obj['Area'];
				$level = $obj['AreaLevel'];
				$data[] = array(
					'label' => sprintf('%s - %s (%s)', $level['name'], $area['name'], $area['code']),
					'value' => array('value-id' => $area['id'], 'area-name' => $area['name'], 'area-code' => $area['code'])
				);
			}
		}
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
		
		$this->controller->paginate = array('order' => sprintf('%s %s', $orderBy, $order));
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
	
	public function add() {
		if($this->request->is(array('post', 'put'))) {
			$areaData = $this->request->data['SecurityGroupArea'];
			foreach ($areaData as $i => $area) {
				if (empty($area['area_id'])) {
					unset($this->request->data['SecurityGroupArea'][$i]);
				}
			}
			
			$data = array();
			$models = array($this->alias, 'SecurityGroupArea', 'SecurityGroupInstitutionSite');
			foreach ($models as $model) {
				if (!empty($this->request->data[$model])) {
					$data[$model] = $this->request->data[$model];
				}
			}
			
			if ($this->saveAll($data)) {
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => get_class($this)));//, 'view', $this->getLastInsertID()));
			} else {
				$this->Message->alert('general.add.failed');
			}
		}
	}
	
	public function view($id) {
		if ($this->exists($id)) {
			$this->recursive = 0;
			$data = $this->findById($id);
			$areas = $this->SecurityGroupArea->findAllBySecurityGroupId($id, null, array('Area.order'));
			$institutions = $this->SecurityGroupInstitutionSite->findAllBySecurityGroupId($id, null, array('InstitutionSite.name'));
			$levels = $this->SecurityGroupArea->Area->AreaLevel->find('list');
			//pr($areas);
			//pr($data);die;
			$this->setVar(compact('data', 'areas', 'levels'));
		} else {
			$this->Message->alert('general.notExists');
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