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

class RubricTemplateOption extends QualityAppModel {
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
		),
		'weighting' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a weighting'
			)
		),
		'color' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a color'
			)
		)
	);

	public function beforeAction() {
		$this->Navigation->addCrumb('Templates Options');
		$named = $this->controller->params->named;
		
		$rubricTemplateOptions = $this->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedRubricTemplate = isset($named['template']) ? $named['template'] : key($rubricTemplateOptions);

		$this->fields['color'] = array(
			'type' => 'element',
			'element' => '../../Plugin/Quality/View/RubricTemplateOption/color',
			'visible' => true
		);
		$this->ControllerAction->setFieldOrder('rubric_template_id', 1);

		if ($this->action == 'index') {

		} else if ($this->action == 'view') {

		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['rubric_template_id']['type'] = 'select';
			$this->fields['rubric_template_id']['options'] = $rubricTemplateOptions;

			if ($this->request->is(array('post', 'put'))) {
			} else {
				$this->request->data['RubricTemplateOption']['rubric_template_id'] = $selectedRubricTemplate;
			}
		}

		$this->controller->set('contentHeader', __('Templates Options'));
		$contentHeader = __('Templates Options');
		$this->controller->set(compact('contentHeader', 'rubricTemplateOptions', 'selectedRubricTemplate'));
	}

	public function index() {
		$named = $this->controller->params->named;

		$rubricTemplates = $this->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedRubricTemplate = isset($named['template']) ? $named['template'] : key($rubricTemplates);

		$rubricTemplateOptions = array();
		foreach ($rubricTemplates as $key => $rubricTemplate) {
			$rubricTemplateOptions['template:' . $key] = $rubricTemplate;
		}

		$this->contain('RubricTemplate');
		$data = $this->find('all', array(
			'conditions' => array(
				'RubricTemplateOption.rubric_template_id' => $selectedRubricTemplate
			),
			'order' => array(
				'RubricTemplateOption.order', 'RubricTemplateOption.name'
			)
		));

		$this->controller->set(compact('data', 'rubricTemplateOptions', 'selectedRubricTemplate'));
	}
}
