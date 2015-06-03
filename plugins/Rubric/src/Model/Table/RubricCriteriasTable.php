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

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.beforeAction'] = 'beforeAction';
		$events['ControllerAction.afterAction'] = 'afterAction';
		$events['ControllerAction.beforeView'] = 'beforeView';
		$events['ControllerAction.afterView'] = 'afterView';
		$events['ControllerAction.beforeAdd'] = 'beforeAdd';
		$events['ControllerAction.addReload'] = 'addReload';
		$events['ControllerAction.beforeEdit'] = 'beforeEdit';
		$events['ControllerAction.editReload'] = 'editReload';
		return $events;
	}

	public function beforeAction($event) {
		$criteriaTypeOptions = [];
		foreach ($this->criteriaType as $key => $criteriaType) {
			$criteriaTypeOptions[$criteriaType['id']] = __($criteriaType['name']);
		}

		if ($this->action == 'index') {
			$toolbarElements = [
                ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
            ];

			$this->controller->set('toolbarElements', $toolbarElements);
		} else if($this->action == 'add' || $this->action == 'edit') {
			$query = $this->request->query;

			$templateOptions = $this->RubricSections->RubricTemplates->find('list')->toArray();
			$selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

            $sectionOptions = $this->RubricSections->find('list')
	        	->find('order')
	        	->where([$this->RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
	        	->toArray();
        
        	$selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);

			$this->fields['rubric_section_id']['type'] = 'select';
			$this->fields['rubric_section_id']['options'] = $sectionOptions;

			$this->fields['type']['type'] = 'select';
			$this->fields['type']['options'] = $criteriaTypeOptions;
			$this->fields['type']['attr'] = ['onchange' => "$('#reload').click()"];

			$this->ControllerAction->addField('criterias', [
				'type' => 'element',
				'order' => 4,
				'element' => 'Rubric.criterias',
				'visible' => false
			]);

			$order = 1;
			$this->ControllerAction->setFieldOrder('rubric_section_id', $order++);
			$this->ControllerAction->setFieldOrder('name', $order++);
			$this->ControllerAction->setFieldOrder('type', $order++);
			$this->ControllerAction->setFieldOrder('criterias', $order++);
		}
	}

	public function afterAction($event) {
		/*
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
		*/
	}

	public function beforeView($event, $query, $contain) {
		//$contain[] = 'RubricCriteriaOptions';
		return compact('query', 'contain');
	}

	public function afterView($event, $entity) {
		return $entity;
	}

	public function beforeAdd($event, $entity) {
		$query = $this->request->query;

		$templateOptions = $this->RubricSections->RubricTemplates->find('list')->toArray();
		$selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

        $sectionOptions = $this->RubricSections->find('list')
        	->find('order')
        	->where([$this->RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
        	->toArray();
    
    	$selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);

    	// type
    	$criteriaTypeOptions = [];
		foreach ($this->criteriaType as $key => $criteriaType) {
			$criteriaTypeOptions[$criteriaType['id']] = __($criteriaType['name']);
		}
		$selectedCriteriaType = key($criteriaTypeOptions);

		$entity->rubric_section_id = $selectedSection;
		$entity->type = $selectedCriteriaType;

    	return $entity;
	}

	public function addReload($event, $entity) {
		return $entity;
	}

	public function beforeEdit($event, $query, $contain) {
		return compact('query', 'contain');
	}

	public function editReload($event, $entity) {
		return $entity;
	}

	public function	setCriteriaElement($entity) {
		pr($entity);
	}
}
