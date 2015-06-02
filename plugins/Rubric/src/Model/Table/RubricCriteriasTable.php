<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class RubricCriteriasTable extends AppTable {
	private $selectedTemplate = null;
	private $selectedSection = null;
	private $criteriaType = array(
		1 => array('id' => 1, 'name' => 'Section Break'),
		2 => array('id' => 2, 'name' => 'Criteria')
	);

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricSections', ['className' => 'Rubric.RubricSections']);
		$this->hasMany('RubricCriteriaOptions', ['className' => 'Rubric.RubricCriteriaOptions', 'dependent' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'rubric_section_id']],
			        'provider' => 'table',
			        'message' => 'This name is already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function beforeAction() {
		$criteriaTypeOptions = [];
		foreach ($this->criteriaType as $key => $criteriaType) {
			$criteriaTypeOptions[$criteriaType['id']] = __($criteriaType['name']);
		}
		$selectedCriteriaType = key($criteriaTypeOptions);

		if($this->action == 'index') {
			$query = $this->request->query;

			$toolbarElements = [
                ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
            ];

			$templates = $this->RubricSections->RubricTemplates->getList();
            $this->selectedTemplate = isset($query['template']) ? $query['template'] : key($templates);

            $templateOptions = [];
            foreach ($templates as $key => $template) {
                $templateOptions['template=' . $key] = $template;
            }

 			$sectionListOptions = [
                'conditions' => [
                	$this->RubricSections->aliasField('rubric_template_id') => $this->selectedTemplate
                ]
            ];
            $sections = $this->RubricSections->getList($sectionListOptions);
            $this->selectedSection = isset($query['section']) ? $query['section'] : key($sections);

            $sectionOptions = [];
            foreach ($sections as $key => $section) {
                $sectionOptions['section=' . $key] = $section;
            }

			$this->ControllerAction->beforePaginate = function($model, $options) {
                if (!is_null($this->selectedSection)) {
                    $options['conditions'][] = [
                    	$model->aliasField('rubric_section_id') => $this->selectedSection
                    ];
                    $options['order'] = [
                    	$model->aliasField('order'),
                    	$model->aliasField('id')
                    ];
                }

                return $options;
            };

            $this->controller->set('toolbarElements', $toolbarElements);
            $this->controller->set('selectedTemplate', $this->selectedTemplate);
            $this->controller->set('templateOptions', $templateOptions);
            $this->controller->set('selectedSection', $this->selectedSection);
            $this->controller->set('sectionOptions', $sectionOptions);
		} else if($this->action == 'add' || $this->action == 'edit') {
			$query = $this->request->query;

			$templateOptions = $this->RubricSections->RubricTemplates->getList();
			$selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

 			$sectionListOptions = [
                'conditions' => [
                	$this->RubricSections->aliasField('rubric_template_id') => $selectedTemplate
                ]
            ];
            $sectionOptions = $this->RubricSections->getList($sectionListOptions);

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;

				if (isset($data['submit']) && $data['submit'] == 'reload') {
					$this->ControllerAction->autoProcess = false;
					$data = $this->newEntity($this->request->data, ['validate' => false]);
					$this->controller->set('data', $data);
				} else {
					$this->ControllerAction->autoProcess = true;
				}
			} else {
				if ($this->action == 'add') {
					$query = $this->request->query;
					$selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);

					$this->request->data[$this->alias()]['rubric_section_id'] = $selectedSection;
					$this->request->data[$this->alias()]['type'] = $selectedCriteriaType;
					$data = $this->newEntity($this->request->data, ['validate' => false]);
					$this->controller->set('data', $data);
				}
			}

			$this->fields['rubric_section_id']['type'] = 'select';
			$this->fields['rubric_section_id']['options'] = $sectionOptions;

			$this->fields['type']['type'] = 'select';
			$this->fields['type']['options'] = $criteriaTypeOptions;
			$this->fields['type']['attr'] = ['onchange' => "$('#reload').click()"];

			$this->ControllerAction->addField('criterias', ['type' => 'element', 'order' => 4]);
			$this->fields['criterias']['element'] = 'Rubric.criterias';
			$this->fields['criterias']['visible'] = false;

			$order = 1;
			$this->ControllerAction->setFieldOrder('rubric_section_id', $order++);
			$this->ControllerAction->setFieldOrder('name', $order++);
			$this->ControllerAction->setFieldOrder('type', $order++);
			$this->ControllerAction->setFieldOrder('criterias', $order++);
		}
	}

	public function afterAction() {
		if ($this->action == 'view') {
		} else if ($this->action == 'add' || $this->action == 'edit') {
			$data = $this->request->data;

			$selectedSection = $data[$this->alias()]['rubric_section_id'];
			$selectedCriteriaType = $data[$this->alias()]['type'];
			$selectedTemplate = $this->RubricSections->find('all')->where([$this->RubricSections->aliasField('id') => $selectedSection])->first()->rubric_template_id;
			
			if ($selectedCriteriaType == 1) {	//1-> Section Break, 2 -> Dropdown
				$this->fields['criterias']['visible'] = false;
			} else if ($selectedCriteriaType == 2) {
				$this->fields['criterias']['visible'] = true;

				if (empty($this->request->data[$this->alias()]['rubric_criteria_options'])) {
					$RubricTemplateOptions = $this->RubricCriteriaOptions->RubricTemplateOptions;
					$templateOptions = $this->RubricCriteriaOptions->RubricTemplateOptions->find('all', [
						'conditions' => [
							$RubricTemplateOptions->aliasField('rubric_template_id') => $selectedTemplate
						],
						'order' => [
							$RubricTemplateOptions->aliasField('order'),
							$RubricTemplateOptions->aliasField('id')
						]
					])->toArray();

					$criteriaOptions = [];
					foreach ($templateOptions as $key => $obj) {
					    $criteriaOptions[$key] = [
					    	'name' => '',
					    	'rubric_template_option_id' => $obj->id,
					    	'rubric_template_option_name' => $obj->name,
					    	'rubric_template_option_weighting' => $obj->weighting
					    ];
					}

					$this->request->data[$this->alias()]['rubric_criteria_options'] = $criteriaOptions;
				}
			}
		}
	}
}
