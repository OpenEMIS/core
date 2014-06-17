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
	public $actsAs = array('ControllerAction', 'Datepicker' => array('start_date', 'end_date'));

	public $belongsTo = array(
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		),
		'TrainingAchievementType' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'training_achievement_type_id',
		),
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

	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
		$controller->FileUploader->fileVar = 'files';
		$controller->FileUploader->fileModel = 'StaffTrainingSelfStudyAttachment';
		$controller->FileUploader->allowEmptyUpload = true;
		$controller->FileUploader->additionalFileType();
    }
	
	public function getDisplayFields($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
                array('field' => 'id', 'type' => 'hidden'),
             	array('field' => 'achievement_type',  'labelKey' => 'StaffTrainingSelfStudy.achievement_type'),
                array('field' => 'title',  'labelKey' => 'StaffTraining.course_title'),
				array('field' => 'start_date'),
				array('field' => 'end_date'),
				array('field' => 'description',  'labelKey' => 'StaffTraining.description'),
				array('field' => 'objective',  'labelKey' => 'StaffTraining.objective'),
				array('field' => 'location'),
				array('field' => 'training_provider'),
				array('field' => 'hours'),
				array('field' => 'credit_hours', 'labelKey' => 'StaffTraining.credit_hours'),
                array('field' => 'result'),
                array('field' => 'pass', 'type' => 'select', 'options' => $controller->Option->get('passfail'),'labelKey' => 'StaffTraining.completed'),
                )
			);
		
		 return $fields;
    }
	
	public function getDisplayFields2($controller) {
        $fields = array(
            'model' => $this->alias,
            'fields' => array(
				array('field' => 'file_name', 'model' => 'StaffTrainingSelfStudyAttachment', 'labelKey' => 'general.attachments', 'multi_records' => true, 'type' => 'files', 'url' => array('action' => 'trainingSelfStudyAttachmentsDownload')),
                array('field' => 'modified_by', 'model' => 'ModifiedUser', 'edit' => false),
                array('field' => 'modified', 'edit' => false),
                array('field' => 'created_by', 'model' => 'CreatedUser', 'edit' => false),
                array('field' => 'created', 'edit' => false)
            )
        );
        return $fields;
    }
	
	public $headerDefault = 'Achievements';

	public function autocompleteTrainingProvider($search) {
		$search = sprintf('%%%s%%', $search);

		$list = $this->query(
			"SELECT * FROM(
			SELECT training_provider as 'name' FROM staff_training_self_studies as StaffTrainingSelfStudy UNION Select name as 'name' from training_providers as TrainingProvider where visible = 1 
			)as TrainingProvider
			WHERE name LIKE '" . $search . "'
			order by 'name';");
		

		
		$data = array();
		
		foreach($list as $obj) {
			$trainingProvider = $obj['TrainingProvider']['name'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $trainingProvider)),
				'value' => array('training-provider' => $trainingProvider)
			);
		}

		return $data;
	}


	public function trainingSelfStudy($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$header = __($this->headerDefault);
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));
		$data = $this->findAllByStaffId($controller->staffId);
		$controller->set(compact('header', 'data'));
	}


	public function trainingSelfStudyView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$header = __($this->headerDefault . ' Details');
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
	
		$data = $this->findById($id);
		if(empty($data)){
			$controller->Message->alert('general.noData');
			$controller->redirect(array('action'=>'trainingSelfStudy'));
		}


		$controller->Session->write('StaffTrainingSelfStudyId', $id);
	
		$attachments = $controller->FileUploader->getList(array('conditions' => array('StaffTrainingSelfStudyAttachment.staff_training_self_study_id'=>$id)));
	   	$data['multi_records'] = $attachments;
	   
		$fields = $this->getDisplayFields($controller);
		$fields2 = $this->getDisplayFields2($controller);
        $controller->set(compact('header', 'data', 'fields', 'fields2','id'));
		//APROVAL
		$pending = $data['StaffTrainingSelfStudy']['training_status_id']=='2' ? 'true' : 'false';
		$controller->Workflow->getApprovalWorkflow($this->name, $pending, $id);
		$controller->set('approvalMethod', 'trainingSelfStudy');
		$controller->set('controller', 'Staff');
		$controller->set('plugin', '');
	}
	


	public function trainingSelfStudyDelete($controller, $params) {
        if($controller->Session->check('StaffId') && $controller->Session->check('StaffTrainingSelfStudyId')) {
            $id = $controller->Session->read('StaffTrainingSelfStudyId');
            if ($this->delete($id)) {
				$StaffTrainingSelfStudyAttachment = ClassRegistry::init('StaffTrainingSelfStudyAttachment');
				$StaffTrainingSelfStudyAttachment->deleteAll(array('StaffTrainingSelfStudyAttachment.staff_training_self_study_id' => $id)); 
                $controller->Message->alert('general.delete.success');
            } else {
                $controller->Message->alert('general.delete.failed');
            }
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

	public function trainingSelfStudyAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault);
		$controller->set('header', __('Add '.$this->headerDefault));
		$this->setup_add_edit_form($controller, $params);
	}
	

	public function trainingSelfStudyEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault );
		$controller->set('header', __('Edit '.$this->headerDefault));
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
				return $controller->redirect(array('action'=>'trainingSelfStudyView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}
	
	function setup_add_edit_form($controller, $params){
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
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

		$trainingAchievementTypeOptions = array_map('__', $this->TrainingAchievementType->getList());
		$controller->set('trainingAchievementTypeOptions', $trainingAchievementTypeOptions);

		$passfailOptions = $controller->Option->get('passfail');
		
		$attachments = $controller->FileUploader->getList(array('conditions' => array('StaffTrainingSelfStudyAttachment.staff_training_self_study_id'=>$id)));
		
		$controller->set(compact('trainingCreditHourOptions', 'trainingProviderOptions', 'passfailOptions', 'attachments'));

		if($controller->request->is('get')){
			
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
		}
		else{
			$controller->request->data[$this->name]['staff_id'] = $controller->staffId;
			$saveData = $controller->request->data;

			$postFileData = $saveData['StaffTrainingSelfStudy']['files'];
			unset($saveData['StaffTrainingSelfStudy']['files']);
			$controller->request->data['StaffTrainingSelfStudy']['training_status_id'] = 1; 
			if ($this->save($saveData, array('validate' => 'only'))){
				if (isset($saveData['save'])) {
				   	$saveData['StaffTrainingSelfStudy']['training_status_id'] = 1; 
				} else if (isset($saveData['submitForApproval'])) {
			      	$saveData['StaffTrainingSelfStudy']['training_status_id'] = 2; 
				}
				if($this->save($saveData['StaffTrainingSelfStudy'])){
					
					$id = null;
					if(isset($saveData['StaffTrainingSelfStudy']['id'])){
						$id = $saveData['StaffTrainingSelfStudy']['id'];
					}
					if(empty($id)){
						$id = $this->getInsertID();
					}
					$controller->FileUploader->additionData = array('staff_training_self_study_id' => $id);
					$controller->FileUploader->uploadFile(NULL, $postFileData);
				
					if ($controller->FileUploader->success) {
						$controller->Message->alert('general.add.success');
						return $controller->redirect(array('action' => 'trainingSelfStudy'));
					}
				}
			}
		}
	}
	
	 public function trainingSelfStudyAttachmentsDelete($controller, $params) {
        $this->render = false;
        if ($controller->request->is('post')) {
            $result = array('alertOpt' => array());
            $controller->Utility->setAjaxResult('alert', $result);
            $id = $params->data['id'];

			$StaffTrainingSelfStudyAttachment = ClassRegistry::init('StaffTrainingSelfStudyAttachment');
            if ($StaffTrainingSelfStudyAttachment->delete($id)) {
				$msgData  = $controller->Message->get('FileUplaod.success.delete');
                $result['alertOpt']['text'] = $msgData['msg'];// __('File is deleted successfully.');
            } else {
				$msgData  = $controller->Message->get('FileUplaod.error.delete');
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = $msgData;//__('Error occurred while deleting file.');
            }
			
            return json_encode($result);
        }
    }

	
	public function trainingSelfStudyAttachmentsDownload($controller, $params) {
		$id = $params['pass'][0];
		$this->render = false;
		$controller->FileUploader->downloadFile($id);
    }

	public function trainingSelfStudyAjaxAddField($controller, $params) {
		$this->render =false;
		
		$fileId = $controller->request->data['size'];
		$multiple = true;
		$controller->set(compact('fileId', 'multiple'));
		$controller->render('/Elements/templates/file_upload_field');
	}
}
?>