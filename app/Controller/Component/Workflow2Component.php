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
	private $workflowId;
	private $workflowRecordId;
	private $modelReference;
	public $WfWorkflow;
	public $WfWorkflowStep;
	public $WorkflowAction;
	public $WorkflowRecord;
	public $WorkflowComment;
	public $WorkflowTransition;

	public $components = array('Session', 'Message', 'Auth');

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
		$this->WfWorkflow = ClassRegistry::init(array('class' => 'Workflows.WfWorkflow', 'alias' => 'WfWorkflow'));
		$this->WfWorkflowStep = ClassRegistry::init(array('class' => 'Workflows.WfWorkflowStep', 'alias' => 'WfWorkflowStep'));
		$this->WorkflowAction = ClassRegistry::init(array('class' => 'Workflows.WorkflowAction', 'alias' => 'WorkflowAction'));
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
				$this->workflowId = $workflows['WfWorkflow']['id'];

				$pass = $this->controller->params->pass;
				if ($this->triggerFrom == 'Controller') {
					$modelReference = $pass[0];
				} else if ($this->triggerFrom == 'Model') {
					unset($pass[0]);
					$modelReference = $pass[1];
				}
				$this->modelReference = $modelReference;

				$workflowRecordId = $this->getWorkflowRecordId();
				$this->WorkflowRecord->contain('WfWorkflowStep');
				$workflowRecords = $this->WorkflowRecord->find('first', array(
					'conditions' => array(
						'WorkflowRecord.id' => $workflowRecordId
					)
				));

				$workflowStepId = $workflowRecords['WfWorkflowStep']['id'];
				$workflowStepName = $workflowRecords['WfWorkflowStep']['name'];

				$buttons = $this->getButtonsByWorkflowStepId($workflowStepId);
				
				$tabs = array(
					'comment' => array('name' => 'Comments', 'class' => ''),
					'transition' => array('name' => 'Transitions', 'class' => '')
				);
				$selectedTab = key($tabs);

				if ($this->controller->request->is(array('post', 'put'))) {
					$data = $this->controller->request->data;
					$selectedTab = $data['Workflow']['selected_tab'];
					unset($data['Workflow']);
					$submit = false;

					if($data['submit'] == 'add') {
						unset($data['WorkflowTransition']);
						
						if ($this->WorkflowComment->saveAll($data)) {
						} else {
							$this->log($this->WorkflowComment->validationErrors, 'debug');
						}
						$submit = true;
					} else if($data['submit'] == 'edit') {

					} else if($data['submit'] == 'delete') {
						$workflowCommentId = $data['WorkflowComment']['id'];

						$this->WorkflowComment->deleteAll(array(
							'WorkflowComment.id' => $workflowCommentId
						), false);
						$submit = true;
					} else if($data['submit'] == 'WorkflowTransition') {
						unset($data['WorkflowComment']);

						$this->WorkflowRecord->updateAll(
						    array('WorkflowRecord.workflow_step_id' => $data['WorkflowTransition']['workflow_step_id']),
						    array('WorkflowRecord.id' => $workflowRecordId)
						);

						if ($this->WorkflowTransition->saveAll($data)) {
						} else {
							$this->log($this->WorkflowTransition->validationErrors, 'debug');
						}
						$submit = true;
					}

					if ($submit) {
						$redirect = $controller->request->params;
						$redirect = array_merge($redirect, $controller->request->params['pass']);
						unset($redirect['pass']);
						unset($redirect['named']);
						unset($redirect['isAjax']);
						$redirect['selected'] = $selectedTab;
						return $controller->redirect($redirect);
					}
				} else {
					$named = $controller->request->params['named'];
					$selectedTab = isset($named['selected']) ? $named['selected'] : $selectedTab;
				}

				$tabs[$selectedTab]['class'] = 'active';

				if ($selectedTab == 'comment') {
					$comments = $this->getCommentByWorkflowRecordId($workflowRecordId);
					$controller->set('comments', $comments);
				} else if ($selectedTab == 'transition') {
					$transitions = $this->getTransitionByWorkflowRecordId($workflowRecordId);
					$controller->set('transitions', $transitions);
				}

				$requestData['WorkflowRecord']['model_reference'] = $this->modelReference;
				$requestData['WorkflowComment']['workflow_record_id'] = $workflowRecordId;
				$requestData['WorkflowTransition']['prev_workflow_step_id'] = $workflowStepId;
				$requestData['WorkflowTransition']['workflow_record_id'] = $workflowRecordId;
				$requestData['Workflow']['selected_tab'] = $selectedTab;
				$this->controller->request->data = $requestData;

				$this->fields['workflows'] = array(
					'type' => 'element',
					'element' => '/Workflow/index',
					'label' => false,
					'valueClass' => 'col-md-12',
					'visible' => true
				);

				$controller->set('_edit', false);
				$controller->set('_add', false);
				$controller->set('_delete', false);
				$controller->set('_execute', false);
				$controller->set('_fields', $this->fields);
				$controller->set('userId', $userId);
				$controller->set('workflowStepName', $workflowStepName);
				$controller->set('buttons', $buttons);
				$controller->set('tabs', $tabs);
				$controller->set('selectedTab', $selectedTab);
			}
		}
	}

	public function getWorkflowRecordId() {
		$workflowRecordId = $this->WorkflowRecord->field('id', array(
			'WorkflowRecord.model' => $this->model,
			'WorkflowRecord.model_reference' => $this->modelReference
		));

		if (empty($workflowRecordId)) {
			$workflowStepId = $this->WfWorkflowStep->field('id', array(
				'WfWorkflowStep.workflow_id' => $this->workflowId,
				'WfWorkflowStep.stage' => 0
			));

			$tmpData['WorkflowRecord']['model'] = $this->model;
			$tmpData['WorkflowRecord']['model_reference'] = $this->modelReference;
			$tmpData['WorkflowRecord']['workflow_step_id'] = $workflowStepId;

			if ($this->WorkflowRecord->saveAll($tmpData)) {
				$workflowRecordId = $this->WorkflowRecord->field('id', array(
					'WorkflowRecord.model' => $this->model,
					'WorkflowRecord.model_reference' => $this->modelReference
				));
			} else {
				$this->log($this->WorkflowRecord->validationErrors, 'debug');
			}
		}

		$this->Session->write('WorkflowRecordId', $workflowRecordId);
		return $workflowRecordId;
	}

	public function getButtonsByWorkflowStepId($workflowStepId) {
		$buttons = array();

		$this->WfWorkflowStep->contain('WorkflowAction');
		$workflowSteps = $this->WfWorkflowStep->findById($workflowStepId);
		$workflowActions = $workflowSteps['WorkflowAction'];

		foreach ($workflowActions as $key => $workflowAction) {
			$buttons[$key] = array(
				'id' => $workflowAction['id'],
				'text' => $workflowAction['name'],
				'value' => $workflowAction['next_workflow_step_id']
			);
		}		

		return $buttons;
	}

	public function getCommentByWorkflowRecordId($workflowRecordId) {
		$this->WorkflowComment->contain('CreatedUser');
		$comments = $this->WorkflowComment->find('all', array(
			'conditions' => array(
				'WorkflowComment.workflow_record_id' => $workflowRecordId
			),
			'order' => array(
				'WorkflowComment.created ASC'
			)
		));

		return $comments;
	}

	public function getTransitionByWorkflowRecordId($workflowRecordId) {		
		$this->WorkflowTransition->contain('PrevWorkflowStep', 'WfWorkflowStep', 'WorkflowAction', 'CreatedUser');
		$transitions = $this->WorkflowTransition->find('all', array(
			'conditions' => array(
				'WorkflowTransition.workflow_record_id' => $workflowRecordId
			),
			'order' => array(
				'WorkflowTransition.created ASC'
			)
		));

		return $transitions;
	}
}
