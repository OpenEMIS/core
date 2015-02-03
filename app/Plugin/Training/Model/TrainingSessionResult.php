<?php
class TrainingSessionResult extends TrainingAppModel {

	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		),
		'TrainingStatus',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	); 

	public $headerDefault = 'Results';
	
	public function result($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);

		$trainingStatus = ClassRegistry::init('TrainingStatus');
		$statusOptions = $trainingStatus->getList();
		$selectedStatus = empty($params['pass'][0])? null:$params['pass'][0];
		

		if ($controller->request->is('post')) {
			if (isset($controller->request->data['sortdir']) && isset($controller->request->data['order'])) {
				if ($controller->request->data['sortdir'] != $controller->Session->read('Search.sortdirTrainingResult')) {
					$controller->Session->delete('Search.sortdirTrainingResult');
					$controller->Session->write('Search.sortdirTrainingResult', $controller->request->data['sortdir']);
				}
				if ($controller->request->data['order'] != $controller->Session->read('Search.orderTrainingResult')) {
					$controller->Session->delete('Search.orderTrainingResult');
					$controller->Session->write('Search.orderTrainingResult', $controller->request->data['order']);
				}
			}
		}

		$conditions = array();
		if(!empty($selectedStatus)){
			$conditions['TrainingSessionResult.training_status_id'] = $selectedStatus;
		}else{
			$conditions['NOT']['TrainingSessionResult.training_status_id'] = 4;
		}

		$fieldordername = ($controller->Session->read('Search.orderTrainingResult')) ? $controller->Session->read('Search.orderTrainingResult') : array('TrainingCourse.code', 'TrainingCourse.title', 'TrainingCourse.credit_hours', 'TrainingSessionResult.training_status_id');
		$fieldorderdir = ($controller->Session->read('Search.sortdirTrainingResult')) ? $controller->Session->read('Search.sortdirTrainingResult') : 'asc';
		$order = $fieldordername;
		if($controller->Session->check('Search.orderTrainingResult')){
			$order = array($fieldordername => $fieldorderdir);
		}

		$controller->Paginator->settings = array(
	        'conditions' => $conditions,
	        'fields' => array('TrainingSessionResult.id', 'TrainingCourse.code', 'TrainingCourse.title', 'TrainingCourse.credit_hours', 'TrainingStatus.id', 'TrainingStatus.name'),
	        'joins' => array(
	        	array(
					'type' => 'INNER',
					'table' => 'training_sessions',
					'alias' => 'TrainingSession',
					'conditions' => array('TrainingSession.id = TrainingSessionResult.training_session_id')
				),
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
					'conditions' => array('TrainingStatus.id = TrainingSessionResult.training_status_id')
				)
		    ),
	        'limit' => 25,
	        'recursive'=> -1,
	        'order' => $order
	    );
		
		$data = $controller->paginate('TrainingSessionResult');
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
	
	public function resultView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'result'));
		}
		
		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$trainingSessionTrainee->bindModel(
	        array('hasMany' => array(
                 	'TrainingSessionTraineeResult' => array(
						'className' => 'TrainingSessionTraineeResult',
						'foreignKey' => 'training_session_trainee_id',
						'dependent' => true
					),
	            )
	        ), false
	    );
		$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
			array(
				'fields' => array('*'),
				'recursive' => 2,
				'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$data['TrainingSessionResult']['training_session_id']),
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

		$trainingSessionTrainer = ClassRegistry::init('TrainingSessionTrainer');
		$trainingSessionTrainers = $trainingSessionTrainer->find('all',  
			array(
				'fields' => array('TrainingSessionTrainer.*'),
				'conditions'=>array('TrainingSessionTrainer.training_session_id'=>$data['TrainingSessionResult']['training_session_id']),
			)
		);

		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$trainingCourses = $trainingCourse->find('first',  
			array(
				'conditions'=>array('TrainingCourse.id'=>$data['TrainingSession']['training_course_id'])
			)
		);

		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviders = $trainingProvider->find('first',  
			array(
				'conditions'=>array('TrainingProvider.id'=>$data['TrainingSession']['training_provider_id'])
			)
		);

		$trainingCourseResultType = ClassRegistry::init('TrainingCourseResultType');
		// $trainingCourseResultType->bindModel(
		//         array('belongsTo' => array(
		//                 'TrainingResultType'
		//             )
		//         )
	 //    );

		$trainingCourseResultTypes = $trainingCourseResultType->find('all', array('conditions'=>array('TrainingCourseResultType.training_course_id'=>$trainingCourses['TrainingCourse']['id'])));	
		$controller->set('trainingCourseResultTypes', $trainingCourseResultTypes);

		$controller->Session->write('TrainingResultId', $id);
		$controller->set('trainingCourses', $trainingCourses);
		$controller->set('trainingSessionTrainees', $trainingSessionTrainees);
		$controller->set('trainingSessionTrainers', $trainingSessionTrainers);
		$controller->set('trainingProviders', $trainingProviders);
		$controller->set('data', $data);

		//APROVAL
		$pending = $data['TrainingSessionResult']['training_status_id']=='2' ? true : false;
		$controller->Workflow->getApprovalWorkflow($this->name, $pending, $id);
		$controller->set('approvalMethod', 'result');
		$controller->set('controller', 'Training');
		$controller->set('plugin', 'Training');
	}

    public function resultActivate($controller, $params) {
        if($controller->Session->check('TrainingResultId')) {
            $id = $controller->Session->read('TrainingResultId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['TrainingSessionResult']['training_status_id']=='2'){

				$trainingCourse = ClassRegistry::init('TrainingCourse');
				$trainingCourses = $trainingCourse->find('first', array('conditions'=> array('id' => $data['TrainingSession']['training_course_id'])));

	            $name = $trainingCourses['TrainingCourse']['code'] . ' - ' . $trainingCourses['TrainingCourse']['title'];
				
	            $this->updateAll(
	    			array('TrainingSessionResult.training_status_id' => 3),
	    			array('TrainingSessionResult.id '=> $id)
				);
	            $controller->Message->alert('Training.activate.success');
	        }
            $controller->redirect(array('action' => 'result'));
        }
    }

	public function resultEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}

	public function resultApproval($controller, $params){
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

						$trainingSessionResult = $this->find('first', array('conditions'=> array($this->name.'.id' => $saveData['WorkflowLog']['record_id'])));
						$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
						$trainingSessionTrainees = $trainingSessionTrainee->find('list', array('fields'=>array('TrainingSessionTrainee.staff_id'),'conditions'=> array('TrainingSessionTrainee.training_session_id' => $trainingSessionResult['TrainingSession']['id'])));
						$staffTrainingNeed = ClassRegistry::init('StaffTrainingNeed');
						
						$staffTrainingNeed->updateAll(
						    array('StaffTrainingNeed.training_status_id' => '4'),
						    array(
						    	'StaffTrainingNeed.ref_course_table'=>'TrainingCourse', 
						    	'StaffTrainingNeed.staff_id'=> $trainingSessionTrainees,
						    	'StaffTrainingNeed.ref_course_id'=>$trainingSessionResult['TrainingSession']['training_course_id'],
						    	'StaffTrainingNeed.training_status_id'=>'3'
					    	)
						);
					}
				}else{
					$this->id =  $saveData['WorkflowLog']['record_id'];
					$this->saveField('training_status_id', 1);
				}
				return $controller->redirect(array('action'=>'resultView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}

	public function getResultList($id, &$trainingSessionTrainees, &$result, &$fieldName){
	 	$trainingSessionResult = $this->find('first',  
			array(
				'recursive'=> -1,
				'conditions'=>array('TrainingSessionResult.id'=>$id)
			)
		);

		$trainingSessionId = $trainingSessionResult['TrainingSessionResult']['training_session_id'];

		$trainingSession = ClassRegistry::init('TrainingSession');
		$trainingSessions = $trainingSession->find('first',  
			array(
				'conditions'=>array('TrainingSession.id'=>$trainingSessionId)
			)
		);

		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$trainingSessionTrainee->bindModel(
	        array('hasMany' => array(
                 	'TrainingSessionTraineeResult' => array(
						'className' => 'TrainingSessionTraineeResult',
						'foreignKey' => 'training_session_trainee_id',
						'dependent' => true
					),
	            )
	        ), false
	    );
		$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
			array(
				'fields' => array('*'),
				'recursive' => 2,
				'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$trainingSessionId),
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


		$trainingCourseResultType = ClassRegistry::init('TrainingCourseResultType');
		// $trainingCourseResultType->bindModel(
		//         array('belongsTo' => array(
		//                 'TrainingResultType'
		//             )
		//         )
	 //    );

		$trainingCourseResultTypes = $trainingCourseResultType->find('all', array('conditions'=>array('TrainingCourseResultType.training_course_id'=>$trainingSessions['TrainingSession']['training_course_id'])));	
		
		$fieldName = array(__('OpenEmis ID'), __('First Name'), __('Last Name'));
		foreach($trainingCourseResultTypes as $trainingCourseResultType){
			$fieldName[] = $trainingCourseResultType['TrainingResultType']['name'] . ' ' . __('(1=Pass/0=Fail)');
			$fieldName[] = $trainingCourseResultType['TrainingResultType']['name'] . ' ' . __('Result');
		}

		$fieldName[] = __('(1=Pass/0=Fail)');
		$fieldName[] = __('Result');

	 	if(!empty($trainingSessionTrainees)){
	        $i = 0;
	        foreach($trainingSessionTrainees as $obj){
	        	$result[$i]['Staff.identification_no'] = $obj['Staff']['identification_no'];
	        	$result[$i]['Staff.first_name'] = $obj['Staff']['first_name'];
	        	$result[$i]['Staff.middle_name'] = $obj['Staff']['middle_name'];
	        	$result[$i]['Staff.third_name'] = $obj['Staff']['third_name'];
	        	$result[$i]['Staff.last_name'] = $obj['Staff']['last_name'];
	        	foreach($obj['TrainingSessionTraineeResult'] as $val){
	        		$pass = $val['pass'];
	        		if($pass=='-1'){
	        			$pass = '';
	        		}
	        		$result[$i]['TrainingSessionTraineeResult.'.$val['training_result_type_id'].'_pass'] = $pass;
	        		$result[$i]['TrainingSessionTraineeResult.'.$val['training_result_type_id'].'_result'] = $val['result'];
	        	}
	        	$pass = $obj['TrainingSessionTrainee']['pass'];
	        	if($pass=='-1'){
        			$pass = '';
        		}
	        	$result[$i]['TrainingSessionTrainee.pass'] = $pass;
	        	$result[$i]['TrainingSessionTrainee.result'] = $obj['TrainingSessionTrainee']['result'];
	            $i++;
	        }
	    }
	}

	public function resultDownloadTemplate($controller, $params){
		 if($controller->Session->check('TrainingResultId')) {
	 	 	$id = $controller->Session->read('TrainingResultId');

	 	 	$result = array();
	 	 	$fieldName = array();
 	 	 	$trainingSessionTrainees = array();
	 	 	$this->getResultList($id, $trainingSessionTrainees, $result, $fieldName);
		 	
		 	echo $controller->download(__('TrainingResult').'_' . date('Ymdhis') . '.csv');

			echo $controller->array2csv($result, $fieldName);
		 	die();
		 }else{
	 		  $controller->redirect(array('action' => 'result'));
		 }
	}

	public function resultUpload($controller, $params){
	 	if($controller->Session->check('TrainingResultId')) {
	 		$id = $controller->Session->read('TrainingResultId');
	 		$trainingSessionResult = $this->find('first',  
				array(
					'recursive'=> -1,
					'conditions'=>array('TrainingSessionResult.id'=>$id)
				)
			);

			if($trainingSessionResult['TrainingSessionResult']['training_status_id']!=1){
				$controller->redirect(array('action' => 'resultView', $id));	
			}
			$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
			$controller->set('subheader', $this->headerDefault);
			$controller->set('id', $id);
			$controller->set('modelName', $this->name);


			if($controller->request->is('post')){
				if ($_FILES['data']['error'][$this->name]['upload_file'] == UPLOAD_ERR_OK               //checks for errors
				      && is_uploaded_file($_FILES['data']['tmp_name'][$this->name]['upload_file'])) { //checks that file is uploaded
				 
				 	 $result = array();
				 	 $fieldName = array();
				 	 $trainingSessionTrainees = array();
				 	 $this->getResultList($id, $trainingSessionTrainees, $result, $fieldName);

			 	 	$tmpName = $_FILES['data']['tmp_name'][$this->name]['upload_file']; 
			 		$row = 0;
					ini_set("auto_detect_line_endings", true);
					$handle = fopen($tmpName, "r");
					$header = array();
				 	$count = 0;
			 	 	$validateFlag = true;
			 	 	$updateData= array();
		 	 	 	$error = '';
			 	 	$errorFormat = __('Row %s: %s');
			 	 	$i = 0;

					while (($rowData = fgetcsv($handle, 1000, ",")) !== FALSE) {
					    $rowData = array_map("utf8_encode", $rowData);
					    
					   	if($row==0){
							$header = $rowData;
						}else{
							try{
								$resultSplit = $result[$i];
				 	 			array_splice($resultSplit,3);

				 	 			$rowSplitCompare = $rowData;
				 	 			array_splice($rowSplitCompare,3);
				 	 			$compare = array_diff($resultSplit, $rowSplitCompare);
				 	 			$resultPass = preg_grep('~pass~i', array_keys($result[$i]));

 
				 	 			if(empty($compare)){
				 	 				foreach($resultPass as $passCol=>$val){
				 	 					if($rowData[$passCol]!='1' && $rowData[$passCol]!='0'){
					 	 					$error .= '<br />' . sprintf($errorFormat, ($i+1), sprintf(__('Column %s only accepts 0 or 1 as input.'), ($passCol+1)));
					 	 					$validateFlag = false;
					 	 				}
				 	 				}
				 	 				
				 	 				$trainingSessionTraineePassCol = array_search('TrainingSessionTrainee.pass', array_keys($result[$i]));
				 	 				$updateData['TrainingSessionTrainee'][$i] = array('id'=>$trainingSessionTrainees[$i]['TrainingSessionTrainee']['id'], 'pass'=>$rowData[$trainingSessionTraineePassCol], 'result'=>$rowData[$trainingSessionTraineePassCol+1]);
				 	 				$r = 0;
				 	 				foreach($resultPass as $passCol=>$val){
				 	 					if($passCol==$trainingSessionTraineePassCol){
				 	 						continue;
				 	 					}
				 	 					$updateData['TrainingSessionTrainee'][$i]['TrainingSessionTraineeResult'][$r] = array('id'=>$trainingSessionTrainees[$i]['TrainingSessionTraineeResult'][$r]['id'], 'pass'=>$rowData[$passCol], 'result'=>$rowData[$passCol+1]);
				 	 					$r++;
				 	 				}
				 	 				$count++;
				 	 			}else{
				 	 				$error .= '<br />' . sprintf($errorFormat, ($i+1), __('Columns/Data do not match.'));
				 	 				$validateFlag = false;
				 	 			}
								$i++;
							} catch (\Exception $e) {
								$validateFlag = false;
							}
						}
						$row++;
					}
					fclose($handle);
					if($row<=1){
						$error .= '<br />' . sprintf($errorFormat, ($i+1), __('Columns/Data do not match.'));
						$validateFlag = false;
					}
					
					if($validateFlag){
						$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
						$trainingSessionTrainee->bindModel(
					        array('hasMany' => array(
				                 	'TrainingSessionTraineeResult' => array(
										'className' => 'TrainingSessionTraineeResult',
										'foreignKey' => 'training_session_trainee_id',
										'dependent' => true
									),
					            )
					        ), false
					    );

						if($trainingSessionTrainee->saveAll($updateData['TrainingSessionTrainee'], array('deep' => true))){
							$controller->Utility->alert(sprintf(__('%s Record(s) have been updated'),$count));
						}else{
							$controller->Utility->alert(__('Error encountered, record(s) could not be updated'), array('type' => 'error'));
						}
					}else{
						$controller->Utility->alert(__('Invalid File Format').$error, array('type' => 'error'));
					}
				}
			}

		}else{
		  	$controller->redirect(array('action' => 'result'));
		}
	}
	
	
	function setup_add_edit_form($controller, $params){
		$trainingCourse = ClassRegistry::init('TrainingCourse');

		$trainingCourseCodeOptions = $trainingCourse->find('list', array('fields'=> array('id', 'code'), 'conditions'=>array('training_status_id'=>3)));
		$trainingCourseOptions = $trainingCourse->find('list', array('fields'=> array('id', 'title'), 'conditions'=>array('training_status_id'=>3)));
	
		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviderOptions = $trainingProvider->getList();

		
		$controller->set('trainingCourseCodeOptions', $trainingCourseCodeOptions);
		$controller->set('trainingCourseOptions', $trainingCourseOptions);
		$controller->set('trainingProviderOptions', $trainingProviderOptions);

		$controller->set('modelName', $this->name);

		
		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$trainingSessionTrainee->bindModel(
	        array('hasMany' => array(
                 	'TrainingSessionTraineeResult' => array(
						'className' => 'TrainingSessionTraineeResult',
						'foreignKey' => 'training_session_trainee_id',
						'dependent' => true
					),
	            )
	        ), false
	    );
	

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
		
			if(!empty($data)){

				if($data['TrainingSessionResult']['training_status_id']!=1){
					return $controller->redirect(array('action' => 'resultView', $id));
				}

				$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
					array(
						'fields' => array('*'),
						'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$data['TrainingSessionResult']['training_session_id']),
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


				$trainingCourse = ClassRegistry::init('TrainingCourse');
				$trainingCourses = $trainingCourse->find('first',  
					array(
						'recursive' => -1, 
						'fields' => array('TrainingCourse.*', 'TrainingSession.*'),
						'joins' => array(
							array(
								'type' => 'INNER',
								'table' => 'training_sessions',
								'alias' => 'TrainingSession',
								'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
							)
						),
						'conditions'=>array('TrainingSession.id'=>$data['TrainingSessionResult']['training_session_id'])
					)
				);


				$trainingSessionTraineesVal = null;
				$trainingSessionTraineeResultsVal = null;
				if(!empty($trainingSessionTrainees)){
					foreach($trainingSessionTrainees as $val){
						$val['TrainingSessionTrainee']['first_name'] = $val['Staff']['first_name'];
						$val['TrainingSessionTrainee']['middle_name'] = $val['Staff']['middle_name'];
						$val['TrainingSessionTrainee']['third_name'] = $val['Staff']['third_name'];
						$val['TrainingSessionTrainee']['last_name'] = $val['Staff']['last_name'];
						$trainingSessionTraineesVal[] = $val['TrainingSessionTrainee'];
						foreach($val['TrainingSessionTraineeResult'] as $val2){
							$trainingSessionTraineeResultsVal[] = $val2;
						}
					}
				}

				$trainingCourseResultType = ClassRegistry::init('TrainingCourseResultType');
				// $trainingCourseResultType->bindModel(
				//         array('belongsTo' => array(
				//                 'TrainingResultType'
				//             )
				//         )
			 //    );

				$trainingCourseResultTypes = $trainingCourseResultType->find('all', array('conditions'=>array('TrainingCourseResultType.training_course_id'=>$trainingCourses['TrainingCourse']['id'])));	
				$controller->set('trainingCourseResultTypes', $trainingCourseResultTypes);
			
				$trainingSessionTrainer = ClassRegistry::init('TrainingSessionTrainer');
				$trainingSessionTrainers = $trainingSessionTrainer->find('all',  
					array(
						'fields' => array('TrainingSessionTrainer.*'),
						'recursive' => -1, 
						'conditions'=>array('TrainingSessionTrainer.training_session_id'=>$data['TrainingSessionResult']['training_session_id']),
					)
				);
				$trainingSessionTrainersVal = null;
				if(!empty($trainingSessionTrainers)){
					foreach($trainingSessionTrainers as $val){
						$trainingSessionTrainersVal[] = $val['TrainingSessionTrainer'];
					}
				}

				$merge = $trainingCourses;

				if(!empty($trainingSessionTraineesVal)){
					$merge = array_merge(array('TrainingSessionTrainee'=>$trainingSessionTraineesVal, 'TrainingSessionTrainer'=>$trainingSessionTrainersVal, 'TrainingSessionTraineeResult'=>$trainingSessionTraineeResultsVal), $trainingCourses);
				}
				$controller->request->data = array_merge($data, $merge);
			}else{
				return $controller->redirect(array('action' => 'result'));
			}
		}else{
			
			if ($this->save($controller->request->data, array('validate' => 'only'))){
				if (isset($controller->request->data['save'])) {
				   	$controller->request->data['TrainingSessionResult']['training_status_id'] = 1; 
				} else if (isset($controller->request->data['submitForApproval'])) {
			      	$controller->request->data['TrainingSessionResult']['training_status_id'] = 2; 
				}
				
				if($this->save($controller->request->data)){
					if(isset($controller->request->data['TrainingSessionTrainee'])){
						$data = $controller->request->data;
					
						if($trainingSessionTrainee->saveAll($data['TrainingSessionTrainee'], array('deep' => true))){	
							$controller->Message->alert('general.edit.success');
							return $controller->redirect(array('action' => 'result'));
						}
					}
					
				}
			}
		}
	}
}
?>
