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

class TrainingSession extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	public $actsAs = array('ControllerAction');

	public $trainerTypeOptions = array('1'=>'Internal', '2'=>'External');

	public $trainerTypes = array('Staff'=>'Internal', '1'=>'External');

	
	public $belongsTo = array(
		'TrainingCourse',
		'TrainingStatus',
		'TrainingProvider',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);

	public $hasMany = array(
		'TrainingSessionTrainee' => array(
			'className' => 'TrainingSessionTrainee',
			'foreignKey' => 'training_session_id',
			'dependent' => true
		),
		'TrainingSessionResult' => array(
			'className' => 'TrainingSessionResult',
			'foreignKey' => 'training_session_id',
			'dependent' => true
		)
	);
	
	public $validate = array(
		'training_course_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Course.'
			)
		),
		'start_date' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Start Date.'
			)
		),
		'end_date' => array(
            'comparison' => array(
            	'rule'=>array('field_comparison', '>=', 'start_date'), 
            	'allowEmpty'=>true,
            	'message' => 'End Date must be greater than Start Date'
            )
        ),
		'location' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Location.'
			)
		),
		'trainer' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Trainer.'
			)
		)
	);

	function field_comparison($check1, $operator, $field2) {
        foreach($check1 as $key=>$value1) {
            $value2 = $this->data[$this->alias][$field2];
            if (!Validation::comparison($value1, $operator, $value2))
                return false;
        }
        return true;
    }
		
	public $headerDefault = 'Sessions';


	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);
		$data = array();
	
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT TrainingSession.location'),
			'conditions' => array('TrainingSession.location LIKE' => $search
			),
			'order' => array('TrainingSession.location')
		));
		
		foreach($list as $obj) {
			$valField = $obj['TrainingSession']['location'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $valField)),
				'value' => array('location' => $valField)
			);
		}

		return $data;
	}

	private function getSessionResultStatus($sessionId){
		$trainingSessionResult = ClassRegistry::init('TrainingSessionResult');

		$trainingSessionResults = $trainingSessionResult->find('first', array('conditions'=>array('TrainingSessionResult.training_session_id'=>$sessionId)));


		if(!empty($trainingSessionResults)){
			if($trainingSessionResults['TrainingSessionResult']['training_status_id']!='1'){
				return '0';
			}else{
				return '2';
			}
		}

		return '1';
	}

	
	public function session($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);

		$trainingStatus = ClassRegistry::init('TrainingStatus');
		$statusOptions = $trainingStatus->find('list', array('fields'=>array('id', 'name')));
		$selectedStatus = empty($params['pass'][0])? null:$params['pass'][0];
	
		if(!empty($selectedStatus)){
			$data = $this->find('all', array('order'=> array('start_date'), 'conditions' => array('TrainingSession.training_status_id' => $selectedStatus)));
		}else{
			$data = $this->find('all', array('order'=> array('start_date'), 'conditions' => array('NOT' => array('TrainingSession.training_status_id' => '4'))));
		}

		$conditions = array();
		if(!empty($selectedStatus)){
			$conditions['TrainingSession.training_status_id'] = $selectedStatus;
		}else{
			$conditions['NOT']['TrainingSession.training_status_id'] = 4;
		}

		$data = $this->find('all', 
			array(
				'recursive' => -1, 
				'fields' => array('TrainingCourse.*', 'TrainingSession.*', 'TrainingStatus.*'),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'training_courses',
						'alias' => 'TrainingCourse',
						'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
					),
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingStatus',
						'conditions' => array('TrainingStatus.id = TrainingSession.training_status_id')
					)
				),
				'order'=> array('TrainingSession.start_date', 'TrainingSession.location', 'TrainingCourse.code', 'TrainingCourse.title',  'TrainingSession.training_status_id'), 
				'conditions' => $conditions
			)
		);


		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('statusOptions', $statusOptions);
		$controller->set('selectedStatus', $selectedStatus);
	}

	public function sessionView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'session'));
		}
		
		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
			array(
				'fields' => array('TrainingSessionTrainee.*', 'Staff.first_name', 'Staff.last_name'),
				'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$id),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'staff',
						'alias' => 'Staff',
						'conditions' => array('Staff.id = TrainingSessionTrainee.staff_id')
					)
				)
			)
		);

		$sessionEditable = $this->getSessionResultStatus($id);
		
		$controller->Session->write('TrainingSessionId', $id);
		$controller->set('trainingSessionTrainees', $trainingSessionTrainees);
		$controller->set('data', $data);
		$controller->set('sessionEditable', $sessionEditable);

		//APROVAL
		$pending = $data['TrainingSession']['training_status_id']=='2' ? 'true' : 'false';
		$controller->Workflow->getApprovalWorkflow($this->name, $pending, $id);
		$controller->set('approvalMethod', 'session');
		$controller->set('controller', 'Training');
		$controller->set('plugin', 'Training');
	}
	
	public function sessionDelete($controller, $params) {
        if($controller->Session->check('TrainingSessionId')) {
            $id = $controller->Session->read('TrainingSessionId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['TrainingSession']['training_status_id']=='1'){
	            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
				
	            $this->delete($id);
	            $controller->Utility->alert($name . ' have been deleted successfully.');
				$controller->Session->delete('TrainingSessionId');
			}
            $controller->redirect(array('action' => 'session'));
        }
    }

    public function sessionActivate($controller, $params) {
        if($controller->Session->check('TrainingSessionId')) {
            $id = $controller->Session->read('TrainingSessionId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['TrainingSession']['training_status_id']=='2'){
	            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
				
	            $this->updateAll(
	    			array('TrainingSession.training_status_id' => 3),
	    			array('TrainingSession.id '=> $id)
				);	

				$data['training_session_id'] = $id;
				$data['training_status_id'] = '1';

				$this->TrainingSessionResult->save($data);
	            $controller->Utility->alert($name . ' have been activated successfully.');
	        }
            $controller->redirect(array('action' => 'session'));
        }
    }

    public function sessionInactivate($controller, $params) {
        if($controller->Session->check('TrainingSessionId')) {
            $id = $controller->Session->read('TrainingSessionId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));

            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
			
              $this->updateAll(
    			array('TrainingSession.training_status_id' => 4),
    			array('TrainingSession.id '=> $id)
			);
            $controller->Utility->alert($name . ' have been inactivated successfully.');
            $controller->redirect(array('action' => 'session'));
        }
    }
	
	public function sessionAdd($controller, $params) {
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);

	}
	
	public function sessionEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}

	public function sessionApproval($controller, $params){
		if(!$controller->request->is('get')){
			$saveData = $controller->request->data;
			if (isset($saveData['approve'])) {
			   	$saveData['WorkflowLog']['approve'] = 1; 
			} else if (isset($saveData['reject'])) {
		      	$saveData['WorkflowLog']['approve'] = 0; 
			}
		
			if($controller->Workflow->updateApproval($saveData)){
				if($saveData['WorkflowLog']['approve']==1){
					if($controller->Workflow->getEndOfWorkflow($this->name, $saveData['WorkflowLog']['step'], $saveData['WorkflowLog']['approve'])){
						$this->id =  $saveData['WorkflowLog']['record_id'];
						$this->saveField('training_status_id', 3);
						$data['training_session_id'] = $saveData['WorkflowLog']['record_id'];
						$data['training_status_id'] = '1';

						$this->TrainingSessionResult->save($data);
					}
				}else{
					$this->id =  $saveData['WorkflowLog']['record_id'];
					$this->saveField('training_status_id', 1);
				}
				return $controller->redirect(array('action'=>'sessionView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}
	
	
	function setup_add_edit_form($controller, $params){
		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$trainingCourseOptions = $trainingCourse->find('list', array('fields'=> array('id', 'title'), 'conditions'=>array('training_status_id'=>3)));
	
		$controller->set('trainingCourseOptions', $trainingCourseOptions);

		$trainerTypeOptions = array_map('__', $this->trainerTypeOptions);
		$controller->set('trainerTypeOptions', $trainerTypeOptions);

		$controller->set('modelName', $this->name);
		
		$provider = '';

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			$sessionEditable = '1';
			if(!empty($data)){
				$sessionEditable = $this->getSessionResultStatus($id);
				if(!$sessionEditable){
					return $controller->redirect(array('action' => 'sessionView', $id));
				}

				$provider = $data['TrainingSession']['training_provider_id'];
				$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
				$trainingSessionTrainees = $this->TrainingSessionTrainee->find('all',  
					array(
						'fields' => array('TrainingSessionTrainee.*', 'Staff.first_name', 'Staff.last_name'),
						'recursive' => -1, 
						'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$id),
						'joins' => array(
							array(
								'type' => 'INNER',
								'table' => 'staff',
								'alias' => 'Staff',
								'conditions' => array('Staff.id = TrainingSessionTrainee.staff_id')
							)
						)
					)
				);
				$trainingSessionTraineesVal = null;
				if(!empty($trainingSessionTrainees)){
					foreach($trainingSessionTrainees as $val){
						$val['TrainingSessionTrainee']['first_name'] = $val['Staff']['first_name'];
						$val['TrainingSessionTrainee']['last_name'] = $val['Staff']['last_name'];
						$trainingSessionTraineesVal[] = $val['TrainingSessionTrainee'];
					}
				}
				$controller->request->data = array_merge($data, array('TrainingSessionTrainee'=>$trainingSessionTraineesVal));
			}
			$controller->request->data['TrainingSession']['sessionEditable'] = $sessionEditable;
		}
		else{
			if ($this->saveAll($controller->request->data, array('validate' => 'only'))){

				if(!isset($controller->request->data['TrainingSession']['sessionEditable']) || $controller->request->data['TrainingSession']['sessionEditable'] == '1'){
					if (isset($controller->request->data['save'])) {
				   	$controller->request->data['TrainingSession']['training_status_id'] = 1; 
					} else if (isset($controller->request->data['submitForApproval'])) {
				      	$controller->request->data['TrainingSession']['training_status_id'] = 2; 
					}
				}
				
				if($this->saveAll($controller->request->data)){
					if(isset($controller->request->data['DeleteTrainee'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteTrainee'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingSessionTrainee->deleteAll(array('TrainingSessionTrainee.id' => $deletedId), false);
					}
					if(empty($controller->request->data[$this->name]['id'])){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}
					else{
						$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
					}
					return $controller->redirect(array('action' => 'session'));
				}
			}
		}
		$controller->set('provider', $provider);
	}
}
