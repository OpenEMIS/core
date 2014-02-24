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
	

		if(!empty($selectedStatus)){
			$data = $this->find('all', array('order'=> array('TrainingSession.start_date'), 'conditions' => array('TrainingSessionResult.training_status_id' => $selectedStatus)));
		}else{
			$data = $this->find('all', array('order'=> array('TrainingSession.start_date')));
		}
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
			$controller->redirect(array('action'=>'course'));
		}
		
		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
			array(
				'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$id)
			)
		);

		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$trainingCourses = $trainingCourse->find('first',  
			array(
				'conditions'=>array('TrainingCourse.id'=>$data['TrainingSession']['training_course_id'])
			)
		);

		$controller->Session->write('TrainingResultId', $id);
		$controller->set('trainingCourses', $trainingCourses);
		$controller->set('trainingSessionTrainees', $trainingSessionTrainees);
		$controller->set('data', $data);
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
    }
	
	
	public function resultEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}
	
	
	function setup_add_edit_form($controller, $params){
		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$trainingCourseOptions = $trainingCourse->find('list', array('fields'=> array('id', 'code'), 'conditions'=>array('training_status_id'=>3)));
	
		$controller->set('trainingCourseOptions', $trainingCourseOptions);

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
						'recursive' => -1, 
						'conditions'=>array('TrainingSessionTrainee.training_session_id'=>$id)
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
						'conditions'=>array('TrainingSession.id'=>$data['TrainingSessionResult']['id'])
					)
				);

				$trainingSessionTraineesVal = null;
				if(!empty($trainingSessionTrainees)){
					foreach($trainingSessionTrainees as $val){
						$trainingSessionTraineesVal[] = $val['TrainingSessionTrainee'];
					}
				}
				$merge = array_merge(array('TrainingSessionTrainee'=>$trainingSessionTraineesVal), $trainingCourses);
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
					$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
					if($trainingSessionTrainee->saveAll($controller->request->data['TrainingSessionTrainee'])){
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