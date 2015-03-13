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

class Workflow2Component extends Component {
	private $controller;
	private $model = null;
	private $triggerFrom = 'Controller';
	private $currentAction;	
	private $fields;
	public $WfWorkflow;
	public $WfWorkflowStep;
	public $WorkflowRecord;
	public $WorkflowComment;
	public $WorkflowTransition;

	public $components = array('Session', 'Message', 'Auth');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->WfWorkflow = ClassRegistry::init(array('class' => 'Workflows.WfWorkflow', 'alias' => 'WfWorkflow'));
		$this->WfWorkflowStep = ClassRegistry::init(array('class' => 'Workflows.WfWorkflowStep', 'alias' => 'WfWorkflowStep'));
		$this->WorkflowRecord = ClassRegistry::init(array('class' => 'Workflows.WorkflowRecord', 'alias' => 'WorkflowRecord'));
		$this->WorkflowComment = ClassRegistry::init(array('class' => 'Workflows.WorkflowComment', 'alias' => 'WorkflowComment'));
		$this->WorkflowTransition = ClassRegistry::init(array('class' => 'Workflows.WorkflowTransition', 'alias' => 'WorkflowTransition'));
	}

	// Is called after the controller's beforeFilter method but before the controller executes the current action handler.
	public function startup(Controller $controller) {
	}

	// Is called after the controller executes the requested action’s logic, but before the controller’s renders views and layout.
	public function beforeRender(Controller $controller) {
		$this->model = !empty($this->controller->viewVars['model'])? $this->controller->viewVars['model'] : null;
		$this->currentAction = !empty($this->controller->viewVars['action'])? $this->controller->viewVars['action'] : null;

		if ($this->currentAction == 'view') {
			$this->WfWorkflow->contain('WorkflowModel');
			$workflows = $this->WfWorkflow->find('first', array(
				'conditions' => array(
					'WorkflowModel.model' => $this->model
				)
			));
			
			if (!empty($workflows)) {
				$this->fields = !empty($this->controller->viewVars['_fields'])? $this->controller->viewVars['_fields'] : null;
				$this->triggerFrom = !empty($this->controller->viewVars['_triggerFrom'])? $this->controller->viewVars['_triggerFrom'] : $this->triggerFrom;
				$userId = $this->Auth->user('id');
				$workflowId = $workflows['WfWorkflow']['id'];

				$pass = $this->controller->params->pass;
				if ($this->triggerFrom == 'Controller') {
					$modelReference = $pass[0];
				} else if ($this->triggerFrom == 'Model') {
					unset($pass[0]);
					$modelReference = $pass[1];
				}

				$buttons = array();
				$tabs = array();
				$tabs['comment'] = array('name' => 'Comments', 'class' => '');
				$tabs['transition'] = array('name' => 'Transitions', 'class' => '');
				$selectedTab = key($tabs);

				if ($this->controller->request->is(array('post', 'put'))) {
					$data = $this->controller->request->data;

					if (!empty($data['submit'])) {
						if($data['submit'] == 'add') {
							unset($data['WorkflowTransition']);
							
							if ($this->WorkflowComment->saveAll($data)) {
							} else {
								$this->log($this->WorkflowComment->validationErrors, 'debug');
							}
						} else if($data['submit'] == 'edit') {

						} else if($data['submit'] == 'delete') {
							$workflowCommentId = $data['WorkflowComment']['id'];

							$this->WorkflowComment->deleteAll(array(
								'WorkflowComment.id' => $workflowCommentId
							), false);
						} else if($data['submit'] == 'comment') {
							$selectedTab = $data['submit'];
						} else if($data['submit'] == 'transition') {
							$selectedTab = $data['submit'];
						}
					} else {
						$workflowRecordId = $data['WorkflowComment']['workflow_record_id'];
						unset($data['WorkflowComment']);
						
						$workflowStepId = $data['WorkflowTransition']['workflow_step_id'];
						$stage = $this->WfWorkflowStep->field('stage', array('WfWorkflowStep.id' => $workflowStepId));
						if (!is_null($stage)) {
							$this->WorkflowRecord->updateAll(
							    array('WorkflowRecord.stage' => $stage),
							    array('WorkflowRecord.id' => $workflowRecordId)
							);
						}

						if ($this->WorkflowTransition->saveAll($data)) {
						} else {
							$this->log($this->WorkflowTransition->validationErrors, 'debug');
						}
					}
				}

				$workflowRecordId = $this->WorkflowRecord->field('id', array(
					'WorkflowRecord.model' => $this->model,
					'WorkflowRecord.model_reference' => $modelReference
				));

				if (empty($workflowRecordId)) {
					$tmpData['WorkflowRecord']['model'] = $this->model;
					$tmpData['WorkflowRecord']['model_reference'] = $modelReference;
					$tmpData['WorkflowRecord']['stage'] = 0;
					$tmpData['WorkflowRecord']['workflow_id'] = $workflowId;

					if ($this->WorkflowRecord->saveAll($tmpData)) {
						$workflowRecordId = $this->WorkflowRecord->field('id', array(
							'WorkflowRecord.model' => $this->model,
							'WorkflowRecord.model_reference' => $modelReference
						));
					} else {
						$this->log($this->WorkflowRecord->validationErrors, 'debug');
					}
				}

				if ($selectedTab == 'comment') {
					$this->WorkflowComment->contain('CreatedUser');
					$comments = $this->WorkflowComment->find('all', array(
						'conditions' => array(
							'WorkflowComment.workflow_record_id' => $workflowRecordId
						),
						'order' => array(
							'WorkflowComment.created ASC'
						)
					));

					$controller->set('comments', $comments);
					$redirect = $controller->request->params;
					//pr($redirect);
					$redirect = array_merge($redirect, $controller->request->params['pass']);
					unset($redirect['pass']);
					unset($redirect['named']);
					unset($redirect['isAjax']);
					//pr($redirect);die;
					//pr($this->controller->request->params);die;
					//return $controller->redirect($redirect);
				} else if ($selectedTab == 'transition') {
					$this->WorkflowTransition->contain('PrevWorkflowStep', 'WfWorkflowStep', 'WorkflowRecord', 'CreatedUser');
					$transitions = $this->WorkflowTransition->find('all', array(
						'conditions' => array(
							'WorkflowRecord.model' => $this->model,
							'WorkflowRecord.model_reference' => $modelReference
						),
						'order' => array(
							'WorkflowTransition.created ASC'
						)
					));

					$controller->set('transitions', $transitions);
				}

				$this->WorkflowComment->contain('CreatedUser');
				$workflowComments = $this->WorkflowComment->find('all', array(
					'conditions' => array(
						'WorkflowComment.workflow_record_id' => $workflowRecordId
					)
				));

				$this->WorkflowTransition->contain('PrevWorkflowStep', 'WfWorkflowStep', 'CreatedUser');
				$workflowTransitions = $this->WorkflowTransition->find('first', array(
					'conditions' => array(
						'WorkflowTransition.workflow_record_id' => $workflowRecordId
					),
					'order' => array(
						'WorkflowTransition.created DESC'
					)
				));

				$options = array();
				if (empty($workflowTransitions)) {
					$options['conditions'] = array(
						'WfWorkflowStep.workflow_id' => $workflowId,
						'WfWorkflowStep.stage' => 0
					);
				} else {
					$workflowStepId = $workflowTransitions['WfWorkflowStep']['id'];
					
					$options['conditions'] = array(
						'WfWorkflowStep.workflow_id' => $workflowId,
						'WfWorkflowStep.id' => $workflowStepId
					);
				}

				$this->WfWorkflowStep->contain('WorkflowAction', 'SecurityRole');
				$workflowSteps = $this->WfWorkflowStep->find('first', $options);

				$SecurityGroupUser = ClassRegistry::init('SecurityGroupUser');
				$roles = $SecurityGroupUser->getRolesByUserId($userId);

				$workflowStepId = $workflowSteps['WfWorkflowStep']['id'];
				$workflowStepName = $workflowSteps['WfWorkflowStep']['name'];
				$workflowActions = !empty($workflowSteps['WorkflowAction']) ? $workflowSteps['WorkflowAction'] : array();
				foreach ($workflowActions as $key => $workflowAction) {
					$buttons[$key] = array(
						'text' => $workflowAction['name'],
						'value' => $workflowAction['next_workflow_step_id']
					);
				}

				$tabs[$selectedTab]['class'] = 'active';

				$requestData['WorkflowRecord']['model_reference'] = $modelReference;
				$requestData['WorkflowComment']['workflow_record_id'] = $workflowRecordId;
				$requestData['WorkflowTransition']['prev_workflow_step_id'] = $workflowStepId;
				$requestData['WorkflowTransition']['workflow_record_id'] = $workflowRecordId;
				$this->controller->request->data = $requestData;

				$this->fields['workflows'] = array(
					'type' => 'element',
					'element' => '../Workflow/index',
					'label' => false,
					'valueClass' => 'col-md-12',
					'visible' => true
				);

				$controller->set('_edit', false);
				$controller->set('_add', false);
				$controller->set('_delete', false);
				$controller->set('_execute', false);
				$controller->set('_fields', $this->fields);
				$controller->set('workflowStepName', $workflowStepName);
				$controller->set('buttons', $buttons);
				$controller->set('tabs', $tabs);
				$controller->set('selectedTab', $selectedTab);
				$controller->set('userId', $userId);
			}
		}
	}
}
