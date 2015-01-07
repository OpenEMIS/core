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
		'TrainingCourseSpecialisation' => array(
			'dependent' => true,
			'exclusive' => true
		),
		'TrainingCourseExperience' => array(
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

		$staffPositionTitle = ClassRegistry::init('Staff.StaffPositionTitle');

		$list = $staffPositionTitle->getList(array('conditions'=>array('StaffPositionTitle.name LIKE' => $search)));
		$data = array();
		foreach($list as $obj) {
			$positionTitleId = $obj['value'];
			$positionTitleName = $obj['name'];
			
			$data[] = array(
				'label' => trim(sprintf('%s', $positionTitleName)),
				'value' => array('position-title-id-'.$index => $positionTitleId, 'position-title-name-'.$index => $positionTitleName,
				'position-title-validate-'.$index => $positionTitleId)
			);
		}

		return $data;
	}
	
	public function course($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);
		$controller->set('modelName', $this->name);

		$trainingStatus = ClassRegistry::init('TrainingStatus');
		$statusOptions = $trainingStatus->getList();
		$selectedStatus = empty($params['pass'][0])? null:$params['pass'][0];
		
		if ($controller->request->is('post')) {
			if (isset($controller->request->data['sortdir']) && isset($controller->request->data['order'])) {
				if ($controller->request->data['sortdir'] != $controller->Session->read('Search.sortdirTrainingCourse')) {
					$controller->Session->delete('Search.sortdirTrainingCourse');
					$controller->Session->write('Search.sortdirTrainingCourse', $controller->request->data['sortdir']);
				}
				if ($controller->request->data['order'] != $controller->Session->read('Search.orderTrainingCourse')) {
					$controller->Session->delete('Search.orderTrainingCourse');
					$controller->Session->write('Search.orderTrainingCourse', $controller->request->data['order']);
				}
			}
		}

		$conditions = array();
		if(!empty($selectedStatus)){
			$conditions['TrainingCourse.training_status_id'] = $selectedStatus;
		}else{
			$conditions['NOT']['TrainingCourse.training_status_id'] = 4;
		}

		$fieldordername = ($controller->Session->read('Search.orderTrainingCourse')) ? $controller->Session->read('Search.orderTrainingCourse') : array('TrainingCourse.code', 'TrainingCourse.title', 'TrainingCourse.credit_hours', 'TrainingCourse.training_status_id');
		$fieldorderdir = ($controller->Session->read('Search.sortdirTrainingCourse')) ? $controller->Session->read('Search.sortdirTrainingCourse') : 'asc';
		$order = $fieldordername;
		if($controller->Session->check('Search.orderTrainingCourse')){
			$order = array($fieldordername => $fieldorderdir);
		}

		$controller->Paginator->settings = array(
	        'conditions' => $conditions,
	        'fields' => array('TrainingCourse.*', 'TrainingStatus.id', 'TrainingStatus.name'),
	        'joins' => array(
		        array(
		            'alias' => 'TrainingStatus',
		            'table' => 'field_option_values',
		            'type' => 'INNER',
		            'conditions' => 'TrainingStatus.id = TrainingCourse.training_status_id'
		        )
		    ),
	        'limit' => 25,
	        'recursive'=> -1,
	        'order' => $order
	    );

		$data = $controller->paginate('TrainingCourse');
		$data = $controller->Workflow->populateWorkflowStatus($this->name, 'TrainingStatus', $data);	

		if (empty($data) && !$controller->request->is('ajax')) {
			$controller->Utility->alert($controller->Utility->getMessage('NO_RECORD'), array('type' => 'info'));
		}
	
		$controller->set('sortedcol', $fieldordername);
		$controller->set('sorteddir', ($fieldorderdir == 'asc') ? 'up' : 'down');

		$controller->set('subheader', $this->headerDefault);
		$controller->set('data', $data);
		$controller->set('statusOptions', $statusOptions);
		$controller->set('selectedStatus', $selectedStatus);
		if ($controller->request->is('post')) {
			//$controller->render('/Training/course/index');
			$controller->set('ajax', true);
		}
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

		$staffPositionTitle = ClassRegistry::init('Staff.StaffPositionTitle');
		$staffPositionTitles = $staffPositionTitle->getList();

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
		$trainingProviders = $trainingProvider->getList();

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

		// $this->TrainingCourseSpecialisation->bindModel(
	 //        array('belongsTo' => array(
	 //                'QualificationSpecialisation' => array(
		// 				'className' => 'QualificationSpecialisation',
		// 				'foreignKey' => 'qualification_specialisation_id'
		// 			)
	 //            )
	 //        )
	 //    );
		$trainingCourseSpecialisations = $this->TrainingCourseSpecialisation->find('all', array('conditions'=>array('TrainingCourseSpecialisation.training_course_id'=>$id)));
		$trainingCourseExperiences = $this->TrainingCourseExperience->find('all', array('conditions'=>array('TrainingCourseExperience.training_course_id'=>$id)));
				

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
		$controller->set('trainingCourseSpecialisations', $trainingCourseSpecialisations);
		$controller->set('trainingCourseExperiences', $trainingCourseExperiences);
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
	            $controller->Message->alert('general.delete.success');
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
	            $controller->Message->alert('Training.activate.success');
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
            $controller->Message->alert('Training.inactivate.success');
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
		$trainingFieldStudyOptions = $this->TrainingFieldStudy->getList();

		$trainingModeDeliveryOptions = $this->TrainingModeDelivery->getList();

		$trainingProvider = ClassRegistry::init('TrainingProvider');
		$trainingProviderOptions = $trainingProvider->getList();

		$trainingRequirementOptions = $this->TrainingRequirement->getList();

		$trainingLevelOptions = $this->TrainingLevel->getList();

		$staffPositionTitle = ClassRegistry::init('Staff.StaffPositionTitle');
		$staffPositionTitles = $staffPositionTitle->getList();

		$trainingCourseTypeOptions = $this->TrainingCourseType->getList();
	
		$configItem = ClassRegistry::init('ConfigItem');
	 	$credit_hours = $configItem->field('ConfigItem.value', array('ConfigItem.name' => 'training_credit_hour'));

	 	$trainingCreditHourOptions = array();
	 	for($i=0;$i<=$credit_hours;$i++){
 			$trainingCreditHourOptions[$i] = $i;
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
 		$trainingResultTypeOptions = $this->TrainingCourseResultType->TrainingResultType->getList();

 		$qualificationSpecialisation = ClassRegistry::init('Training.QualificationSpecialisation');
		$qualificationSpecialisationOptions = $qualificationSpecialisation->getList();

		$controller->set(compact('trainingFieldStudyOptions', 'trainingModeDeliveryOptions', 'trainingProviderOptions', 
		'trainingRequirementOptions', 'trainingLevelOptions', 'staffPositionTitles', 'trainingCourseTypeOptions', 'trainingCreditHourOptions', 'trainingResultTypeOptions', 'qualificationSpecialisationOptions'));
	

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
				$trainingCourseTargetPopulationsVal = null;
				if(!empty($trainingCourseTargetPopulations)){
					foreach($trainingCourseTargetPopulations as $val){
						$trainingCourseTargetPopulationsVal[] = $val['TrainingCourseTargetPopulation'];
					}
				}

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

				$trainingCourseSpecialisations = $this->TrainingCourseSpecialisation->find('all', array('conditions'=>array('TrainingCourseSpecialisation.training_course_id'=>$id)));
				$trainingCourseSpecialisationsVal = null;
				if(!empty($trainingCourseSpecialisations)){
					foreach($trainingCourseSpecialisations as $val){
						$trainingCourseSpecialisationsVal[] = $val['TrainingCourseSpecialisation'];
					}
				}

				$trainingCourseExperiences = $this->TrainingCourseExperience->find('all', array('conditions'=>array('TrainingCourseExperience.training_course_id'=>$id)));
				$trainingCourseExperiencesVal = null;
				if(!empty($trainingCourseExperiences)){
					foreach($trainingCourseExperiences as $val){
						$trainingCourseExperiencesVal[] = $val['TrainingCourseExperience'];
					}
				}

				$merge = array_merge(array('TrainingCourseTargetPopulation'=>$trainingCourseTargetPopulationsVal), array('TrainingCoursePrerequisite'=>$trainingCoursePrerequisitesVal)
					, array('TrainingCourseProvider'=>$trainingCourseProvidersVal), array('TrainingCourseResultType'=>$trainingCourseResultTypesVal), array('TrainingCourseSpecialisation'=>$trainingCourseSpecialisationsVal)
					, array('TrainingCourseExperience'=>$trainingCourseExperiencesVal));
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
					if(isset($controller->request->data['DeleteCoursePrerequisite'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteCoursePrerequisite'] as $key=>$value){
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
					if(isset($controller->request->data['DeleteSpecialisation'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteSpecialisation'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingCourseSpecialisation->deleteAll(array('TrainingCourseSpecialisation.id' => $deletedId), false);
					}
					if(isset($controller->request->data['DeleteExperience'])){
						$deletedId = array();
						foreach($controller->request->data['DeleteExperience'] as $key=>$value){
							$deletedId[] = $value['id'];
						}
						$this->TrainingCourseExperience->deleteAll(array('TrainingCourseExperience.id' => $deletedId), false);
					}
					if(empty($controller->request->data[$this->name]['id'])){	
						$controller->Message->alert('general.add.success');
					}
					else{	
						$controller->Message->alert('general.update.success');
					}
					return $controller->redirect(array('action' => 'course'));
				}
			}
		}
		$controller->set('attachments', $attachments);
	}

}
