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

class StaffTrainingSelfStudy extends StaffAppModel {
	public $actsAs = array('ControllerAction');

	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		),
		'TrainingSession',
		'TrainingStatus',
	);

	public $hasMany = array(
		'StaffTrainingSelfStudyAttachment' => array(
			'className' => 'StaffTrainingSelfStudyAttachment',
			'foreignKey' => 'staff_training_self_study_id',
			'dependent' => true
		)
	);
	
	
	public $validate = array(
		'training_session_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Course.'
			)
		),
		'credit_hours' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Credit Hours.'
			)
		),
		'pass' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Result.'
			)
		),
		'result' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Result.'
			)
		)
	);

	public $headerDefault = 'Training Self Study';
		

	public function trainingSelfStudy($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);
		$data = $this->TrainingSession->find('all',
			array(
				'fields' => array('StaffTrainingSelfStudy.*', 'TrainingCourse.*', 'TrainingStatus.*'),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'staff_training_self_studies',
						'alias' => 'StaffTrainingSelfStudy',
						'conditions' => array(
							'TrainingSession.id = StaffTrainingSelfStudy.training_session_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_courses',
						'alias' => 'TrainingCourse',
						'conditions' => array(
							'TrainingCourse.id = TrainingSession.training_course_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingStatus',
						'conditions' => array(
							'TrainingStatus.id = StaffTrainingSelfStudy.training_status_id'
						)
					)
				),
				'conditions'=> array(
					'StaffTrainingSelfStudy.staff_id'=> $controller->staffId,
				)
			)
		);
		
		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		
	}


	public function trainingSelfStudyView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
	
		$data = $this->TrainingSession->find('first',
			array(
				'fields' => array('StaffTrainingSelfStudy.*', 'TrainingCourse.*', 'TrainingSession.*', 'TrainingStatus.*', 'CreatedUser.*', 'ModifiedUser.*'),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'staff_training_self_studies',
						'alias' => 'StaffTrainingSelfStudy',
						'conditions' => array(
							'TrainingSession.id = StaffTrainingSelfStudy.training_session_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_courses',
						'alias' => 'TrainingCourse',
						'conditions' => array(
							'TrainingCourse.id = TrainingSession.training_course_id'
						)
					),
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingStatus',
						'conditions' => array(
							'TrainingStatus.id = StaffTrainingSelfStudy.training_status_id'
						)
					),
					array(
						'type' => 'LEFT',
						'table' => 'security_users',
						'alias' => 'CreatedUser',
						'conditions' => array(
							'CreatedUser.id = StaffTrainingSelfStudy.created_user_id'
						)
					),
					array(
						'type' => 'LEFT',
						'table' => 'security_users',
						'alias' => 'ModifiedUser',
						'conditions' => array(
							'ModifiedUser.id = StaffTrainingSelfStudy.modified_user_id'
						)
					)
				),
				'conditions'=> array(
					'StaffTrainingSelfStudy.id'=> $id,
				)
			)
		);
		
		
		if(empty($data)){
			$controller->redirect(array('action'=>'trainingSelfStudy'));
		}

		$arrMap = array('model'=>'Staff.StaffTrainingSelfStudyAttachment', 'foreignKey' => 'staff_training_self_study_id');
        $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

        $attachments = $FileAttachment->getList($id);
		
		$controller->Session->write('StaffTrainingSelfStudyId', $id);
		$controller->set('data', $data);
		$controller->set('attachments', $attachments);
		$controller->set('_model','StaffTrainingSelfStudyAttachment');
	}
	


	public function trainingSelfStudyDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffTrainingSelfStudyId')) {
            $id = $controller->Session->read('StaffTrainingSelfStudyId');
            $staffId = $controller->Session->read('StaffId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			
            $trainingCourse = ClassRegistry::init('TrainingCourse');
			$trainingCourses = $trainingCourse->find('first', array('conditions'=> array('TrainingCourse.id' =>$data['TrainingSession']['training_course_id'])));
		
            $name = $trainingCourses['TrainingCourse']['code'] . ' - ' . $trainingCourses['TrainingCourse']['title'];
			
			
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->Session->delete('StaffTrainingSelfStudyId');
            $controller->redirect(array('action' => 'trainingSelfStudy'));
        }
    }



    public function trainingSelfStudyActivate($controller, $params) {
        if($controller->Session->check('StaffTrainingSelfStudyId')) {
            $id = $controller->Session->read('StaffTrainingSelfStudyId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['StaffTrainingSelfStudy']['training_status_id']=='2'){

				$trainingCourse = ClassRegistry::init('TrainingCourse');
				$trainingCourses = $trainingCourse->find('first', array('conditions'=> array('TrainingCourse.id' =>$data['TrainingSession']['training_course_id'])));
			
	            $name = $trainingCourses['TrainingCourse']['code'] . ' - ' . $trainingCourses['TrainingCourse']['title'];
				
	            $this->updateAll(
	    			array('StaffTrainingSelfStudy.training_status_id' => 3),
	    			array('StaffTrainingSelfStudy.id '=> $id)
				);
	            $controller->Utility->alert($name . ' have been activate successfully.');
	        }
            $controller->redirect(array('action' => 'trainingSelfStudy'));
        }
    }

    public function trainingSelfStudyInactivate($controller, $params) {
        if($controller->Session->check('StaffTrainingSelfStudyId')) {
            $id = $controller->Session->read('StaffTrainingSelfStudyId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));

			$trainingCourse = ClassRegistry::init('TrainingCourse');
			$trainingCourses = $trainingCourse->find('first', array('conditions'=> array('TrainingCourse.id' =>$data['TrainingSession']['training_course_id'])));
		
            $name = $trainingCourses['TrainingCourse']['code'] . ' - ' . $trainingCourses['TrainingCourse']['title'];
			
          	$this->updateAll(
    			array('StaffTrainingSelfStudy.training_status_id' => 4),
    			array('StaffTrainingSelfStudy.id '=> $id)
			);
            $controller->Utility->alert($name . ' have been inactivated successfully.');
            $controller->redirect(array('action' => 'trainingSelfStudy'));
        }
    }
	

	public function trainingSelfStudyAdd($controller, $params) {

		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
	}
	

	public function trainingSelfStudyEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$controller->set('modelName', $this->name);

		$trainingCourseOptions = array();
		$trainingSessionTraineeData = array();
		$trainingSessionTrainee = ClassRegistry::init('TrainingSessionTrainee');
		$trainingCreditHour = ClassRegistry::init('TrainingCreditHour');
		$trainingCreditHourOptions = array();

		if($controller->Session->check('StaffId')){
		 	$staffId = $controller->Session->read('StaffId');
		
			$trainingSessionTrainees = $trainingSessionTrainee->find('all',  
				array(
					'recursive' => -1, 
					'fields' => array('TrainingCourse.*', 'TrainingSession.*', 'TrainingSessionTrainee.*'),
					'joins' => array(
						array(
							'type' => 'INNER',
							'table' => 'training_sessions',
							'alias' => 'TrainingSession',
							'conditions' => array('TrainingSession.id = TrainingSessionTrainee.training_session_id')
						),
						array(
							'type' => 'INNER',
							'table' => 'training_courses',
							'alias' => 'TrainingCourse',
							'conditions' => array('TrainingCourse.id = TrainingSession.training_course_id')
						)
					),
					'conditions'=>array(
						'TrainingSessionTrainee.identification_id'=>$staffId,
						'TrainingSessionTrainee.identification_table'=>'staff',
						array('NOT' => 
							array(
								'TrainingSession.training_status_id' => array(1,2)
							)
						)
						
					)
				)
			);

			if(!empty($trainingSessionTrainees)){
				foreach($trainingSessionTrainees as $val){
					$trainingSessionTraineeData = $val['TrainingSessionTrainee'];
					$trainingCreditHours = $trainingCreditHour->find('first', 
						array(
						'fields'=>array('min', 'max'), 
						'recursive' => -1,
						'conditions'=>array('TrainingCreditHour.id' => $val['TrainingCourse']['training_credit_hour_id'])
						)
					);
					$i = 0;
					for($i = $trainingCreditHours['TrainingCreditHour']['min']; $i <= $trainingCreditHours['TrainingCreditHour']['max']; $i++){
						$trainingCreditHourOptions[$i] =  $i;
					}
					$trainingCourseOptions[] = array($val['TrainingSession']['id']=>$val['TrainingCourse']['code']);
				}
			}

		}
		$controller->set('trainingCreditHourOptions', $trainingCreditHourOptions);
		$controller->set('trainingCourseOptions', $trainingCourseOptions);

	  	$trainingCourseId = isset($controller->request->data['StaffTrainingSelfStudy']['training_session_id']) ? $controller->request->data['StaffTrainingSelfStudy']['training_session_id'] : "";
    	$controller->set('selectedCourse', $trainingCourseId);

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
				if($data['StaffTrainingSelfStudy']['training_status_id']!=1){
					return $controller->redirect(array('action' => 'trainingSelfStudyView', $id));
				}
			}else{
				$data = array();
			}

			$controller->request->data = array_merge($data, array('TrainingSessionTrainee' => $trainingSessionTraineeData));

		 	$arrMap = array('model'=>'Staff.StaffTrainingSelfStudyAttachment', 'foreignKey' => 'staff_training_self_study_id');
            $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

            $attachments = $FileAttachment->getList($id);
            $controller->set('attachments',$attachments);
            $controller->set('_model','StaffTrainingSelfStudyAttachment');
		}
		else{
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			$saveData = $controller->request->data;
			unset($saveData['StaffTrainingSelfStudyAttachment']);

			if ($this->save($saveData, array('validate' => 'only'))){
				if (isset($controller->request->data['save'])) {
				   	$controller->request->data['StaffTrainingSelfStudy']['training_status_id'] = 1; 
				} else if (isset($controller->request->data['submitForApproval'])) {
			      	$controller->request->data['StaffTrainingSelfStudy']['training_status_id'] = 2; 
				}

				if($this->save($saveData)){
					$id = null;
					if(isset($saveData['StaffTrainingSelfStudy']['id'])){
						$id = $saveData['StaffTrainingSelfStudy']['id'];
					}
					if(empty($id)){
						$id = $this->getInsertID();
					}
					
	                $arrMap = array('model'=>'Staff.StaffTrainingSelfStudyAttachment', 'foreignKey' => 'staff_training_self_study_id');
	                $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);
	          
	               	$fileData = $params['form'];
	                if(!empty($fileData)){
	                    $errors = $FileAttachment->saveAll($controller->request->data, $fileData, $id);
	                }
					
					if(empty($controller->request->data[$this->name]['id'])){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}
					else{
						$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
					}
					return $controller->redirect(array('action' => 'trainingSelfStudy'));
				}
			}
		}
	}


}
?>