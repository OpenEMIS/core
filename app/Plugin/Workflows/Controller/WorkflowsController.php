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

class WorkflowsController extends WorkflowsAppController {
	public $uses = array(
		'Workflows.WfWorkflow',
		'Workflows.WorkflowModel'
	);

	public $components = array(
		'ControllerAction' => array('model' => 'Workflows.WfWorkflow')
	);

	public function beforeFilter() {
		parent::beforeFilter();
		$this->bodyTitle = 'Administration';
		$this->Navigation->addCrumb('Administration', array('controller' => 'Areas', 'action' => 'index', 'plugin' => false));
		$this->Navigation->addCrumb('Workflows');
		$this->set('contentHeader', 'Workflows');
		$this->WfWorkflow->fields['code']['hyperlink'] = true;

		if ($this->action == 'index' || $this->action == 'view') {
			$this->WfWorkflow->fields['workflow_model_id']['dataModel'] = 'WorkflowModel';
			$this->WfWorkflow->fields['workflow_model_id']['dataField'] = 'name';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->WfWorkflow->fields['workflow_model_id']['type'] = 'select';

			$selectedWorkflowId = null;
			if ($this->request->is(array('post', 'put'))) {
			} else {
				$pass = $this->request->params['pass'];
				$selectedWorkflowId = isset($pass[0]) ? $pass[0] : 0;
			}

			$workflowIds = $this->WfWorkflow->find('list', array(
				'fields' => array(
					'WfWorkflow.workflow_model_id', 'WfWorkflow.workflow_model_id'
				),
				'conditions' => array(
					'NOT' => array('WfWorkflow.id' => $selectedWorkflowId)
				),
			));

			$workflowModelOptions = $this->WorkflowModel->find('list', array(
				'fields' => array(
					'WorkflowModel.id', 'WorkflowModel.name'
				),
				'conditions' => array(
					'NOT' => array('WorkflowModel.id' => $workflowIds)
				),
				'order' => array(
					'WorkflowModel.name'
				)
			));

			$selectedWorkflowModelId = key($workflowModelOptions);
			$this->WfWorkflow->fields['workflow_model_id']['options'] = $workflowModelOptions;
		}
	}
}
