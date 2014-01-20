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

class StudentHealthHistory extends StudentsAppModel {
	//public $useTable = 'student_health_histories';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		//'Student',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'health_condition_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Condition.'
			)
		)
	);	public $booleanOptions = array('No', 'Yes');
	
	public function health_history($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb('History');
		$controller->set('modelName', $this->name);
		$data = $this->find('all', array('conditions'=> array('student_id'=> $controller->studentId)));
		
		$HealthCondition = ClassRegistry::init('HealthCondition');
		$healthConditionsOptions = $HealthCondition->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('data', $data);
		$controller->set('healthConditionsOptions', $healthConditionsOptions);
		
	}
	
	public function health_history_view($controller, $params){
		$controller->Navigation->addCrumb('Health - View History');
		$controller->set('subheader', 'Health - View History');
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'health_history'));
		}
		
		$controller->Session->write('StudentHealthHistoryId', $id);
		$HealthCondition = ClassRegistry::init('HealthCondition');
		$healthConditionsOptions = $HealthCondition->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('data', $data);
		$controller->set('healthConditionsOptions', $healthConditionsOptions);
	}
	
	public function health_history_delete($controller, $params) {
        if($controller->Session->check('StudentId') && $controller->Session->check('StudentHealthHistoryId')) {
            $id = $controller->Session->read('StudentHealthHistoryId');
            $studentId = $controller->Session->read('StudentId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			 
			$HealthCondition = ClassRegistry::init('HealthCondition');
			$healthConditionsOptions = $HealthCondition->find('first', array('conditions'=> array('id' => $data[$this->name]['health_condition_id'])));
	
            $name = $healthConditionsOptions['HealthCondition']['name'];
			
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->Session->delete('StudentHealthHistoryId');
            $controller->redirect(array('action' => 'health_history'));
        }
    }
	
	public function health_history_add($controller, $params) {
		$controller->Navigation->addCrumb('Health - Add History');
		$controller->set('subheader', 'Health - Add History');
		$this->setup_add_edit_form($controller, $params);
	}
	
	public function health_history_edit($controller, $params) {
		$controller->Navigation->addCrumb('Health - Edit History');
		$controller->set('subheader', 'Health - Edit History');
		$this->setup_add_edit_form($controller, $params);
		
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$controller->set('modelName', $this->name);
		
		$HealthCondition = ClassRegistry::init('HealthCondition');
		$healthConditionsOptions = $HealthCondition->find('list', array('fields'=> array('id', 'name')));
		$controller->set('healthConditionsOptions', $healthConditionsOptions);
		$controller->set('booleanOptions', $this->booleanOptions);
		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
		}
		else{
			$controller->request->data[$this->name]['student_id'] = $controller->studentId;
			if($this->save($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
				}
				else{
					$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
				}
				return $controller->redirect(array('action' => 'health_history'));
			}
		}
	}
}
