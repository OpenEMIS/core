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

class StaffHealth extends StaffAppModel {
	//public $useTable = 'staff_healths';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		//'Staff',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	/*public $validate = array(
		'doctor_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Name.'
			)
		),
		'doctor_contact' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Contact Number.'
			)
		)
	);*/
	
	public $bloodTypeOptions = array('O+' => 'O+', 'O-' => 'O-', 'A+' => 'A+', 'A-' => 'A-', 'B+'=>'B+' ,'B-' => 'B-', 'AB+' => 'AB+', 'AB-' => 'AB-');
	public $booleanOptions = array('No', 'Yes');
	
	public function health($controller, $params) {
		$this->render = false;
		return $controller->redirect(array('action' =>'health_view'));
	}
	
	public function health_view($controller, $params) {
		$controller->Navigation->addCrumb('Health - Overview');
        $data = $this->findByStaffId($controller->staffId);
	
		$controller->set('data', $data);
		$controller->set('modelName', $this->name);
	}
	
	public function health_edit($controller, $params){
		$controller->Navigation->addCrumb('Health - Edit Overview');
		$controller->set('bloodTypeOptions', $this->bloodTypeOptions);
		$controller->set('booleanOptions', $this->booleanOptions);
		$controller->set('modelName', $this->name);
		//pr($controller->request);
		if($controller->request->is('get')){
			$this->recursive = -1;
			$data = $this->findByStaffId($controller->staffId);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			if(empty($controller->staffId)){
				return $controller->redirect(array('action' => 'view'));
			}
			
			if($this->save($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
				}
				else{
					$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
				}
				return $controller->redirect(array('action' => 'health_view'));
			}
		}
	}
}
