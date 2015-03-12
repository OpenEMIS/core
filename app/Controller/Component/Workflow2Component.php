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
	public $WfWorkflowLog;

	public $components = array('Session', 'Message', 'Auth');
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->init();
	}

	public function init() {
		$this->WfWorkflow = ClassRegistry::init(array('class' => 'Workflows.WfWorkflow', 'alias' => 'WfWorkflow'));
		$this->WfWorkflowStep = ClassRegistry::init(array('class' => 'Workflows.WfWorkflowStep', 'alias' => 'WfWorkflowStep'));
		$this->WfWorkflowLog = ClassRegistry::init(array('class' => 'Workflows.WfWorkflowLog', 'alias' => 'WfWorkflowLog'));
	}

	// Is called before the controller's beforeFilter method.
	public function initialize(Controller $controller) {
		$this->controller =& $controller;
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
			
			if ($workflows) {
				$this->fields = !empty($this->controller->viewVars['_fields'])? $this->controller->viewVars['_fields'] : null;
				$this->triggerFrom = !empty($this->controller->viewVars['_triggerFrom'])? $this->controller->viewVars['_triggerFrom'] : $this->triggerFrom;

				$pass = $this->controller->params->pass;
				if ($this->triggerFrom == 'Controller') {
					$modelReference = $pass[0];
				} else if ($this->triggerFrom == 'Model') {
					unset($pass[0]);
					$modelReference = $pass[1];
				}

				if ($this->controller->request->is(array('post', 'put'))) {
					$data = $this->controller->request->data;
					if ($this->WfWorkflowLog->saveAll($data)) {
					} else {
						$this->log($this->WfWorkflowLog->validationErrors, 'debug');
					}
				}

				$buttons = array();
				$tabs = array();
				$this->WfWorkflowLog->contain('PrevWorkflowStep', 'WfWorkflowStep');
				$workflowLogs = $this->WfWorkflowLog->find('first', array(
					'conditions' => array(
						'WfWorkflowLog.model' => $this->model,
						'WfWorkflowLog.model_reference' => $modelReference
					),
					'order' => array(
						'WfWorkflowLog.created DESC'
					)
				));

				$options = array();
				if ($workflowLogs) {
					$workflowId = $workflowLogs['WfWorkflowStep']['workflow_id'];
					$workflowStepId = $workflowLogs['WfWorkflowStep']['id'];
					
					$options['conditions'] = array(
						'WfWorkflowStep.workflow_id' => $workflowId,
						'WfWorkflowStep.id' => $workflowStepId
					);
				} else {
					$workflowId = $workflows['WfWorkflow']['id'];
					$options['conditions'] = array(
						'WfWorkflowStep.workflow_id' => $workflowId,
						'WfWorkflowStep.editable' => 0
					);
				}

				$this->WfWorkflowStep->contain('WorkflowAction', 'SecurityRole');
				$workflowSteps = $this->WfWorkflowStep->find('first', $options);

				$SecurityGroupUser = ClassRegistry::init('SecurityGroupUser');
				$userId = $this->Auth->user('id');
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

				$data['WfWorkflowLog']['model'] = $this->model;
				$data['WfWorkflowLog']['model_reference'] = $modelReference;
				$data['WfWorkflowLog']['prev_workflow_step_id'] = $workflowStepId;
				$this->controller->request->data = $data;

				$this->fields['workflows'] = array(
					'type' => 'element',
					'element' => '../Elements/workflows',
					'label' => false,
					'valueClass' => 'col-md-12',
					'visible' => true
				);

				$tabs = array('Comments', 'Transitions');

				$controller->set('_edit', false);
				$controller->set('_add', false);
				$controller->set('_delete', false);
				$controller->set('_execute', false);
				$controller->set('_fields', $this->fields);
				$controller->set('workflowStepName', $workflowStepName);
				$controller->set('buttons', $buttons);
				$controller->set('tabs', $tabs);
			}
		}
	}
}
