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

class RubricCriteria extends QualityAppModel {
	private $criteriaType = array(
		1 => array('id' => 1, 'name' => 'Section Break'),
		2 => array('id' => 2, 'name' => 'Criteria')
	);

	public $belongsTo = array(
		'RubricSection' => array(
            'className' => 'Quality.RubricSection',
            'foreignKey' => 'rubric_section_id'
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

	public $hasMany = array(
        'RubricCriteriaOption' => array(
            'className' => 'Quality.RubricCriteriaOption',
			'dependent' => true
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
	            'rule' => array('checkUnique', array('name', 'rubric_section_id'), false),
	            'message' => 'This name is already exists in the system'
	        )
		)
	);

	public function beforeAction() {
		$named = $this->controller->params->named;

		$templates = $this->RubricSection->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedTemplate = isset($named['template']) ? $named['template'] : key($templates);

		$templateOptions = array();
		foreach ($templates as $key => $template) {
			$templateOptions['template:' . $key] = $template;
		}

		$sections = $this->RubricSection->find('list', array(
			'conditions' => array(
				'RubricSection.rubric_template_id' => $selectedTemplate
			),
			'order' => array('RubricSection.order', 'RubricSection.name')
		));
		$selectedSection = isset($named['section']) ? $named['section'] : key($sections);

		$sectionOptions = array();
		foreach ($sections as $key => $section) {
			$sectionOptions['section:' . $key] = $section;
		}

		if ($this->action == 'reorder') {
			$this->Navigation->addCrumb('Criterias', array('controller' => 'QualityRubrics', 'action' => 'RubricCriteria', 'index', 'template' => $selectedTemplate, 'section' => $selectedSection, 'plugin' => false));
			$this->Navigation->addCrumb('Reorder');
			$contentHeader = __('Criterias - Reorder');
		} else if ($this->action == 'preview') {
			$this->Navigation->addCrumb('Criterias', array('controller' => 'QualityRubrics', 'action' => 'RubricCriteria', 'index', 'template' => $selectedTemplate, 'section' => $selectedSection, 'plugin' => false));
			$this->Navigation->addCrumb('Preview');
			$contentHeader = __('Criterias - Preview');
		} else {
			$this->Navigation->addCrumb('Criterias');
			$contentHeader = __('Criterias');
		}

		$criteriaTypeOptions = array();
		foreach ($this->criteriaType as $key => $criteriaType) {
			$criteriaTypeOptions[$criteriaType['id']] = __($criteriaType['name']);
		}
		$selectedCriteriaType = key($criteriaTypeOptions);

		$this->fields['criterias'] = array(
			'type' => 'element',
			'element' => '../../Plugin/Quality/View/RubricCriteria/criterias',
			'visible' => true
		);

		$this->fields['order']['visible'] = false;
		$this->ControllerAction->setFieldOrder('rubric_section_id', 1);
		$this->ControllerAction->setFieldOrder('name', 2);
		$this->ControllerAction->setFieldOrder('type', 3);
		$this->ControllerAction->setFieldOrder('criterias', 4);

		if ($this->action == 'view') {
			$this->fields['rubric_section_id']['dataModel'] = 'RubricSection';
			$this->fields['rubric_section_id']['dataField'] = 'name';

			$this->fields['type']['dataModel'] = 'CriteriaType';
			$this->fields['type']['dataField'] = 'name';
		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['rubric_section_id']['type'] = 'select';
			$this->fields['rubric_section_id']['options'] = $sections;

			$this->fields['type']['type'] = 'select';
			$this->fields['type']['options'] = $criteriaTypeOptions;
			$this->fields['type']['attr'] = array('onchange' => "$('#reload').click()");

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;

				if ($data['submit'] == 'reload') {
					$this->ControllerAction->autoProcess = false;
				} else {
					$this->ControllerAction->autoProcess = true;
				}
			} else {
				$this->request->data['RubricCriteria']['rubric_section_id'] = $selectedSection;
				$this->request->data['RubricCriteria']['type'] = $selectedCriteriaType;
			}
		} else if ($this->action == 'reorder' || $this->action == 'moveOrder') {
			$params['conditions'] = array('RubricCriteria.rubric_section_id' => $selectedSection);
			$this->controller->set(compact('params'));
		}

		$this->controller->set(compact('contentHeader', 'templateOptions', 'selectedTemplate', 'sectionOptions', 'selectedSection', 'criteriaTypeOptions'));
	}

