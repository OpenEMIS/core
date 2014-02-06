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

	public $headerDefault = 'Training Needs';
		

	public function trainingNeed($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);
		$data = $this->find('all', array('conditions'=> array('staff_id'=> $controller->staffId)));
		
		$trainingCreditHour = ClassRegistry::init('TrainingCreditHour');
		$trainingCreditHourOptions = $trainingCreditHour->find('list', array('fields'=> array('id', 'name')));
		
		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('trainingCreditHourOptions', $trainingCreditHourOptions);
		
	}


	public function trainingNeedView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'trainingNeed'));
		}
		
		$controller->Session->write('StaffTrainingNeedId', $id);
		$controller->set('data', $data);
	}
	


	public function trainingNeedDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffTrainingNeedId')) {
            $id = $controller->Session->read('StaffTrainingNeedId');
            $staffId = $controller->Session->read('StaffId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			
            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
			
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
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

		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
	}
	

	public function trainingNeedEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}
	
	function setup_add_edit_form($controller, $params){
		$controller->set('modelName', $this->name);
		
		$trainingPriority = ClassRegistry::init('TrainingPriority');
		$trainingPriorityOptions = $trainingPriority->find('list', array('fields'=> array('id', 'name')));
		$trainingCourse = ClassRegistry::init('TrainingCourse');
		$trainingCourseOptions = array();
		if($controller->Session->check('StaffId')){
		 	$staffId = $controller->Session->read('StaffId');
		 	$institutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
		 	$staffData = $institutionSiteStaff->find('all', array('recursive'=>-1,'conditions'=>array('staff_id' => $staffId)));
		 	$staffPositionTitleId = array();
		 	foreach($staffData as $val){
		 		if(!empty($val['InstitutionSiteStaff']['position_title_id'])){
		 			$staffPositionTitleId[] = $val['InstitutionSiteStaff']['position_title_id'];
		 		}
		 	}
			$trainingCourseOptions = $trainingCourse->find('list', 
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
						)
					)
				)
			);
		}
		$controller->set('trainingPriorityOptions', $trainingPriorityOptions);
		$controller->set('trainingCourseOptions', $trainingCourseOptions);
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
			if ($this->save($controller->request->data, array('validate' => 'only'))){
				if (isset($controller->request->data['save'])) {
				   	$controller->request->data['StaffTrainingNeed']['training_status_id'] = 1; 
				} else if (isset($controller->request->data['submitForApproval'])) {
			      	$controller->request->data['StaffTrainingNeed']['training_status_id'] = 2; 
				}
				if($this->save($controller->request->data)){
					if(empty($controller->request->data[$this->name]['id'])){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}
					else{
						$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
					}
					return $controller->redirect(array('action' => 'trainingNeed'));
				}
			}
		}
	}


}
?>