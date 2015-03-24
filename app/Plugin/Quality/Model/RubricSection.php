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

App::uses('AppModel', 'Model');

class RubricSection extends QualityAppModel {
	public $belongsTo = array(
		'RubricTemplate' => array(
            'className' => 'Quality.RubricTemplate',
            'foreignKey' => 'rubric_template_id'
        ),
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);

    public $validate = array(
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			),
			'unique' => array(
	            'rule' => array('checkUnique', array('name', 'rubric_template_id'), false),
	            'message' => 'This name is already exists in the system'
	        )
		)
	);

	public function beforeAction() {
		$this->Navigation->addCrumb('Sections');
		$named = $this->controller->params->named;
		
		$templateOptions = $this->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedTemplate = isset($named['template']) ? $named['template'] : key($templateOptions);

		$this->fields['order']['visible'] = false;
		$this->ControllerAction->setFieldOrder('rubric_template_id', 1);

		if ($this->action == 'index') {
			$this->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->fields['rubric_template_id']['dataField'] = 'name';
		} else if ($this->action == 'view') {
			$this->fields['rubric_template_id']['dataModel'] = 'RubricTemplate';
			$this->fields['rubric_template_id']['dataField'] = 'name';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['rubric_template_id']['type'] = 'select';
			$this->fields['rubric_template_id']['options'] = $templateOptions;

			if ($this->request->is(array('post', 'put'))) {
			} else {
				$this->request->data['RubricSection']['rubric_template_id'] = $selectedTemplate;
			}
		}

		$contentHeader = __('Sections');
		$this->controller->set(compact('contentHeader', 'templateOptions', 'selectedTemplate'));
	}

	public function index() {
		$named = $this->controller->params->named;

		$templates = $this->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedTemplate = isset($named['template']) ? $named['template'] : key($templates);

		$templateOptions = array();
		foreach ($templates as $key => $rubricTemplate) {
			$templateOptions['template:' . $key] = $rubricTemplate;
		}

		if (empty($templateOptions)) {
			$this->controller->Message->alert('RubricTemplate.noTemplate');
		} else {
			$this->contain('RubricTemplate');
			$data = $this->find('all', array(
				'conditions' => array(
					'RubricSection.rubric_template_id' => $selectedTemplate
				),
				'order' => array(
					'RubricSection.order', 'RubricSection.name'
				)
			));

			$this->controller->set(compact('data', 'templateOptions', 'selectedTemplate'));
		}
	}
}
