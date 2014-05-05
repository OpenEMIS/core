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

class TeacherTrainingSelfStudy extends TeachersAppModel {
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
		'TeacherTrainingSelfStudyAttachment' => array(
			'className' => 'TeacherTrainingSelfStudyAttachment',
			'foreignKey' => 'teacher_training_self_study_id',
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
				'message' => 'Please enter a valid Credits.'
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
		$data = $this->find('all',
			array(
				'conditions'=> array(
					'TeacherTrainingSelfStudy.teacher_id'=> $controller->teacherId,
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
					'TeacherTrainingSelfStudy.id'=> $id,
				)
			)
		);
		
		
		if(empty($data)){
			$controller->redirect(array('action'=>'trainingSelfStudy'));
		}

		$arrMap = array('model'=>'Teachers.TeacherTrainingSelfStudyAttachment', 'foreignKey' => 'teacher_training_self_study_id');
        $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

        $attachments = $FileAttachment->getList($id);
		
		$controller->Session->write('TeacherTrainingSelfStudyId', $id);
		$controller->set('data', $data);
		$controller->set('attachments', $attachments);
		$controller->set('_model','TeacherTrainingSelfStudyAttachment');

		//APROVAL
		$controller->Workflow->getApprovalWorkflow($this->name, $id);
		$controller->set('approvalMethod', 'trainingSelfStudy');
		$controller->set('controller', 'Teachers');
		$controller->set('plugin', '');
	}
	


	public function trainingSelfStudyDelete($controller, $params) {
        if($controller->Session->check('TeacherId') && $controller->Session->check('TeacherTrainingSelfStudyId')) {
            $id = $controller->Session->read('TeacherTrainingSelfStudyId');
            $teacherId = $controller->Session->read('TeacherId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			
          	$name = $data['TeacherTrainingSelfStudy']['title'];
					
            $this->delete($id);
            $controller->Utility->alert($name . ' have been deleted successfully.');
			$controller->Session->delete('TeacherTrainingSelfStudyId');
            $controller->redirect(array('action' => 'trainingSelfStudy'));
        }
    }



    public function trainingSelfStudyActivate($controller, $params) {
        if($controller->Session->check('TeacherTrainingSelfStudyId')) {
            $id = $controller->Session->read('TeacherTrainingSelfStudyId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['TeacherTrainingSelfStudy']['training_status_id']=='2'){
	            $name = $data['TeacherTrainingSelfStudy']['title'];
				
	            $this->updateAll(
	    			array('TeacherTrainingSelfStudy.training_status_id' => 3),
	    			array('TeacherTrainingSelfStudy.id '=> $id)
				);
	            $controller->Utility->alert($name . ' have been activate successfully.');
	        }
            $controller->redirect(array('action' => 'trainingSelfStudy'));
        }
    }

    public function trainingSelfStudyInactivate($controller, $params) {
        if($controller->Session->check('TeacherTrainingSelfStudyId')) {
            $id = $controller->Session->read('TeacherTrainingSelfStudyId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));

			 $name = $data['TeacherTrainingSelfStudy']['title'];
				
            $this->updateAll(
    			array('TeacherTrainingSelfStudy.training_status_id' => 4),
    			array('TeacherTrainingSelfStudy.id '=> $id)
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

	public function trainingSelfStudyApproval($controller, $params){
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
				return $controller->redirect(array('action'=>'trainingSelfStudy', $saveData['WorkflowLog']['record_id']));
			}
		}
	}
	
	function setup_add_edit_form($controller, $params){
		$controller->set('modelName', $this->name);

		$trainingCreditHourOptions = array();

		if($controller->Session->check('TeacherId')){
		 	$teacherId = $controller->Session->read('TeacherId');
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
				if($data['TeacherTrainingSelfStudy']['training_status_id']!=1){
					return $controller->redirect(array('action' => 'trainingSelfStudyView', $id));
				}
			}else{
				$data = array();
			}

			$controller->request->data = array_merge($data);

		 	$arrMap = array('model'=>'Teachers.TeacherTrainingSelfStudyAttachment', 'foreignKey' => 'teacher_training_self_study_id');
            $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

            $attachments = $FileAttachment->getList($id);
            $controller->set('attachments',$attachments);
            $controller->set('_model','TeacherTrainingSelfStudyAttachment');
		}
		else{
			$controller->request->data[$this->name]['teacher_id'] = $controller->teacherId;
			$saveData = $controller->request->data;
			unset($saveData['TeacherTrainingSelfStudyAttachment']);
			if ($this->save($saveData, array('validate' => 'only'))){
				if (isset($saveData['save'])) {
				   	$saveData['TeacherTrainingSelfStudy']['training_status_id'] = 1; 
				} else if (isset($saveData['submitForApproval'])) {
			      	$saveData['TeacherTrainingSelfStudy']['training_status_id'] = 2; 
				}

				if($this->save($saveData)){
					$id = null;
					if(isset($saveData['TeacherTrainingSelfStudy']['id'])){
						$id = $saveData['TeacherTrainingSelfStudy']['id'];
					}
					if(empty($id)){
						$id = $this->getInsertID();
					}
					
	                $arrMap = array('model'=>'Teachers.TeacherTrainingSelfStudyAttachment', 'foreignKey' => 'teacher_training_self_study_id');
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