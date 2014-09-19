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

class SecurityUserAccess extends AppModel {
	public $useTable = 'security_user_access';
	
	public $actsAs = array('ControllerAction2');
	
	public $belongsTo = array(
		'SecurityUser'
	);
	
	public function beforeAction() {
		parent::beforeAction();
	}
	
	public function isAccessExists($conditions) {
		$data = $this->find('first', array(
			'conditions' => $conditions
		));
		return $data;
	}
	
	public function getAccess($userId) {
		$modules = array(
			//'Teacher' => ClassRegistry::init('Teachers.Teacher'),
			'Staff' => ClassRegistry::init('Staff.Staff'),
			'Student' => ClassRegistry::init('Students.Student')
		);
		$data = $this->find('all', array(
			'conditions' => array('security_user_id' => $userId),
			'order' => array('table_name')
		));
		
		foreach($data as &$row) {
			$obj = &$row['SecurityUserAccess'];
			$table = $obj['table_name'];
			$id = $obj['table_id'];
			$user = $modules[$table]->find('first', array(
				'fields' => array('first_name', 'last_name', 'identification_no'),
				'recursive' => -1,
				'conditions' => array($table . '.id' => $id)
			));
			if($user) {
				$obj['name'] = $user[$table]['first_name'] . ' ' . $user[$table]['last_name'];
				$obj['identification_no'] = $user[$table]['identification_no'];
			}
		}
		return $data;
	}
	
	public function view() {
		$this->Navigation->addCrumb('Users', array('controller' => 'Security', 'action' => 'users'));
		if($this->Session->check('SecurityUserId')) {
			$userId = $this->Session->read('SecurityUserId');
			$this->SecurityUser->formatResult = true;
			$data = $this->SecurityUser->find('first', array('recursive' => 0, 'conditions' => array('SecurityUser.id' => $userId)));
			$data['access'] = $this->getAccess($userId);
			$name = $data['first_name'] . ' ' . $data['last_name'];
			$moduleOptions = array('Student' => __('Student'), /*'Teacher' => __('Teacher'), */'Staff' => __('Staff'));
			$this->setVar('data', $data);
			$this->setVar('moduleOptions', $moduleOptions);
			$this->Navigation->addCrumb($name);
		} else {
			$this->redirect(array('action' => 'users'));
		}
	}
	
	public function add() {
		$this->render = false;
		if ($this->request->is(array('post', 'put'))) {
			$postData = $this->request->data['SecurityUserAccess'];
			
			unset($postData['search']);
			//pr($postData);die;
			
			if(empty($postData['table_name']) || empty($postData['table_id'])){
				$this->Message->alert('general.add.failed');
				return $this->redirect(array('action' => 'SecurityUserAccess', 'view'));
			}
			
			if (!$this->isAccessExists($postData)) {
				$this->save($postData);
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => 'SecurityUserAccess', 'view'));
			} else {
				$this->Message->alert('UserAccess.add.accessExists');
				return $this->redirect(array('action' => 'SecurityUserAccess', 'view'));
			}

		}else{
			return $this->redirect(array('action' => 'SecurityUserAccess', 'view'));
		}
	}
	
	public function delete($userId=0, $tableId=0, $tableName='') {
		$this->render = false;

		if (!empty($userId) && !empty($tableId) && !empty($tableName)) {
			$conditions = array(
				'security_user_id' => $userId,
				'table_id' => $tableId,
				'table_name' => $tableName
			);
			$this->deleteAll($conditions, false);
			$this->Message->alert('general.delete.success');
			return $this->redirect(array('action' => 'SecurityUserAccess', 'view'));
		}
	}

	public function autocomplete() {
		if ($this->request->is('ajax')) {
			$this->render = false;
			
			$defaultModel = 'Student';
			$model = $this->controller->params->query['model'];
			if(empty($model)){
				$model = $defaultModel;
			}
			$search = $this->controller->params->query['term'];
			
			$data = array();
			$list = $this->controller->{$model}->autocomplete($search);
			
			foreach ($list as $obj) {
				$info = $obj[$model];
				$data[] = array(
					'label' => sprintf('%s - %s %s', $info['identification_no'], $info['first_name'], $info['last_name']),
					'value' => array('table_id' => $info['id']) 
				);
			}
			
			return json_encode($data);
		}
	}
}
