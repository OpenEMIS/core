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

	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		),
		'TrainingCourse',
		'TrainingPriority',
		'TrainingStatus',
	);
	
	public $validate = array(
		'training_course_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Course.'
			)
		),
		'training_priority_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Priority.'
			)
		)
	);
	
    public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
    }
	
	public $headerDefault = 'Training Needs';
		

	public function trainingNeed($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$header = __($this->headerDefault);
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->staffId);//('all', array('conditions'=> array('staff_id'=> $controller->staffId)));
		
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

		$trainingRequirement = ClassRegistry::init('TrainingRequirement');
		$trainingRequirementOptions = $trainingRequirement->find('list', array('fields'=> array('id', 'name')));
		
		$controller->Session->write('StaffTrainingNeedId', $id);
        $controller->set(compact('header', 'data', 'trainingRequirementOptions', 'id'));

		//APROVAL
		$controller->Workflow->getApprovalWorkflow($this->name, $id);
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
		 	$staffData = $institutionSiteStaff->findAllByStaffId($staffId);//('all', array('recursive'=>-1,'conditions'=>array('staff_id' => $staffId)));
		 	$staffPositionTitleId = array();
		 	foreach($staffData as $val){
		 		if(!empty($val['InstitutionSiteStaff']['position_title_id'])){
		 			$staffPositionTitleId[] = $val['InstitutionSiteStaff']['position_title_id'];
		 		}
		 	}
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
							     'TrainingCourseTargetPopulation.position_title_id' => $staffPositionTitleId,
							     'TrainingCourseTargetPopulation.position_title_table' => 'staff_position_titles'
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
								'TrainingSessionTrainee.identification_id' => $staffId,
								'TrainingSessionTrainee.identification_table' => 'staff',
							)
					)
				),
				'conditions' =>array(
					'TrainingCourse.training_status_id' => 3,
					'TrainingSessionTrainee.id' => null
				))
			);
		}
      //  $trainingCourseId = isset($controller->request->data['StaffTrainingNeed']['training_course_id']) ? $controller->request->data['StaffTrainingNeed']['training_course_id'] : "";
      //	$controller->set('selectedCourse', $trainingCourseId);
		
		$controller->set(compact('trainingPriorityOptions', 'trainingCourseOptions'));

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			$this->recursive = -1;
			$data = $this->findById($id);
			if(!empty($data)){
				$controller->request->data = $data;
			}

		}
		else{
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			
			$this->set($controller->request->data);
			if ($this->validates()){
				if (isset($controller->request->data['save'])) {
				   	$controller->request->data['StaffTrainingNeed']['training_status_id'] = 1; 
				} else if (isset($controller->request->data['submitForApproval'])) {
			      	$controller->request->data['StaffTrainingNeed']['training_status_id'] = 2; 
				}
				
				if($this->save($controller->request->data)){
					$controller->Message->alert('general.add.success');
					return $controller->redirect(array('action' => 'trainingNeed'));
				}
			}
		}
	}

}
?>