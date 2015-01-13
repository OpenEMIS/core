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
	public $actsAs = array('ControllerAction', 'Containable', 'DatePicker' => array('start_date', 'end_date'));

	public $trainerTypeOptions = array('1'=>'Internal', '2'=>'External');

	public $trainerTypes = array('Staff'=>'Internal', '1'=>'External');
	
	public $belongsTo = array(
		'TrainingCourse',
		'TrainingStatus',
		'TrainingProvider',
		'Area',
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
		),
		'TrainingSessionTrainer' => array(
			'className' => 'TrainingSessionTrainer',
			'foreignKey' => 'training_session_id',
			'dependent' => true
		)
	);

	public $hasOne = array(
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
		$trainingSessions = $this->find('first', array('recursive'=>-1,'conditions'=>array('TrainingSession.id'=>$sessionId)));
		
		if($trainingSessions['TrainingSession']['training_status_id']=='1'){
			return '1';
		}else if($trainingSessions['TrainingSession']['training_status_id']=='3'){
			$trainingSessionResults = $this->TrainingSessionResult->find('first', array('recursive'=>-1,'conditions'=>array('TrainingSessionResult.training_session_id'=>$sessionId)));
		
			if(!empty($trainingSessionResults)){
				if($trainingSessionResults['TrainingSessionResult']['training_status_id']=='1'){
					return '2';
				}
			}
		}
		return '0';
	}

	
	public function session($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);

		$trainingStatus = ClassRegistry::init('TrainingStatus');
		$statusOptions = $trainingStatus->getList();
		$selectedStatus = empty($params['pass'][0])? null:$params['pass'][0];
	

		if ($controller->request->is('post')) {
			if (isset($controller->request->data['sortdir']) && isset($controller->request->data['order'])) {
				if ($controller->request->data['sortdir'] != $controller->Session->read('Search.sortdirTrainingSession')) {
					$controller->Session->delete('Search.sortdirTrainingSession');
					$controller->Session->write('Search.sortdirTrainingSession', $controller->request->data['sortdir']);
				}
				if ($controller->request->data['order'] != $controller->Session->read('Search.orderTrainingSession')) {
					$controller->Session->delete('Search.orderTrainingSession');
					$controller->Session->write('Search.orderTrainingSession', $controller->request->data['order']);
				}
			}
		}

		$conditions = array();
		if(!empty($selectedStatus)){
			$conditions['TrainingSession.training_status_id'] = $selectedStatus;
		}else{
			$conditions['NOT']['TrainingSession.training_status_id'] = 4;
		}


		$fieldordername = ($controller->Session->read('Search.orderTrainingSession')) ? $controller->Session->read('Search.orderTrainingSession') : array('TrainingSession.start_date', 'TrainingSession.location', 'TrainingCourse.code',  'TrainingSession.training_status_id');
		$fieldorderdir = ($controller->Session->read('Search.sortdirTrainingSession')) ? $controller->Session->read('Search.sortdirTrainingSession') : 'asc';
		$order = $fieldordername;
		if($controller->Session->check('Search.orderTrainingSession')){
			$order = array($fieldordername => $fieldorderdir);
		}

		$controller->Paginator->settings = array(
	        'conditions' => $conditions,
	        'fields' => array('TrainingSession.*', 'TrainingCourse.code', 'TrainingCourse.title', 'TrainingStatus.id', 'TrainingStatus.name'),
	        'joins' => array(
		        array(
					'type' => 'INNER',
					'table' => 'training_courses',
					'alias' => 'TrainingCourse',
					'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
				),
				array(
					'type' => 'INNER',
					'table' => 'field_option_values',
					'alias' => 'TrainingStatus',
					'conditions' => array('TrainingStatus.id = TrainingSession.training_status_id')
				)
		    ),
	        'limit' => 25,
	        'recursive'=> -1,
	        'order' => $order
	    );
		
		$data = $controller->paginate('TrainingSession');
		$data = $controller->Workflow->populateWorkflowStatus($this->name, 'TrainingStatus', $data);	

		if (empty($data) && !$controller->request->is('ajax')) {
			$controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}

		$controller->set('sortedcol', $fieldordername);
		$controller->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');

		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('statusOptions', $statusOptions);
		$controller->set('selectedStatus', $selectedStatus);
		if ($controller->request->is('post')) {
			$controller->set('ajax', true);
		}
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
		
		$trainingSessionTrainees = $this->TrainingSessionTrainee->find('all',  
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

		$trainingSessionTrainers = $this->TrainingSessionTrainer->find('all',  
			array(
				'fields' => array('TrainingSessionTrainer.*'),
				'conditions'=>array('TrainingSessionTrainer.training_session_id'=>$id)
			)
		);

		$sessionEditable = $this->getSessionResultStatus($id);
		
		$controller->Session->write('TrainingSessionId', $id);
		$controller->set('trainingSessionTrainees', $trainingSessionTrainees);
		$controller->set('trainingSessionTrainers', $trainingSessionTrainers);
		$controller->set('data', $data);
		$controller->set('sessionEditable', $sessionEditable);

		//APROVAL
		$pending = $data['TrainingSession']['training_status_id']=='2' ? true : false;
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
	            $controller->Message->alert('general.delete.success');
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
	            $controller->Message->alert('Training.activate.success');
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
            $controller->Message->alert('Training.inactivate.success');
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

						$trainingSessions = $this->find('first', array('conditions'=>array('TrainingSession.id'=>$saveData['WorkflowLog']['record_id'])));
						$trainingCourseResultType = ClassRegistry::init('TrainingCourseResultType');
						// $trainingCourseResultType->bindModel(
					 //        array('belongsTo' => array(
					 //                'TrainingResultType'
					 //            )
					 //        )
					 //    );

						$trainingCourseResultTypes = $trainingCourseResultType->find('all', array('recursive'=>-1,'conditions'=>array('TrainingCourseResultType.training_course_id'=>$trainingSessions['TrainingSession']['training_course_id'])));
						$trainingSessionTraineeResults = array();
						foreach($trainingSessions['TrainingSessionTrainee'] as $key=>$val){
							foreach($trainingCourseResultTypes as $key2=>$val2){
								$trainingSessionTraineeResults[] = array('training_session_trainee_id'=>$val['id'], 'training_result_type_id'=>$val2['TrainingCourseResultType']['training_result_type_id']);
							}
						}
						
						$trainingSessionTraineeResult = ClassRegistry::init('TrainingSessionTraineeResult');
						$trainingSessionTraineeResult->saveAll($trainingSessionTraineeResults);
					}
				}else{
					$this->id =  $saveData['WorkflowLog']['record_id'];
					$this->saveField('training_status_id', 1);
				}
				return $controller->redirect(array('action'=>'sessionView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}

	public function sessionDownloadTemplate($controller, $params){
 	 	$result = array();
 	 	$fieldName = array(__('OpenEmis ID'));
	 	
	 	echo $controller->download(__('TrainingSessionTrainee').'_' . date('Ymdhis') . '.csv');

		echo $controller->array2csv($result, $fieldName);
	 	die();
	}

	public function sessionTraineeListDownload($controller, $params){
	 	if($controller->Session->check('TrainingSessionId')) {
 			$fieldName = array(__('OpenEmis ID'), __('First Name'), __('Last Name'));
	 	 	$id = $controller->Session->read('TrainingSessionId');
	 	 	$this->TrainingSessionTrainee->bindModel(
		        array('belongsTo' => array(
		                'Staff' => array(
							'className' => 'Staff.Staff',
							'foreignKey' => 'staff_id'
						)
		            )
		        )
		    );
	 	 	$result = array();
 	 		$trainingSessionTrainees = $this->TrainingSessionTrainee->find('all', array('fields'=>array('Staff.identification_no', 'Staff.first_name', 'Staff.last_name'), 'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$id)));
 	 		if(!empty($trainingSessionTrainees)){
		        $i = 0;
		        foreach($trainingSessionTrainees as $obj){
		        	$result[$i]['Staff.identification_no'] =  $obj['Staff']['identification_no'];
	        		$result[$i]['Staff.first_name'] =  $obj['Staff']['first_name'];
        			$result[$i]['Staff.last_name'] =  $obj['Staff']['last_name'];
		        	$i++;
		        }
		    }
 	 		echo $controller->download(__('TrainingSessionTrainee').'_' . date('Ymdhis') . '.csv');

			echo $controller->array2csv($result, $fieldName);
		 	die();
		}else{
 		  	$controller->redirect(array('action' => 'session'));
	 	}
	}

	public function sessionDuplicate($controller, $params){
		if($controller->Session->check('TrainingSessionId')) {
		 	$id = $controller->Session->read('TrainingSessionId');
			$trainingSession = $this->find('all', array('conditions'=>array('TrainingSession.id'=>$id)));
			if(!empty($trainingSession)){
				$data['TrainingSession'] = $trainingSession[0]['TrainingSession'];
				$data['TrainingSessionTrainee'] = $trainingSession[0]['TrainingSessionTrainee'];
				$data['TrainingSessionTrainer'] = $trainingSession[0]['TrainingSessionTrainer'];
			}
			
			$data['TrainingSession']['training_status_id'] = 1;
			unset($data['TrainingSession']['modified_user_id']);
			unset($data['TrainingSession']['modified']);
			unset($data['TrainingSession']['created_user_id']);
			unset($data['TrainingSession']['created']);
			unset($data['TrainingSession']['id']);
			if(!empty($data['TrainingSessionTrainee'])){
				foreach($data['TrainingSessionTrainee'] as $key=>$val){
					unset($data['TrainingSessionTrainee'][$key]['id']);
					unset($data['TrainingSessionTrainee'][$key]['training_session_id']);
				}
			}
			if(!empty($data['TrainingSessionTrainer'])){
				foreach($data['TrainingSessionTrainer'] as $key=>$val){
					unset($data['TrainingSessionTrainer'][$key]['id']);
					unset($data['TrainingSessionTrainer'][$key]['training_session_id']);
				}
			}

			if($this->saveAll($data)){
				$controller->Message->alert('general.duplicate.success');
				$controller->redirect(array('action' => 'sessionEdit', $this->getLastInsertId()));
			}else{
				$controller->Message->alert('general.duplicate.failed');
				$controller->redirect(array('action' => 'session'));
			}
			
		}else{
 		  	$controller->redirect(array('action' => 'session'));
	 	}
	}

	
	function setup_add_edit_form($controller, $params){
		$trainingCourseOptions = $this->TrainingCourse->find('list', array('fields'=> array('id', 'title'), 'conditions'=>array('training_status_id'=>3)));
		$areaOptions = $this->Area->find('list', array('fields'=> array('id', 'name'), 'conditions'=>array('area_level_id'=>4, 'visible'=>1), 'order'=>array('order')));

		$controller->set('trainingCourseOptions', $trainingCourseOptions);
		$controller->set('areaOptions', $areaOptions);

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

				$trainingSessionTrainers = $this->TrainingSessionTrainer->find('all',  
					array(
						'fields' => array('TrainingSessionTrainer.*'),
						'recursive' => -1, 
						'conditions'=>array('TrainingSessionTrainer.training_session_id'=>$id),
					)
				);
				$trainingSessionTrainersVal = null;
				if(!empty($trainingSessionTrainers)){
					foreach($trainingSessionTrainers as $val){
						$trainingSessionTrainersVal[] = $val['TrainingSessionTrainer'];
					}
				}
				$controller->request->data = array_merge($data, array('TrainingSessionTrainee'=>$trainingSessionTraineesVal, 'TrainingSessionTrainer'=>$trainingSessionTrainersVal));
			}
			$controller->request->data['TrainingSession']['sessionEditable'] = $sessionEditable;
		}
		else{
			if ($this->saveAll($controller->request->data, array('validate' => 'only'))){

				if(!isset($controller->request->data['TrainingSession']['sessionEditable']) || $controller->request->data['TrainingSession']['sessionEditable'] == '1'){
					if ($controller->request->data['TrainingSession']['training_status_id']=='1') {
				   	$controller->request->data['TrainingSession']['training_status_id'] = 1; 
					} else if ($controller->request->data['TrainingSession']['training_status_id']=='2') {
				      	$controller->request->data['TrainingSession']['training_status_id'] = 2; 
					}
				}

				$data = $controller->request->data;
				if($data['TrainingSession']['sessionEditable']=='2'){
					$this->TrainingSessionTrainee->bindModel(
				        array('hasMany' => array(
			                 	'TrainingSessionTraineeResult' => array(
									'className' => 'TrainingSessionTraineeResult',
									'foreignKey' => 'training_session_trainee_id',
									'dependent' => true,
									'exclusive' => true
								),
				            )
				        ),
				        false
				    );

					
					$trainingCourseResultType = ClassRegistry::init('TrainingCourseResultType');
					// $trainingCourseResultType->bindModel(
				 //        array('belongsTo' => array(
				 //                'TrainingResultType'
				 //            )
				 //        )
				 //    );

				    $trainingCourseResultTypes = $trainingCourseResultType->find('all', array('recursive'=>-1,'conditions'=>array('TrainingCourseResultType.training_course_id'=>$data['TrainingSession']['training_course_id'])));
						
					if(!empty($data['TrainingSessionTrainee'])){
						$trainingSessionTraineeResults = array();
						foreach($data['TrainingSessionTrainee'] as $key=>$value){
							$data['TrainingSessionTrainee'][$key]['training_session_id'] = $data['TrainingSession']['id'];
							if(!isset($data['TrainingSessionTrainee'][$key]['id'])){
								$this->TrainingSessionTrainee->create();
								$this->TrainingSessionTrainee->save($data['TrainingSessionTrainee'][$key]);
								$insertId = $this->TrainingSessionTrainee->getLastInsertId();
								unset($data['TrainingSessionTrainee'][$key]);
								foreach($trainingCourseResultTypes as $key=>$val){
									$trainingSessionTraineeResults[] = array('training_session_trainee_id'=>$insertId, 'training_result_type_id'=>$val['TrainingCourseResultType']['training_result_type_id']);
								}
							}
						}
						$this->TrainingSessionTrainee->saveAll($data['TrainingSessionTrainee']);
						$this->TrainingSessionTrainee->TrainingSessionTraineeResult->saveAll($trainingSessionTraineeResults);
					}

					if(isset($data['DeleteTrainee'])){
						$deletedId = array();
						foreach($data['DeleteTrainee'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						
					 	$this->TrainingSessionTrainee->deleteAll(array('TrainingSessionTrainee.id'=>$deletedId));
					}
					$controller->Message->alert('general.edit.success');
					return $controller->redirect(array('action' => 'session'));
					
				}else{
					if($this->saveAll($data)){
						if(isset($data['DeleteTrainee'])){
							$deletedId = array();
							foreach($data['DeleteTrainee'] as $key=>$value){
								$deletedId[] = $value['id'];
							}
							$this->TrainingSessionTrainee->deleteAll(array('TrainingSessionTrainee.id' => $deletedId), false);
						}
						if(isset($data['DeleteTrainer'])){
							$deletedId = array();
							foreach($data['DeleteTrainer'] as $key=>$value){
								$deletedId[] = $value['id'];
							}
							$this->TrainingSessionTrainer->deleteAll(array('TrainingSessionTrainer.id' => $deletedId), false);
						}
						if(empty($controller->request->data[$this->name]['id'])){
						  	$controller->Message->alert('general.add.success');
						}
						else{	
						  	$controller->Message->alert('general.edit.success');
						}
						return $controller->redirect(array('action' => 'session'));
					}
				}
			}
		}
		$controller->set('provider', $provider);
	}
}