	public function afterAction() {
		if ($this->action == 'view') {
			$data = $this->controller->viewVars['data'];

			$selectedCriteriaId = $data['RubricCriteria']['id'];
			$selectedCriteriaType = $data['RubricCriteria']['type'];

			$data['CriteriaType'] = $this->criteriaType[$selectedCriteriaType];
			$this->controller->set('data', $data);

			if ($selectedCriteriaType == 1) {	//1-> Section Break, 2 -> Dropdown
				unset($this->fields['criterias']);
			} else if ($selectedCriteriaType == 2) {
				$this->RubricCriteriaOption->contain('RubricTemplateOption');
				$criteriaOptions = $this->RubricCriteriaOption->find('all', array(
					'conditions' => array(
						'RubricCriteriaOption.rubric_criteria_id' => $selectedCriteriaId
					),
					'order' => array('RubricTemplateOption.order', 'RubricTemplateOption.name')
				));

				$this->controller->set(compact('criteriaOptions'));
			}
		} else if ($this->action == 'add' || $this->action == 'edit') {
			$data = $this->request->data;

			$selectedSection = $data['RubricCriteria']['rubric_section_id'];
			$selectedCriteriaType = $data['RubricCriteria']['type'];
			$selectedTemplate = $this->RubricSection->field('rubric_template_id', array('RubricSection.id' => $selectedSection));

			if ($selectedCriteriaType == 1) {	//1-> Section Break, 2 -> Dropdown
				unset($this->fields['criterias']);
			} else if ($selectedCriteriaType == 2) {
				$this->RubricCriteriaOption->RubricTemplateOption->contain();
				$templateOptions = $this->RubricCriteriaOption->RubricTemplateOption->find('all', array(
					'conditions' => array(
						'RubricTemplateOption.rubric_template_id' => $selectedTemplate
					),
					'order' => array('RubricTemplateOption.order', 'RubricTemplateOption.name')
				));

				$this->controller->set(compact('templateOptions'));

				$criteriaOptions = array();
				foreach ($templateOptions as $key => $obj) {
					$templateOptionId = $obj['RubricTemplateOption']['id'];
				    $criteriaOptions[$templateOptionId] = array(
				    	'name' => '',
				    	'rubric_template_option_id' => $templateOptionId
				    );
				}

				if (!empty($data['RubricCriteriaOption'])) {
					foreach ($data['RubricCriteriaOption'] as $key => $obj) {
						$templateOptionId = $obj['rubric_template_option_id'];
						if (array_key_exists($templateOptionId, $criteriaOptions)) {
							$criteriaOptions[$templateOptionId] = $obj;
						}
					}
				}

				$this->request->data['RubricCriteriaOption'] = $criteriaOptions;
			}
		}
	}

	public function index() {
		$named = $this->controller->params->named;

		$templates = $this->RubricSection->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedTemplate = isset($named['template']) ? $named['template'] : key($templates);

		$templateOptions = array();
		foreach ($templates as $key => $template) {
			$templateOptions['template:' . $key] = $template;
		}

		if (empty($templateOptions)) {
			$this->controller->Message->alert('RubricTemplate.noTemplate');
		} else {
			$sections = $this->RubricSection->find('list', array(
				'conditions' => array(
					'RubricSection.rubric_template_id' => $selectedTemplate
				),
				'order' => array('RubricSection.order', 'RubricSection.name')
			));
			$selectedSection = isset($named['section']) ? $named['section'] : key($sections);

			$sectionOptions = array();
			foreach ($sections as $key => $section) {
				$sectionOptions['section:' . $key] = $section;
			}

			if (empty($sectionOptions)) {
				$this->controller->Message->alert('RubricSection.noSection');
			} else {
				$this->contain('RubricSection', 'RubricCriteriaOption');
				$data = $this->find('all', array(
					'conditions' => array(
						'RubricCriteria.rubric_section_id' => $selectedSection
					),
					'order' => array(
						'RubricCriteria.order', 'RubricCriteria.id'
					)
				));

				$this->controller->set(compact('data', 'sectionOptions', 'selectedSection'));
			}

			$this->controller->set(compact('templateOptions', 'selectedTemplate'));
		}
	}

	public function preview() {
		$named = $this->controller->params->named;

		$templates = $this->RubricSection->RubricTemplate->find('list', array(
			'order' => array('RubricTemplate.name')
		));
		$selectedTemplate = isset($named['template']) ? $named['template'] : key($templates);

		$templateOptions = array();
		foreach ($templates as $key => $template) {
			$templateOptions['template:' . $key] = $template;
		}

		if (empty($templateOptions)) {
			$this->controller->Message->alert('RubricTemplate.noTemplate');
		} else {
			$sections = $this->RubricSection->find('list', array(
				'conditions' => array(
					'RubricSection.rubric_template_id' => $selectedTemplate
				),
				'order' => array('RubricSection.order', 'RubricSection.name')
			));
			$selectedSection = isset($named['section']) ? $named['section'] : key($sections);

			$sectionOptions = array();
			foreach ($sections as $key => $section) {
				$sectionOptions['section:' . $key] = $section;
			}

			if (empty($sectionOptions)) {
				$this->controller->Message->alert('RubricSection.noSection');
			} else {
				$this->RubricCriteriaOption->RubricTemplateOption->contain();
				$rubricTemplateOptionData = $this->RubricCriteriaOption->RubricTemplateOption->find('all', array(
					'conditions' => array(
						'RubricTemplateOption.rubric_template_id' => $selectedTemplate
					),
					'order' => array('RubricTemplateOption.order', 'RubricTemplateOption.name')
				));
				$rubricTemplateOptions = array();
				foreach ($rubricTemplateOptionData as $key => $obj) {
					$rubricTemplateOptions[$obj['RubricTemplateOption']['id']] = $obj['RubricTemplateOption'];
				}

				$rubricTemplateOptionCount = sizeof($rubricTemplateOptions);

				$this->contain('RubricCriteriaOption');
				$data = $this->find('all', array(
					'conditions' => array(
						'RubricCriteria.rubric_section_id' => $selectedSection
					),
					'order' => array(
						'RubricCriteria.order', 'RubricCriteria.name'
					)
				));

				$this->controller->set(compact('data', 'rubricTemplateOptions', 'rubricTemplateOptionCount', 'sectionOptions', 'selectedSection'));
			}

			$this->controller->set(compact('templateOptions', 'selectedTemplate'));
		}
	}
}
