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
		'TrainingProvider',
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
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Course title.'
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
		'hours' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter the Hours.'
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

	function field_comparison($check1, $operator, $field2) {
        foreach($check1 as $key=>$value1) {
            $value2 = $this->data[$this->alias][$field2];
            if (!Validation::comparison($value1, $operator, $value2))
                return false;
        }
        return true;
    }

	public $headerDefault = 'Training Achievements';
		

	public function trainingSelfStudy($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);
		$data = $this->find('all', array(
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
	
		$data = $this->find('first',
			array(
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
			
            $name = $data['StaffTrainingSelfStudy']['title'];
				
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
			
	            $name = $data['StaffTrainingSelfStudy']['title'];
				
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

			$name = $data['StaffTrainingSelfStudy']['title'];
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

		$trainingCreditHourOptions = array();
		if($controller->Session->check('StaffId')){
		 	$staffId = $controller->Session->read('StaffId');
		}
		$i = 0;
		$configItem = ClassRegistry::init('ConfigItem');
		$credit_hours = $configItem->field('ConfigItem.value', array('ConfigItem.name' => 'training_credit_hour'));
		for($i = 0; $i <= $credit_hours; $i++){
			$trainingCreditHourOptions[$i] =  $i;
		}

		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviderOptions = $trainingProvider->find('list', array('fields'=>array('id', 'name')));
		$controller->set('trainingCreditHourOptions', $trainingCreditHourOptions);
		$controller->set('trainingProviderOptions', $trainingProviderOptions);

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

			$controller->request->data = array_merge($data);

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
				if (isset($saveData['save'])) {
				   	$saveData['StaffTrainingSelfStudy']['training_status_id'] = 1; 
				} else if (isset($saveData['submitForApproval'])) {
			      	$saveData['StaffTrainingSelfStudy']['training_status_id'] = 2; 
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