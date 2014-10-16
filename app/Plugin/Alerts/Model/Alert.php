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
class Alert extends AlertsAppModel {
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name'
			)
		),
		'threshold' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Threshold'
			)
		),
		'subject' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Subject'
			)
		),
		'message' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Message'
			)
		)
	);
	
    public function beforeAction() {
		parent::beforeAction();
		
		$this->setFieldOrder('name', 1);
		$this->setFieldOrder('threshold', 2);
		
		$statusOptions = $this->controller->Option->get('enableOptions');
		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $statusOptions;
		$this->setFieldOrder('status', 3);
		
		$methodOptions = $this->controller->Option->get('alertMethod');
		$this->fields['method']['type'] = 'select';
		$this->fields['method']['options'] = $methodOptions;
		$this->setFieldOrder('method', 4);
		
		$this->setFieldOrder('subject', 5);
		$this->setFieldOrder('message', 6);
	}
	
	public function afterAction() {
		parent::afterAction();
	}
	
	public function index(){
		$alias = $this->alias;
		
		$this->recursive = 0;
		$data = $this->find('all', array(
			'fields' => array($alias . '.*')
		));
		
		$this->setVar(compact('data'));
	}
	
	public function add(){
		$alias = $this->alias;
		
		$statusOptions = $this->controller->Option->get('enableOptions');
		$methodOptions = $this->controller->Option->get('alertMethod');
		
		$SecurityRole = ClassRegistry::init('SecurityRole');
		$roleOptions = $SecurityRole->getAllRoleOptions();
		
		if($this->request->is(array('post', 'put'))){
			$alertData = $this->request->data[$alias];
			$rolesData = $alertData['roles'];
			unset($alertData['roles']);
			
			if ($this->save($alertData)) {
				pr($rolesData);die;
			}
		}
		
		$this->setVar(compact('statusOptions', 'methodOptions', 'roleOptions'));
	}
	
	public function edit(){
		
	}
}
