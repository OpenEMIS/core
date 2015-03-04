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

class WorkflowStepsController extends WorkflowsAppController {
	public $uses = array(
		'Workflows.WorkflowStep',
		'Workflows.Workflow'
	);

	public $components = array(
		'ControllerAction' => array('model' => 'Workflows.WorkflowStep')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Workflows', array('plugin' => 'Workflows', 'controller' => 'Workflows', 'action' => 'index'));
		$this->Navigation->addCrumb('Steps');
		$this->set('contentHeader', 'Workflow Steps');

		$this->WorkflowStep->fields['security_roles'] = array(
			'type' => 'chosen_select',
			'id' => 'SecurityRole.SecurityRole',
			'placeholder' => __('Select security roles'),
			'visible' => true
		);
		$this->WorkflowStep->setFieldOrder('security_roles', 3);

		$this->WorkflowStep->fields['actions'] = array(
			'type' => 'element',
			'element' => '../../Plugin/Workflows/View/WorkflowSteps/actions',
			'visible' => true
		);
		$this->WorkflowStep->setFieldOrder('actions', 4);

		if ($this->action == 'view') {
			$this->WorkflowStep->fields['wf_workflow_id']['dataModel'] = 'Workflow';
			$this->WorkflowStep->fields['wf_workflow_id']['dataField'] = 'name';

			$this->WorkflowStep->fields['security_roles']['dataModel'] = 'SecurityRole';
			$this->WorkflowStep->fields['security_roles']['dataField'] = 'name';

			$workflowSteps = $this->WorkflowStep->find('list');
			$this->set('workflowSteps', $workflowSteps);
		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->WorkflowStep->fields['wf_workflow_id']['type'] = 'select';
			$workflowOptions = $this->Workflow->find('list');
			$this->WorkflowStep->fields['wf_workflow_id']['options'] = $workflowOptions;

			$securityRoleOptions = $this->WorkflowStep->SecurityRole->find('list');
			$this->WorkflowStep->fields['security_roles']['options'] = $securityRoleOptions;

			$pass = $this->request->params['pass'];
			$workflowStepId = isset($pass[0]) ? $pass[0] : 0;
			$workflowStepData = $this->WorkflowStep->find('list', array(
				'conditions' => array(
					'NOT' => array(
						'WorkflowStep.id' => $workflowStepId
					)
				)
			));
			$workflowStepOptions = $this->Option->prependLabel($workflowStepData, 'WorkflowStep.select_step');

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;

				if($data['submit'] == 'WorkflowAction') {
					$this->request->data['WorkflowAction'][] =array(
						'name' => '',
						'next_wf_workflow_step_id' => 0,
						'visible' => 1
					);
					$this->ControllerAction->autoProcess = false;
				} else {
					if (isset($data['WorkflowAction'])) {
						foreach ($data['WorkflowAction'] as $key => $obj) {
							if (!isset($obj['id']) && empty($obj['name'])) {
								unset($data['WorkflowAction'][$key]);
							}
						}
					}
					$this->request->data = $data;

					$this->ControllerAction->autoProcess = true;
				}

				$this->ControllerAction->processAction();
			}

			$this->set('workflowStepOptions', $workflowStepOptions);
		}
	}

	public function index() {
		$named = $this->params->named;

		$workflows = $this->Workflow->find('list');
		$selectedWorkflow = isset($named['workflow']) ? $named['workflow'] : key($workflows);

		$workflowOptions = array();
		foreach ($workflows as $key => $workflow) {
			$workflowOptions['workflow:' . $key] = $workflow;
		}

		$this->WorkflowStep->contain('Workflow', 'WorkflowAction', 'WorkflowAction.NextWorkflowStep', 'SecurityRole');
    	$data = $this->WorkflowStep->find('all', array(
			'conditions' => array(
				'WorkflowStep.wf_workflow_id' => $selectedWorkflow
			),
			'order' => array(
				'Workflow.code', 'Workflow.name'
			)
		));

		$this->set('workflowOptions', $workflowOptions);
		$this->set('selectedWorkflow', $selectedWorkflow);
		$this->set('data', $data);
	}
}
