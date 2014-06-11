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

class TrainingCourse extends TrainingAppModel {
	//public $useTable = 'student_health_histories';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'TrainingFieldStudy',
		'TrainingCourseType',
		'TrainingModeDelivery',
		'TrainingRequirement',
		'TrainingLevel',
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

	public $hasMany = array(
		'TrainingCourseAttachment' => array(
			'className' => 'TrainingCourseAttachment',
			'foreignKey' => 'training_course_id',
			'dependent' => true
		),
		'TrainingCoursePrerequisite' => array(
			'className' => 'TrainingCoursePrerequisite',
			'foreignKey' => 'training_course_id',
			'dependent' => true
		),
		'TrainingCourseTargetPopulation' => array(
			'className' => 'TrainingCourseTargetPopulation',
			'foreignKey' => 'training_course_id',
			'dependent' => true
		),
		'TrainingCourseProvider' => array(
			'className' => 'TrainingCourseProvider',
			'foreignKey' => 'training_course_id',
			'dependent' => true
		),
		'StaffTrainingNeed' => array(
    	 	'foreignKey' => 'ref_course_id',
            'conditions' => array('ref_course_table' => 'TrainingCourse'),
            'dependent' => true
        ),
		'TrainingSession' => array(
			'className' => 'TrainingSession',
			'foreignKey' => 'training_course_id',
			'dependent' => true
		)
	);
	
