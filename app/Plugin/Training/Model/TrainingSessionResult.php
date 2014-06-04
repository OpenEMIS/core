<?php
class TrainingSessionResult extends TrainingAppModel {
	//public $useTable = 'student_health_histories';

	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_session_id'
		),
		'TrainingStatus' => array(
			'className' => 'TrainingStatus',
			'foreignKey' => 'training_status_id'
		),
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
		$statusOptions = $trainingStatus->find('list', array('fields'=>array('id', 'name')));
		$selectedStatus = empty($params['pass'][0])? null:$params['pass'][0];
	
		$conditions = array();
		if(!empty($selectedStatus)){
			$conditions['TrainingSessionResult.training_status_id'] = $selectedStatus;
		}else{
			$conditions['NOT']['TrainingSessionResult.training_status_id'] = 4;
		}

		$data = $this->find('all', 
			array(
				'recursive' => -1, 
				'fields' => array('TrainingSessionResult.*', 'TrainingCourse.*', 'TrainingSession.*', 'TrainingStatus.*'),
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
						'table' => 'training_statuses',
						'alias' => 'TrainingStatus',
						'conditions' => array('TrainingStatus.id = TrainingSessionResult.training_status_id')
					)
				),
				'order'=> array('TrainingCourse.code', 'TrainingCourse.title', 'TrainingCourse.credit_hours', 'TrainingSessionResult.training_status_id'), 
				'conditions' => $conditions
			)
		);
		$courseId = array();
		foreach($data as $val){
			$courseId[] = $val['TrainingSession']['training_course_id'];
		} 
		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$trainingCourses = $trainingCourse->find('all', array('conditions'=>array('TrainingCourse.id' => $courseId)));

		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('trainingCourses', $trainingCourses);
		$controller->set('statusOptions', $statusOptions);
		$controller->set('selectedStatus', $selectedStatus);
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
		$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
			array(
				'fields' => array('TrainingSessionTrainee.*', 'Staff.first_name', 'Staff.last_name'),
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
				'conditions'=>array('TrainingCourse.id'=>$data['TrainingSession']['training_course_id'])
			)
		);

		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviders = $trainingProvider->find('first',  
			array(
				'conditions'=>array('TrainingProvider.id'=>$data['TrainingSession']['training_provider_id'])
			)
		);

		$controller->Session->write('TrainingResultId', $id);
		$controller->set('trainingCourses', $trainingCourses);
		$controller->set('trainingSessionTrainees', $trainingSessionTrainees);
		$controller->set('trainingProviders', $trainingProviders);
		$controller->set('data', $data);

		//APROVAL
		$pending = $data['TrainingSessionResult']['training_status_id']=='2' ? 'true' : 'false';
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
	            $controller->Utility->alert($name . ' have been activated successfully.');
	        }
            $controller->redirect(array('action' => 'result'));
        }
    }
    /*
    public function resultInactivate($controller, $params) {
        if($controller->Session->check('TrainingResultId')) {
            $id = $controller->Session->read('TrainingResultId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));

            $trainingCourse = ClassRegistry::init('TrainingCourse');
			$trainingCourses = $trainingCourse->find('first', array('conditions'=> array('id' => $data['TrainingSession']['training_course_id'])));

            $name = $trainingCourses['TrainingCourse']['code'] . ' - ' . $trainingCourses['TrainingCourse']['title'];
			
          	$this->updateAll(
    			array('TrainingSessionResult.training_status_id' => 4),
    			array('TrainingSessionResult.id '=> $id)
			);
            $controller->Utility->alert($name . ' have been inactivated successfully.');
            $controller->redirect(array('action' => 'result'));
        }
    }*/
	
	
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
					}
				}else{
					$this->id =  $saveData['WorkflowLog']['record_id'];
					$this->saveField('training_status_id', 1);
				}
				return $controller->redirect(array('action'=>'resultView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}

	public function getResultList($id){
	 	$trainingSessionResult = $this->find('first',  
			array(
				'recursive'=> -1,
				'conditions'=>array('TrainingSessionResult.id'=>$id)
			)
		);

		$trainingSessionId = $trainingSessionResult['TrainingSessionResult']['training_session_id'];

		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$data = $trainingSessionTrainee->find('all',  
			array(
				'fields' => array('id', 'identification_first_name', 'identification_last_name', 'pass', 'result'),
				'recursive' => -1, 
				'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$trainingSessionId)
			)
		);

		$result = array();

	 	if(!empty($data)){
	        $i = 0;
	        foreach($data as $obj){
	            foreach($obj as $key=>$value){
	            	$result[$i][] = implode(",", $value);
	            }
	            $i++;
	        }
	    }

		return $result;
	}

	public function resultDownloadTemplate($controller, $params){
		 if($controller->Session->check('TrainingResultId')) {
	 	 	$id = $controller->Session->read('TrainingResultId');

	 	 	$result = $this->getResultList($id);
		 	
		 	echo $this->download('TrainingResult_' . date('Ymdhis') . '.csv');

			

			$fieldName = array('Id', 'First Name', 'Last Name', '(1=Pass/0=Fail)', 'Result');
			echo $this->array2csv($result, $fieldName);
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
				 	 $data = file_get_contents($_FILES['data']['tmp_name'][$this->name]['upload_file']); 
				 	 

				 	 $result = $this->getResultList($id);

				 	 $arr = preg_split('/\r\n|\r|\n/', $data);
				 	 array_splice($arr, 0, 1);

				 	 $i = 0;
				 	 $count = 0;
				 	 $validateFlag = true;
				 	 $updateData= array();

				 	 $error = '';
				 	 $errorFormat = __('Row %s: %s');
				 	 foreach($arr as $row){
				 	 	if(!empty($row)){
				 	 		if(isset($result[$i][0])){
				 	 			$resultSplit = split(",",$result[$i][0]);
				 	 			array_splice($resultSplit,3);

				 	 			$rowSplit = split(",", $row);
				 	 			$rowSplitCompare = $rowSplit;
				 	 			array_splice($rowSplitCompare,3);

				 	 			if($rowSplitCompare==$resultSplit){
				 	 				if($rowSplit[3]!='1' && $rowSplit[3]!='0'){
				 	 					$error .= '<br />' . sprintf($errorFormat, ($i+1), 'Pass Column only accepts 0 or 1 as input.');
				 	 					$validateFlag = false;
				 	 				}
				 	 				$updateData[] = array('TrainingSessionTrainee'=>array('id'=>$rowSplit[0], 'pass'=>$rowSplit[3], 'result'=>$rowSplit[4]));
				 	 				$count++;
				 	 			}else{
				 	 				$error .= '<br />' . sprintf($errorFormat, ($i+1), 'Columns/Data do not match.');
				 	 				$validateFlag = false;
				 	 			}
				 	 		}else{
				 	 			$error .= '<br />' . sprintf($errorFormat, ($i+1), 'Columns/Data do not match.');
				 	 			$validateFlag = false;
				 	 		}
				 	 	}
				 	 	$i++;
				 	 }

					if($validateFlag){
						$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');

						if($trainingSessionTrainee->saveAll($updateData)){
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

	public function array2csv($results=NULL, $fieldName=NULL)
    {
       ob_end_clean();
       ob_start();
       $df = fopen("php://output", 'w');
       //fputs($df,$fieldName);
       fputs($df, implode(",", $fieldName)."\n");

        if(!empty($results)){
            foreach($results as $key=>$value){
                fputs($df, implode(",", $value)."\n");
            }
        }
       fclose($df);
       return ob_get_clean();
    }

	public function download($name){
        if( ! $name)
        {
            $name = md5(uniqid() . microtime(TRUE) . mt_rand()). '.csv';
        }
        header('Expires: 0');
        header('Content-Encoding: UTF-8');
        // force download  
        header("Content-Type: application/force-download; charset=UTF-8'");
        header("Content-Type: application/octet-stream; charset=UTF-8'");
        header("Content-Type: application/download; charset=UTF-8'");
        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$name}");
        header("Content-Transfer-Encoding: binary");
	}
	
	
	function setup_add_edit_form($controller, $params){
		$trainingCourse = ClassRegistry::init('TrainingCourse');

		$trainingCourseCodeOptions = $trainingCourse->find('list', array('fields'=> array('id', 'code'), 'conditions'=>array('training_status_id'=>3)));
		$trainingCourseOptions = $trainingCourse->find('list', array('fields'=> array('id', 'title'), 'conditions'=>array('training_status_id'=>3)));
	
		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviderOptions = $trainingProvider->find('list', array('fields'=> array('id', 'name')));
	
		$controller->set('trainingCourseCodeOptions', $trainingCourseCodeOptions);
		$controller->set('trainingCourseOptions', $trainingCourseOptions);
		$controller->set('trainingProviderOptions', $trainingProviderOptions);

		$controller->set('modelName', $this->name);
		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
		
			if(!empty($data)){
				if($data['TrainingSessionResult']['training_status_id']!=1){
					return $controller->redirect(array('action' => 'resultView', $id));
				}

				$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
				$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
					array(
						'fields' => array('TrainingSessionTrainee.*', 'Staff.first_name', 'Staff.last_name'),
						'recursive' => -1, 
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
				if(!empty($trainingSessionTrainees)){
					foreach($trainingSessionTrainees as $val){
						$val['TrainingSessionTrainee']['first_name'] = $val['Staff']['first_name'];
						$val['TrainingSessionTrainee']['last_name'] = $val['Staff']['last_name'];
						$trainingSessionTraineesVal[] = $val['TrainingSessionTrainee'];
					}
				}
				$merge = $trainingCourses;

				if(!empty($trainingSessionTraineesVal)){
					$merge = array_merge(array('TrainingSessionTrainee'=>$trainingSessionTraineesVal), $trainingCourses);
				}
				$controller->request->data = array_merge($data, $merge);
			}else{
				return $controller->redirect(array('action' => 'result'));
			}
		}
		else{
			
			if ($this->save($controller->request->data, array('validate' => 'only'))){
				if (isset($controller->request->data['save'])) {
				   	$controller->request->data['TrainingSessionResult']['training_status_id'] = 1; 
				} else if (isset($controller->request->data['submitForApproval'])) {
			      	$controller->request->data['TrainingSessionResult']['training_status_id'] = 2; 
				}
				
				if($this->save($controller->request->data)){
					if(isset($controller->request->data['TrainingSessionTrainee'])){
						$trainingSessionTrainee = ClassRegistry::init('Training.TrainingSessionTrainee');
						if($trainingSessionTrainee->saveAll($controller->request->data['TrainingSessionTrainee'])){
						}
					}
					if(empty($controller->request->data[$this->name]['id'])){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}
					else{
						$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
					}
					return $controller->redirect(array('action' => 'result'));
				}
			}
		}
	}
}
?>
