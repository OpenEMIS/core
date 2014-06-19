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
			'dependent' => true,
			'exclusive' => true
		),
		'TrainingCoursePrerequisite' => array(
			'dependent' => true,
			'exclusive' => true
		),
		'TrainingCourseTargetPopulation' => array(
			'dependent' => true,
			'exclusive' => true
		),
		'TrainingCourseResultType' => array(
			'dependent' => true,
			'exclusive' => true
		),
		'TrainingCourseProvider' => array(
			'dependent' => true,
			'exclusive' => true
		),
		'StaffTrainingNeed' => array(
    	 	'foreignKey' => 'ref_course_id',
            'conditions' => array('ref_course_table' => 'TrainingCourse'),
           'dependent' => true,
			'exclusive' => true
        ),
		'TrainingSession' => array(
			'dependent' => true,
			'exclusive' => true
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

		$staffPositionTitle = ClassRegistry::init('StaffPositionTitle');

		$list = $staffPositionTitle->find('all', array(
			'recursive' => -1,
			'fields' => array('DISTINCT StaffPositionTitle.name', 'StaffPositionTitle.id'),
			'conditions' => array(
				'StaffPositionTitle.name LIKE' => $search,
				'StaffPositionTitle.visible' => 1
			),
			'order' => array('StaffPositionTitle.name')
		));
		
		$data = array();
		
		foreach($list as $obj) {
			$positionTitleId = $obj['StaffPositionTitle']['id'];
			$positionTitleName = $obj['StaffPositionTitle']['name'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $positionTitleName)),
				'value' => array('position-title-id-'.$index => $positionTitleId, 'position-title-name-'.$index => $positionTitleName,
				'position-title-validate-'.$index => $positionTitleId)
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
			$data = $this->find('all', array('order'=> array('code', 'title')));
		}else{
			$data = $this->find('all', array('order'=> array('code', 'title'), 'conditions' => array('NOT' => array('TrainingCourse.training_status_id' => '4'))));
		}

		$conditions = array();
		if(!empty($selectedStatus)){
			$conditions['TrainingCourse.training_status_id'] = $selectedStatus;
		}else{
			$conditions['NOT']['TrainingCourse.training_status_id'] = 4;
		}
		
		$data = $this->find('all', 
			array(
				'recursive' => -1, 
				'fields' => array('TrainingCourse.*', 'TrainingStatus.*'),
				'joins' => array(
					array(
						'type' => 'INNER',
						'table' => 'training_statuses',
						'alias' => 'TrainingStatus',
						'conditions' => array('TrainingStatus.id = TrainingCourse.training_status_id')
					)
				),
				'order'=> array('TrainingCourse.code', 'TrainingCourse.title', 'TrainingCourse.credit_hours', 'TrainingCourse.training_status_id'), 
				'conditions' => $conditions
			)
		);

		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('statusOptions', $statusOptions);
		$controller->set('selectedStatus', $selectedStatus);
		
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
		$trainingCourseTargetPopulations = $this->TrainingCourseTargetPopulation->find('all', array('conditions'=>array('TrainingCourseTargetPopulation.training_course_id'=>$id)));

		$staffPositionTitle = ClassRegistry::init('StaffPositionTitle');
		$staffPositionTitles = $staffPositionTitle->find('list', array('fields'=>array('id', 'name'), 'conditions'=>array('StaffPositionTitle.visible'=>1)));

		$trainingCoursePrerequisites = $this->TrainingCoursePrerequisite->find('all',  
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

		$trainingCourseProviders = $this->TrainingCourseProvider->find('all', array('conditions'=>array('TrainingCourseProvider.training_course_id'=>$id)));

		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviders = $trainingProvider->find('list', array('fields'=>array('id', 'name')));

		$this->TrainingCourseResultType->bindModel(
	        array('belongsTo' => array(
	                'TrainingResultType' => array(
						'className' => 'FieldOptionValue',
						'foreignKey' => 'training_result_type_id'
					)
	            )
	        )
	    );

		$trainingCourseResultTypes = $this->TrainingCourseResultType->find('all', array('conditions'=>array('TrainingCourseResultType.training_course_id'=>$id)));

		$arrMap = array('model'=>'Training.TrainingCourseAttachment', 'foreignKey' => 'training_course_id');
        $FileAttachment = $controller->Components->load('FileAttachment', $arrMap);

        $attachments = $FileAttachment->getList($id);

		$controller->set('data', $data);
		$controller->set('trainingCourseTargetPopulations', $trainingCourseTargetPopulations);
		$controller->set('staffPositionTitles', $staffPositionTitles);
		$controller->set('trainingCoursePrerequisites', $trainingCoursePrerequisites);
		$controller->set('trainingCourseProviders', $trainingCourseProviders);
		$controller->set('trainingProviders', $trainingProviders);
		$controller->set('trainingCourseResultTypes', $trainingCourseResultTypes);
		$controller->set('attachments', $attachments);
		$controller->set('_model','TrainingCourseAttachment');

		//APROVAL
		$pending = $data['TrainingCourse']['training_status_id']=='2' ? true : false;
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
		$trainingFieldStudyOptions = $this->TrainingFieldStudy->find('list', array('fields'=> array('id', 'name')));
		
		$trainingModeDeliveryOptions = $this->TrainingModeDelivery->find('list', array('fields'=> array('id', 'name')));
		
		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviderOptions = $trainingProvider->find('list', array('fields'=> array('id', 'name')));
		
		$trainingRequirementOptions = $this->TrainingRequirement->find('list', array('fields'=> array('id', 'name')));

		$trainingLevelOptions = $this->TrainingLevel->find('list', array('fields'=> array('id', 'name')));

		$staffPositionTitle = ClassRegistry::init('StaffPositionTitle');
		$staffPositionTitles = $staffPositionTitle->find('list', array('fields'=>array('id', 'name')));

		$trainingCourseTypeOptions = $this->TrainingCourseType->find('list', array('fields'=> array('id', 'name')));
	
		$configItem = ClassRegistry::init('ConfigItem');
	 	$credit_hours = $configItem->field('ConfigItem.value', array('ConfigItem.name' => 'training_credit_hour'));

	 	$trainingCreditHourOptions = array();
	 	for($i=0;$i<=$credit_hours;$i++){
 			$trainingCreditHourOptions[$i] = $i;
	 	}

	 	
		$controller->set(compact('trainingFieldStudyOptions', 'trainingModeDeliveryOptions', 'trainingProviderOptions', 
		'trainingRequirementOptions', 'trainingLevelOptions', 'staffPositionTitles', 'trainingCourseTypeOptions', 'trainingCreditHourOptions', 'trainingResultTypeOptions'));
	

		$controller->set('modelName', $this->name);
		$attachments = array();
		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];

			//==================================================

			$this->recursive = -1;
			$data = $this->findById($id);

			$attachments = $controller->FileUploader->getList(array('conditions' => array('TrainingCourseAttachment.training_course_id'=>$id)));
		
	
			if(!empty($data)){
				if($data['TrainingCourse']['training_status_id']!=1){
					return $controller->redirect(array('action' => 'courseView', $id));
				}
				$controller->request->data = $data;
				$trainingCourseTargetPopulations = $this->TrainingCourseTargetPopulation->find('all', array('conditions'=>array('TrainingCourseTargetPopulation.training_course_id'=>$id)));

				$trainingCoursePrerequisites = $this->TrainingCoursePrerequisite->find('all',  
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

				$this->TrainingCourseResultType->bindModel(
			        array('belongsTo' => array(
			                'TrainingResultType' => array(
								'className' => 'FieldOptionValue',
								'foreignKey' => 'training_result_type_id'
							)
			            )
			        )
			    );

			   $trainingCourseResultTypes = $this->TrainingCourseResultType->find('all',  
					array(
						'conditions'=>array('TrainingCourseResultType.training_course_id'=>$id)
					)
				);

				$trainingCourseResultTypesVal = null;
				if(!empty($trainingCourseResultTypes)){
					foreach($trainingCourseResultTypes as $val){
						$trainingCourseResultTypesVal[] = array_merge(array('result_type'=>$val['TrainingResultType']['name']), $val['TrainingCourseResultType']);
					}
				}

				$merge = array_merge(array('TrainingCourseTargetPopulation'=>$trainingCourseTargetPopulationsVal), array('TrainingCoursePrerequisite'=>$trainingCoursePrerequisitesVal)
					, array('TrainingCourseProvider'=>$trainingCourseProvidersVal), array('TrainingCourseResultType'=>$trainingCourseResultTypesVal));
				$controller->request->data = array_merge($data, $merge);
			}
		}
		else{
			$saveData = $controller->request->data;
			$postFileData = $saveData['TrainingCourse']['files'];
			unset($saveData['TrainingCourse']['files']);


			if ($this->saveAll($saveData, array('validate' => 'only'))){
				if (isset($saveData['save'])) {
				   	$saveData['TrainingCourse']['training_status_id'] = 1; 
				} else if (isset($saveData['submitForApproval'])) {
			      	$saveData['TrainingCourse']['training_status_id'] = 2; 
				}
				if($this->saveAll($saveData)){
					$id = null;
					if(isset($saveData['TrainingCourse']['id'])){
						$id = $saveData['TrainingCourse']['id'];
					}
					if(empty($id)){
						$id = $this->getInsertID();
					}

					$controller->FileUploader->additionData = array('training_course_id' => $id);
					$controller->FileUploader->uploadFile(NULL, $postFileData);
					
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
					if(isset($controller->request->data['DeleteResultType'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteResultType'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingCourseResultType->deleteAll(array('TrainingCourseResultType.id' => $deletedId), false);
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
		$controller->set('attachments', $attachments);
	}
}
