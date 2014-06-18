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

class StaffTrainingNeed extends StaffAppModel {
	public $actsAs = array('ControllerAction');

	public $trainingNeedTypeOptions = array('1'=>'Course Catalogue', '2'=>'Need Category');

	public $trainingNeedTypes = array('TrainingCourse'=>'Course Catalogue', 'TrainingNeedCategory'=>'Need Category');

	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		),
		'TrainingCourse' => array(
            'className' => 'TrainingCourse',
            'foreignKey' => 'ref_course_id',
            'conditions' => array('ref_course_table' => 'TrainingCourse'),
        ),
        'TrainingNeedCategory' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'ref_course_id',
            'conditions' => array('ref_course_table' => 'TrainingNeedCategory'),
		),
		'Staff.Staff',
		'TrainingPriority',
		'TrainingStatus',
	);
	
	public $validate = array(
		'training_priority_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Priority.'
			)
		)
	);

	 public $validateCourse = array(
		'ref_course_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Course.'
			)
		),
		'ref_course_code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Course Code.'
			)
		),
		'ref_course_title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Course Title.'
			)
		)
  	);

  	public $validateNeed = array(
	    'ref_need_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Need Category.'
			)
		),
		'ref_need_code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Course Code.'
			)
		),
		'ref_need_title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Course Title.'
			)
		)
  	);
	
	public $headerDefault = 'Training Needs';
		

	public function trainingNeed($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$header = __($this->headerDefault);
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->Session->read('Staff.id'));
		$controller->set(compact('header' ,'data'));
	}


	public function trainingNeedView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$header = __($this->headerDefault . ' Details');
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->findById($id);//('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action'=>'trainingNeed'));
		}
		$trainingNeedCategoryOptions = array_map('__', $this->TrainingNeedCategory->getList());
		$trainingNeedTypes = array_map('__', $this->trainingNeedTypes);
		$controller->Session->write('StaffTrainingNeedId', $id);
        $controller->set(compact('header', 'data', 'id', 'trainingNeedTypes', 'trainingNeedCategoryOptions'));

		//APROVAL
		$pending = $data['StaffTrainingNeed']['training_status_id']=='2' ? true : false;
		$controller->Workflow->getApprovalWorkflow($this->name, $pending, $id);
		$controller->set('approvalMethod', 'trainingNeed');
		$controller->set('controller', 'Staff');
		$controller->set('plugin', '');
	}
	


	public function trainingNeedDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffTrainingNeedId')) {
            $id = $controller->Session->read('StaffTrainingNeedId');
            if ($this->delete($id)) {
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
			$controller->Session->delete('StaffTrainingNeedId');
            $controller->redirect(array('action' => 'trainingNeed'));
        }
    }



    public function trainingNeedActivate($controller, $params) {
        if($controller->Session->check('StaffTrainingNeedId')) {
            $id = $controller->Session->read('StaffTrainingNeedId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['StaffTrainingNeed']['training_status_id']=='2'){
	            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
				
	          
	            $this->updateAll(
	    			array('StaffTrainingNeed.training_status_id' => 3),
	    			array('StaffTrainingNeed.id '=> $id)
				);
	            $controller->Utility->alert($name . ' have been activate successfully.');
	        }
            $controller->redirect(array('action' => 'trainingNeed'));
        }
    }

    public function trainingNeedInactivate($controller, $params) {
        if($controller->Session->check('StaffTrainingNeedId')) {
            $id = $controller->Session->read('StaffTrainingNeedId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));


            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
			
              $this->updateAll(
    			array('StaffTrainingNeed.training_status_id' => 4),
    			array('StaffTrainingNeed.id '=> $id)
			);
            $controller->Utility->alert($name . ' have been inactivated successfully.');
            $controller->redirect(array('action' => 'trainingNeed'));
        }
    }
	

	public function trainingNeedAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('header', __('Add '.$this->headerDefault));
		$this->setup_add_edit_form($controller, $params);
	}
	

	public function trainingNeedEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault);
		$controller->set('header', __('Edit '.$this->headerDefault));
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}

	public function trainingNeedApproval($controller, $params){
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
				return $controller->redirect(array('action'=>'trainingNeedView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}
	
	function setup_add_edit_form($controller, $params){
		$trainingPriorityOptions = $this->TrainingPriority->find('list', array('fields'=> array('id', 'name')));
		$trainingCourseOptions = array();
		if($controller->Session->check('StaffId')){
		 	$staffId = $controller->Session->read('StaffId');
		 	$institutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		 	$staffPositionID = $institutionSiteStaff->find('list', 
				array(
					'fields'=>array('institutionSitePosition.staff_position_title_id'),
					'joins' => array(
						array(
							'type' => 'LEFT',
							'table' => 'institution_site_positions',
							'alias' => 'institutionSitePosition',
							'conditions' => array(
								'institutionSitePosition.id = institutionSiteStaff.institution_site_position_id'
							)
						)
					),
					'conditions'=>array('institutionSiteStaff.staff_id'=>$staffId)
				)
			);

		 	$trainingNeedTypeOptions = $this->trainingNeedTypeOptions;

			$trainingCourseOptions = $this->TrainingCourse->find('list', 
				array(
				'fields'=> array('TrainingCourse.id', 'TrainingCourse.title'),
				'joins' => array(
					array(
							'type' => 'LEFT',
							'table' => 'training_course_target_populations',
							'alias' => 'TrainingCourseTargetPopulation',
							'conditions' => array(
								'TrainingCourse.id = TrainingCourseTargetPopulation.training_course_id',
							     'TrainingCourseTargetPopulation.staff_position_title_id' => $staffPositionID
							)
					),
					array(
						'type' => 'LEFT',
							'table' => 'training_sessions',
							'alias' => 'TrainingSession',
							'conditions' => array(
								'TrainingCourse.id = TrainingSession.training_course_id',
								'TrainingSession.training_status_id' => 3,
							)
					),
					array(
						'type' => 'LEFT',
							'table' => 'training_session_trainees',
							'alias' => 'TrainingSessionTrainee',
							'conditions' => array(
								'TrainingSession.id = TrainingSessionTrainee.training_session_id',
								'TrainingSessionTrainee.staff_id' => $staffId
							)
					)
				),
				'conditions' =>array(
					'TrainingCourse.training_status_id' => 3,
					'TrainingSessionTrainee.id' => null
				))
			);
		}

		$trainingNeedCategoryOptions = array_map('__', $this->TrainingNeedCategory->getList());
		$controller->set(compact('trainingPriorityOptions', 'trainingCourseOptions', 'trainingNeedTypeOptions', 'trainingNeedCategoryOptions'));

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}
			$controller->request->data['StaffTrainingNeed']['training_need_type'] = '1';
			if(!empty($data)){
				if($data['StaffTrainingNeed']['ref_course_table']=='TrainingNeedCategory'){
					$controller->request->data['StaffTrainingNeed']['training_need_type'] = '2';
					$controller->request->data['StaffTrainingNeed']['ref_need_id'] = $data['StaffTrainingNeed']['ref_course_table'];
					$controller->request->data['StaffTrainingNeed']['ref_need_code']= $data['StaffTrainingNeed']['ref_course_code'];
					$controller->request->data['StaffTrainingNeed']['ref_need_title']= $data['StaffTrainingNeed']['ref_course_title'];
					$controller->request->data['StaffTrainingNeed']['ref_need_description']= $data['StaffTrainingNeed']['ref_course_description'];
					$controller->request->data['StaffTrainingNeed']['ref_need_requirement']= $data['StaffTrainingNeed']['ref_course_requirement'];
				}
			}
		}else{
			$controller->request->data[$this->name]['staff_id'] = $controller->Session->read('Staff.id');
			
			$data = $controller->request->data;
			

			$data[$this->name]['ref_course_table'] = 'TrainingCourse';
			$controller->request->data[$this->name]['training_status_id'] = 1;

			if($data[$this->name]['training_need_type']=='2'){
				$data[$this->name]['ref_course_table'] = 'TrainingNeedCategory';
				$data[$this->name]['ref_course_id'] = $data[$this->name]['ref_need_id'];
				$data[$this->name]['ref_course_code'] = $data[$this->name]['ref_need_code'];
				$data[$this->name]['ref_course_title'] = $data[$this->name]['ref_need_title'];
				$data[$this->name]['ref_course_description'] = $data[$this->name]['ref_need_description'];
				$data[$this->name]['ref_course_requirement'] = $data[$this->name]['ref_need_requirement'];
			}
			$this->set($data);

			if ($this->validates()){
				if (isset($data['save'])) {
				   	$data['StaffTrainingNeed']['training_status_id'] = 1; 
				} else if (isset($data['submitForApproval'])) {
			      	$data['StaffTrainingNeed']['training_status_id'] = 2; 
				}
				
				if($this->save($data)){
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => 'trainingNeed'));
				}
			}
		}
	}


				
	public function beforeValidate($options = array()) {
	 	if($this->data[$this->name]['training_need_type']=='1'){
          $this->validate = array_merge($this->validate, $this->validateCourse);
      	}else if($this->data[$this->name]['training_need_type']=='2'){
          $this->validate = array_merge($this->validate, $this->validateNeed);
      	}
	}

}
?>