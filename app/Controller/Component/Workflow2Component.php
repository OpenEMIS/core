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
	private $currentAction;	
	private $fields;
	public $WfWorkflow;

	public $components = array('Session', 'Message', 'Auth');
	public function __construct(ComponentCollection $collection, $settings = array()) {
		parent::__construct($collection, $settings);
		$this->init();
	}

	public function init() {
		$this->WfWorkflow = ClassRegistry::init('WfWorkflow');
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
			$result = $this->WfWorkflow->find('first', array(
				'joins' => array(
					array(
						'table' => 'wf_workflow_models',
						'alias' => 'WorkflowModel',
						'conditions' => array(
							'WorkflowModel.id = WfWorkflow.wf_workflow_model_id',
							'WorkflowModel.model' => $this->model
						)
					)
				)
			));
			
			if ($result) {
				$controller->set('_edit', false);
				$controller->set('_add', false);
				$controller->set('_delete', false);

				$this->fields = !empty($this->controller->viewVars['_fields'])? $this->controller->viewVars['_fields'] : null;
				$this->fields['workflows'] = array(
					'type' => 'element',
					'element' => '../Elements/workflows',
					'visible' => true
				);
				$controller->set('_fields', $this->fields);
			}
		}
	}
}
