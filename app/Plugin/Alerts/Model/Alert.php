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

class Alert extends AlertsAppModel {
	public $actsAs = array(
		'ControllerAction2'
	);
	
	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $hasMany = array('Alerts.AlertRole');
	
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
		$this->Navigation->addCrumb('Alerts');
		
		$this->recursive = 0;
		$data = $this->find('all', array(
			'fields' => array($alias . '.*')
		));
		
//		pr($this->getAlertByName('Student Absent'));
//		$alertAttendance = $this->getAlertWithRolesByName('Student Absent');
//		$roleIds = array();
//		foreach($alertAttendance AS $row){
//			$roleIds[] = $row['AlertRole']['security_role_id'];
//		}
//		
//		$InstitutionSiteStudentAbsence = ClassRegistry::init('InstitutionSiteStudentAbsence');
//		$studentIds = $InstitutionSiteStudentAbsence->getStudentListForAlert(1);
//		//pr($studentIds);
//		//pr($roleIds);
//		
//		$SecurityRole = ClassRegistry::init('SecurityRole');
//		$users = $SecurityRole->find('all', array(
//				'recursive' => -1,
//				'fields' => array('SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.email', 'Student.first_name', 'Student.last_name'),
//				'joins' => array(
//					array(
//						'table' => 'security_groups',
//						'alias' => 'SecurityGroup',
//						'conditions' => array(
//							'SecurityRole.security_group_id = SecurityGroup.id'
//						)
//					),
//					array(
//						'table' => 'security_group_users',
//						'alias' => 'SecurityGroupUser',
//						'conditions' => array(
//							'SecurityGroup.id = SecurityGroupUser.security_group_id'
//						)
//					),
//					array(
//						'table' => 'security_users',
//						'alias' => 'SecurityUser',
//						'conditions' => array(
//							'SecurityGroupUser.security_user_id = SecurityUser.id'
//						)
//					),
//					array(
//						'table' => 'security_group_institution_sites',
//						'alias' => 'SecurityGroupInstitutionSite',
//						'conditions' => array(
//							'SecurityGroup.id = SecurityGroupInstitutionSite.security_group_id'
//						)
//					),
//					array(
//						'table' => 'institution_site_students',
//						'alias' => 'InstitutionSiteStudent',
//						'conditions' => array(
//							'InstitutionSiteStudent.institution_site_id = SecurityGroupInstitutionSite.institution_site_id'
//						)
//					),
//					array(
//						'table' => 'students',
//						'alias' => 'Student',
//						'conditions' => array(
//							'InstitutionSiteStudent.student_id = Student.id',
//							'Student.id' => $studentIds
//						)
//					)
//				),
//				'conditions' => array('SecurityRole.id' => $roleIds),
//				'group' => array('SecurityUser.id', 'Student.id')
//		));
//		
//		//pr($users);
//		
		$this->setVar(compact('data'));
	}
	
	public function add(){
		$alias = $this->alias;
		$this->Navigation->addCrumb('Alerts', array('action' => 'Alert'));
		$this->Navigation->addCrumb('Add');
		//pr(String::uuid());die;
		
		$statusOptions = $this->controller->Option->get('enableOptions');
		$methodOptions = $this->controller->Option->get('alertMethod');
		
		$SecurityRole = ClassRegistry::init('SecurityRole');
		$roleOptions = $SecurityRole->getAllRoleOptions();
		
		if($this->request->is(array('post', 'put'))){
			$alertData = $this->request->data[$alias];
			$rolesData = $alertData['roles'];
			unset($alertData['roles']);
			
			if ($this->save($alertData)) {
				$alertId = $this->getLastInsertId();
				$alertRoleData = array();
				foreach($rolesData AS $roleId){
					$alertRoleData[] = array(
						'id' => String::uuid(),
						'alert_id' => $alertId,
						'security_role_id' => $roleId
					);
				}
				
				$this->AlertRole->saveMany($alertRoleData);
				
				$this->Message->alert('general.add.success');
				return $this->redirect(array('action' => get_class($this)));
			}else{
				$this->Message->alert('general.add.failed');
			}
		}
		
		$this->setVar(compact('statusOptions', 'methodOptions', 'roleOptions'));
	}
	
	public function edit($id=0){
		$alias = $this->alias;
		$this->Navigation->addCrumb('Alerts', array('action' => 'Alert'));
		$this->Navigation->addCrumb('Edit');
		$data = $this->findById($id);
		
		$statusOptions = $this->controller->Option->get('enableOptions');
		$methodOptions = $this->controller->Option->get('alertMethod');
		
		$SecurityRole = ClassRegistry::init('SecurityRole');
		$roleOptions = $SecurityRole->getAllRoleOptions();
		
		if($this->request->is(array('post', 'put'))){
			$alertData = $this->request->data[$alias];
			$rolesData = $alertData['roles'];
			unset($alertData['roles']);
			
			if ($this->save($alertData)) {
				$alertRoleData = array();
				foreach($rolesData AS $roleId){
					$alertRoleData[] = array(
						'id' => String::uuid(),
						'alert_id' => $id,
						'security_role_id' => $roleId
					);
				}
				
				$this->AlertRole->deleteAll(array('alert_id' => $id), false);
				$this->AlertRole->saveMany($alertRoleData);
				
				$this->Message->alert('general.edit.success');
				return $this->redirect(array('action' => get_class($this), 'view', $id));
			}else{
				$this->Message->alert('general.edit.failed');
			}
		}else{
			$this->request->data = $data;
			
			$roleIds = array();
			$roleData = $this->AlertRole->find('list', array(
				'recursive' => -1,
				'fields' => array('security_role_id'),
				'conditions' => array('alert_id' => $id)
			));
			
			foreach($roleData AS $existingRoldId){
				$roleIds[] = $existingRoldId;
			}
		}
		
		$this->setVar(compact('id', 'statusOptions', 'methodOptions', 'roleOptions', 'roleIds'));
	}
	
	public function view($id=0){
		$data = $this->findById($id);
		$this->Navigation->addCrumb('Alerts', array('action' => 'Alert'));
		$this->Navigation->addCrumb('Alert Details');

		$roles = $this->AlertRole->getRolesByAlertId($id);
		
		$this->setVar(compact('id', 'data', 'roles'));
	}
	
	public function getAlertWithRolesByName($name){
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('Alert.*', 'AlertRole.security_role_id'),
			'joins' => array(
				array(
					'table' => 'alert_roles',
					'alias' => 'AlertRole',
					'conditions' => array('Alert.id = AlertRole.alert_id')
				)
			),
			'conditions' => array('Alert.name' => $name)
		));
		
		return $data;
	}
	
	public function getAlertByName($name){
		$data = $this->find('first', array(
			'recursive' => -1,
			'conditions' => array(
				'Alert.name' => $name,
				'Alert.status' => 1
			)
		));
		
		return $data;
	}
}