	public $validate = array(
		'code' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Course Code.'
			),
			'ruleUnique' => array(
				'rule' => 'isUnique',
				'required' => true,
			  	'on' => 'create',
				'message' => 'Please enter a new Course Code.'
			)
			
		),
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Course Title.'
			)
		),
		'training_field_study_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Category / Field of Study.'
			)
		),
		'training_course_type_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Course Type.'
			)
		),
		'credit_hours' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Credits.'
			)
		),
		'training_mode_delivery_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Mode of Delivery.'
			)
		),
		'training_requirement_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Training Requirement.'
			)
		),
		'training_level_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Training Level.'
			)
		),
		'pass_result' => array(
			'ruleRequired' => array(
				'rule' => 'numeric',
				'required' => true,
				'message' => 'Please enter a valid Pass Result.'
			)
		)
	);
		
	public $headerDefault = 'Courses';

	public function beforeAction($controller, $action) {
        $controller->set('model', $this->alias);
		$controller->FileUploader->fileVar = 'files';
		$controller->FileUploader->fileModel = 'TrainingCourseAttachment';
		$controller->FileUploader->allowEmptyUpload = true;
		$controller->FileUploader->additionalFileType();
    }
	

	public function autocomplete($search,$index) {
		$search = sprintf('%%%s%%', $search);
		$list = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT TrainingCourse.code', 'TrainingCourse.title', 'TrainingCourse.id'),
			'conditions' => array(
				'OR' => array(
					'TrainingCourse.code LIKE' => $search,
					'TrainingCourse.title LIKE' => $search
				),
				'TrainingCourse.training_status_id' => 3
			),
			'order' => array('TrainingCourse.code', 'TrainingCourse.title')
		));

		
		$data = array();
		
		foreach($list as $obj) {
			$trainingCourseId = $obj['TrainingCourse']['id'];
			$trainingCourseCode = $obj['TrainingCourse']['code'];
			$trainingCourseTitle = $obj['TrainingCourse']['title'];
			
			$data[] = array(
				'label' => trim(sprintf('%s - %s', $trainingCourseCode, $trainingCourseTitle)),
				'value' => array('training-course-id-'.$index => $trainingCourseId, 'course-code-'.$index=>$trainingCourseCode,'course-title-'.$index=>$trainingCourseTitle,'training-course-title-'.$index => trim(sprintf('%s - %s', $trainingCourseCode, $trainingCourseTitle)))
			);
		}

		return $data;
	}



	public function autocompletePosition($search, $index) {
		$search = sprintf('%%%s%%', $search);

		$list = $this->query(
			"SELECT * FROM(
			SELECT *, 'staff_position_titles' as position_table FROM staff_position_titles as StaffPositionTitle UNION Select *, 'teacher_position_titles' as position_table from teacher_position_titles as TeacherPositionTitle 
			)as TrainingPosition
			WHERE name LIKE '" . $search . "' and visible = 1
			order by 'order';");
		

		
		$data = array();
		
		foreach($list as $obj) {
			$positionTitleId = $obj['TrainingPosition']['id'];
			$positionTitleName = $obj['TrainingPosition']['name'];
			$positionTitleTable= $obj['TrainingPosition']['position_table'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $positionTitleName)),
				'value' => array('position-title-id-'.$index => $positionTitleId, 'position-title-name-'.$index => $positionTitleName, 'position-title-table-'.$index => $positionTitleTable,
				'position-title-validate-'.$index => $positionTitleTable . '_' . $positionTitleId)
			);
		}

		return $data;
	}
	
	public function course($controller, $params) {
	//	pr('aas');
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);


		$trainingStatus = ClassRegistry::init('TrainingStatus');
		$statusOptions = $trainingStatus->find('list', array('fields'=>array('id', 'name')));
		$selectedStatus = empty($params['pass'][0])? null:$params['pass'][0];
	
		if(!empty($selectedStatus)){
			$data = $this->find('all', array('order'=> array('code', 'title'), 'conditions' => array('TrainingCourse.training_status_id' => $selectedStatus)));
		}else{
			$data = $this->find('all', array('order'=> array('code', 'title')));
		}

		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('statusOptions', $statusOptions);
		$controller->set('selectedStatus', $selectedStatus);
		
	}


  
    public function coursePrequisiteDelete() {
        $this->autoRender = false;
        if($this->request->is('post')) {
            $result = array('alertOpt' => array());
            $this->Utility->setAjaxResult('alert', $result);
            $id = $this->params->data['id'];

            $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
            $FileAttachment = $this->Components->load('FileAttachment', $arrMap);
            
            if($FileAttachment->delete($id)) {
                $result['alertOpt']['text'] = __('File is deleted successfully.');
            } else {
                $result['alertType'] = $this->Utility->getAlertType('alert.error');
                $result['alertOpt']['text'] = __('Error occurred while deleting file.');
            }
            
            return json_encode($result);
        }
    }
        
    public function attachmentsLeaveDownload($id) {
        $arrMap = array('model'=>'Staff.StaffLeaveAttachment', 'foreignKey' => 'staff_leave_id');
        $FileAttachment = $this->Components->load('FileAttachment', $arrMap);

        $FileAttachment->download($id);
    }
       


	public function courseView($controller, $params){
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$controller->set('modelName', $this->name);
		
		$id = empty($params['pass'][0])? 0:$params['pass'][0];
		$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
		
		if(empty($data)){
			$controller->redirect(array('action'=>'course'));
		}
		
		$controller->Session->write('TrainingCourseId', $id);
		$trainingCourseTargetPopulation = ClassRegistry::init('TrainingCourseTargetPopulation');
		$trainingCourseTargetPopulations = $trainingCourseTargetPopulation->find('all', array('conditions'=>array('TrainingCourseTargetPopulation.training_course_id'=>$id)));

		$teacherPositionTitle = ClassRegistry::init('TeacherPositionTitle');
		$teacherPositionTitles = $teacherPositionTitle->find('list', array('fields'=>array('id', 'name')));
		
		$staffPositionTitle = ClassRegistry::init('StaffPositionTitle');
		$staffPositionTitles = $staffPositionTitle->find('list', array('fields'=>array('id', 'name')));

		$trainingCoursePrerequisite = ClassRegistry::init('TrainingCoursePrerequisite');
		$trainingCoursePrerequisites = $trainingCoursePrerequisite->find('all',  
					array(
						'fields' => array('TrainingPrerequisiteCourse.*', 'TrainingCoursePrerequisite.*'),
						'joins' => array(
							array(
								'type' => 'INNER',
								'table' => 'training_courses',
								'alias' => 'TrainingPrerequisiteCourse',
								'conditions' => array('TrainingPrerequisiteCourse.id = TrainingCoursePrerequisite.training_prerequisite_course_id')
							)
						),
						'conditions'=>array('TrainingCoursePrerequisite.training_course_id'=>$id)
					)
				);

		$trainingCourseProvider = ClassRegistry::init('TrainingCourseProvider');
		$trainingCourseProviders = $trainingCourseProvider->find('all', array('conditions'=>array('TrainingCourseProvider.training_course_id'=>$id)));

		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviders = $trainingProvider->find('list', array('fields'=>array('id', 'name')));

		$arrMap = array('model'=>'Training.TrainingCourseAttachment', 'foreignKey' => 'training_course_id');
        $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

        $attachments = $FileAttachment->getList($id);

		$controller->set('data', $data);
		$controller->set('trainingCourseTargetPopulations', $trainingCourseTargetPopulations);
		$controller->set('teacherPositionTitles', $teacherPositionTitles);
		$controller->set('staffPositionTitles', $staffPositionTitles);
		$controller->set('trainingCoursePrerequisites', $trainingCoursePrerequisites);
		$controller->set('trainingCourseProviders', $trainingCourseProviders);
		$controller->set('trainingProviders', $trainingProviders);
		$controller->set('attachments', $attachments);
		$controller->set('_model','TrainingCourseAttachment');

		//APROVAL
		$pending = $data['TrainingCourse']['training_status_id']=='2' ? 'true' : 'false';
		$controller->Workflow->getApprovalWorkflow($this->name, $pending, $id);
		$controller->set('approvalMethod', 'course');
		$controller->set('controller', 'Training');
		$controller->set('plugin', 'Training');
	}
	
	public function courseDelete($controller, $params) {
        if($controller->Session->check('TrainingCourseId')) {
            $id = $controller->Session->read('TrainingCourseId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['TrainingCourse']['training_status_id']=='1'){
	            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
				
	            $this->delete($id);
	            $controller->Utility->alert($name . ' have been deleted successfully.');
				$controller->Session->delete('TrainingCourseId');
			}
            $controller->redirect(array('action' => 'course'));
        }
    }

    public function courseActivate($controller, $params) {
        if($controller->Session->check('TrainingCourseId')) {
            $id = $controller->Session->read('TrainingCourseId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));
			if($data['TrainingCourse']['training_status_id']=='2'){
	            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
				
	          
	            $this->updateAll(
	    			array('TrainingCourse.training_status_id' => 3),
	    			array('TrainingCourse.id '=> $id)
				);
	            $controller->Utility->alert($name . ' have been activated successfully.');
	        }
            $controller->redirect(array('action' => 'course'));
        }
    }

    public function courseInactivate($controller, $params) {
        if($controller->Session->check('TrainingCourseId')) {
            $id = $controller->Session->read('TrainingCourseId');
			
			$data = $this->find('first',array('conditions' => array($this->name.'.id' => $id)));



            $name = $data['TrainingCourse']['code'] . ' - ' . $data['TrainingCourse']['title'];
			
              $this->updateAll(
    			array('TrainingCourse.training_status_id' => 4),
    			array('TrainingCourse.id '=> $id)
			);
            $controller->Utility->alert($name . ' have been inactivated successfully.');
            $controller->redirect(array('action' => 'course'));
        }
    }
	
	public function courseAdd($controller, $params) {
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);

	}
	
	public function courseEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}

	public function courseApproval($controller, $params){
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
				return $controller->redirect(array('action'=>'courseView', $saveData['WorkflowLog']['record_id']));
			}
		}
	}
	
	function setup_add_edit_form($controller, $params){
		$trainingFieldStudy = ClassRegistry::init('TrainingFieldStudy');
		$trainingFieldStudyOptions = $trainingFieldStudy->find('list', array('fields'=> array('id', 'name')));
		
		$trainingModeDelivery = ClassRegistry::init('TrainingModeDelivery');
		$trainingModeDeliveryOptions = $trainingModeDelivery->find('list', array('fields'=> array('id', 'name')));
		
		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviderOptions = $trainingProvider->find('list', array('fields'=> array('id', 'name')));
		
		$trainingRequirement = ClassRegistry::init('TrainingRequirement');
		$trainingRequirementOptions = $trainingRequirement->find('list', array('fields'=> array('id', 'name')));

		$trainingLevel = ClassRegistry::init('TrainingLevel');
		$trainingLevelOptions = $trainingLevel->find('list', array('fields'=> array('id', 'name')));


		$teacherPositionTitle = ClassRegistry::init('TeacherPositionTitle');
		$teacherPositionTitles = $teacherPositionTitle->find('list', array('fields'=>array('id', 'name')));

		$staffPositionTitle = ClassRegistry::init('StaffPositionTitle');
		$staffPositionTitles = $staffPositionTitle->find('list', array('fields'=>array('id', 'name')));

		$trainingCourseType = ClassRegistry::init('TrainingCourseType');
		$trainingCourseTypeOptions = $trainingCourseType->find('list', array('fields'=> array('id', 'name')));
	

		$configItem = ClassRegistry::init('ConfigItem');
	 	$credit_hours = $configItem->field('ConfigItem.value', array('ConfigItem.name' => 'training_credit_hour'));

	 	$trainingCreditHourOptions = array();
	 	for($i=0;$i<=$credit_hours;$i++){
 			$trainingCreditHourOptions[$i] = $i;
	 	}

	 	
		$controller->set(compact('trainingFieldStudyOptions', 'trainingModeDeliveryOptions', 'trainingProviderOptions', 
		'trainingRequirementOptions', 'trainingLevelOptions', 'teacherPositionTitles', 'staffPositionTitles', 'trainingCourseTypeOptions', 'trainingCreditHourOptions'));
	

		$controller->set('modelName', $this->name);

		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];

			//==================================================

			$this->recursive = -1;
			$data = $this->findById($id);

			$attachments = $controller->FileUploader->getList(array('conditions' => array('TrainingCourseAttachment.training_course_id'=>$id)));
		
			$controller->set('attachments', $attachments);

			if(!empty($data)){
				if($data['TrainingCourse']['training_status_id']!=1){
					return $controller->redirect(array('action' => 'courseView', $id));
				}
				$controller->request->data = $data;
				$trainingCourseTargetPopulations = $this->TrainingCourseTargetPopulation->find('all', array('conditions'=>array('TrainingCourseTargetPopulation.training_course_id'=>$id)));

				$trainingCoursePrerequisite = ClassRegistry::init('TrainingCoursePrerequisite');
				$trainingCoursePrerequisites = $trainingCoursePrerequisite->find('all',  
					array(
						'fields' => array('TrainingPrerequisiteCourse.*', 'TrainingCoursePrerequisite.*'),
						'joins' => array(
							array(
								'type' => 'INNER',
								'table' => 'training_courses',
								'alias' => 'TrainingPrerequisiteCourse',
								'conditions' => array('TrainingPrerequisiteCourse.id = TrainingCoursePrerequisite.training_prerequisite_course_id')
							)
						),
						'conditions'=>array('TrainingCoursePrerequisite.training_course_id'=>$id)
					)
				);
				$trainingCourseTargetPopulationsVal = null;
				if(!empty($trainingCourseTargetPopulations)){
					foreach($trainingCourseTargetPopulations as $val){
						$trainingCourseTargetPopulationsVal[] = $val['TrainingCourseTargetPopulation'];
					}
				}

				$trainingCoursePrerequisitesVal = null;
				if(!empty($trainingCoursePrerequisites)){
					$temp = array();
					foreach($trainingCoursePrerequisites as $val){
						$temp = $val['TrainingCoursePrerequisite'];
						$temp['code'] = $val['TrainingPrerequisiteCourse']['code'];
						$temp['title'] = $val['TrainingPrerequisiteCourse']['title'];
						$trainingCoursePrerequisitesVal[] = $temp;
					}
				}

				$trainingCourseProviders = $this->TrainingCourseProvider->find('all', array('conditions'=>array('TrainingCourseProvider.training_course_id'=>$id)));
				$trainingCourseProvidersVal = null;
				if(!empty($trainingCourseProviders)){
					foreach($trainingCourseProviders as $val){
						$trainingCourseProvidersVal[] = $val['TrainingCourseProvider'];
					}
				}

			  	$arrMap = array('model'=>'Training.TrainingCourseAttachment', 'foreignKey' => 'training_course_id');
	            $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

	            $attachments = $FileAttachment->getList($id);
	            $controller->set('attachments',$attachments);
	            $controller->set('_model','TrainingCourseAttachment');


				$merge = array_merge(array('TrainingCourseTargetPopulation'=>$trainingCourseTargetPopulationsVal), array('TrainingCoursePrerequisite'=>$trainingCoursePrerequisitesVal)
					, array('TrainingCourseProvider'=>$trainingCourseProvidersVal));
				$controller->request->data = array_merge($data, $merge);
			}
		}
		else{
			$saveData = $controller->request->data;
			$postFileData = $saveData['TrainingCourseAttachment']['files'];
			unset($saveData['TrainingCourseAttachment']['files']);

			if ($this->saveAll($saveData, array('validate' => 'only'))){
				if (isset($saveData['save'])) {
				   	$saveData['TrainingCourse']['training_status_id'] = 1; 
				} else if (isset($saveData['submitForApproval'])) {
			      	$saveData['TrainingCourse']['training_status_id'] = 2; 
				}
				$controller->FileUploader->additionData = array('training_course_id' => $id);
				$controller->FileUploader->uploadFile(NULL, $postFileData);
				if($this->saveAll($saveData) && $controller->FileUploader->success){
					$id = null;
					if(isset($saveData['TrainingCourse']['id'])){
						$id = $saveData['TrainingCourse']['id'];
					}
					if(empty($id)){
						$id = $this->getInsertID();
					}
					
					$staffTrainingNeed = ClassRegistry::init('StaffTrainingNeed');
					$staffTrainingNeed->updateAll(
		    			array(
		    				'StaffTrainingNeed.ref_course_code' => $saveData['TrainingCourse']['code'], 
		    				'StaffTrainingNeed.ref_course_title' => $saveData['TrainingCourse']['title'], 
		    				'StaffTrainingNeed.ref_course_description' => $saveData['TrainingCourse']['description'], 
		    				'StaffTrainingNeed.ref_course_requirement' => $trainingRequirementOptions[$saveData['TrainingCourse']['training_requirement_id']], 
		    			),
		    			array('StaffTrainingNeed.ref_course_id '=> $id, 'StaffTrainingNeed.ref_course_table'=> 'TrainingCourse')
					);

					if(isset($controller->request->data['DeleteTargetPopulation'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteTargetPopulation'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingCourseTargetPopulation->deleteAll(array('TrainingCourseTargetPopulation.id' => $deletedId), false);
					}
					if(isset($controller->request->data['DeletePrerequisite'])){
						$deletedId = array();
						foreach($controller->request->data['DeletePrerequisite'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingCoursePrerequisite->deleteAll(array('TrainingCoursePrerequisite.id' => $deletedId), false);
					}
					if(isset($controller->request->data['DeleteProvider'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteProvider'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingCourseProvider->deleteAll(array('TrainingCourseProvider.id' => $deletedId), false);
					}
					if(empty($controller->request->data[$this->name]['id'])){
						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));	
					}
					else{
						$controller->Utility->alert($controller->Utility->getMessage('UPDATE_SUCCESS'));	
					}
					return $controller->redirect(array('action' => 'course'));
				}
			}
		}
	}
}
