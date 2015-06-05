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

	public function beforeAction($event) {
	}

	public function afterAction($event) {
	}

	public function indexBeforeAction($event) {
		//Only index page has controls dropdown
		$toolbarElements = [
            ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function addBeforeAction($event) {
		$this->setFields();
	}

	public function addOnInitialize($event, $entity) {
		$entity = $this->setFieldValues($entity);
		return $entity;
	}

	public function addBeforePatch($event, $entity, $data, $options) {
		$options['associated'] = ['RubricCriteriaOptions.RubricTemplateOptions'];
		return compact('entity', 'data', 'options');
	}

	public function addOnReload($event, $entity, $data, $options) {
		list($entity, $data, $options) = array_values($this->setCriteriaElement($entity, $data, $options));
		return compact('entity', 'data', 'options');
	}

	public function addAfterAction($event, $entity) {
		$this->setCriteriaVisible($entity);
		return $entity;
	}

	public function editBeforeAction($event) {
		$this->setFields();
	}

	public function editBeforeQuery($event, $query, $contain) {
		$contain = ['RubricCriteriaOptions.RubricTemplateOptions'];
		return compact('query', 'contain');
	}

	public function editBeforePatch($event, $entity, $data, $options) {
		$options['associated'] = ['RubricCriteriaOptions.RubricTemplateOptions'];
		return compact('entity', 'data', 'options');
	}

	public function editAfterAction($event, $entity) {
		$this->setCriteriaVisible($entity);
		return $entity;
	}

	public function getOptions() {
		$query = $this->request->query;

		$templateOptions = $this->RubricSections->RubricTemplates->find('list')->toArray();
		$selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

        $sectionOptions = $this->RubricSections->find('list')
        	->find('order')
        	->where([$this->RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
        	->toArray();
    	$selectedSection = isset($query['section']) ? $query['section'] : key($sectionOptions);

		$criteriaTypeOptions = [];
		foreach ($this->criteriaType as $key => $criteriaType) {
			$criteriaTypeOptions[$criteriaType['id']] = __($criteriaType['name']);
		}
		$selectedCriteriaType = key($criteriaTypeOptions);

		return compact('sectionOptions', 'selectedSection', 'criteriaTypeOptions', 'selectedCriteriaType');
	}

	public function setFields() {
		list($sectionOptions, , $criteriaTypeOptions, ) = array_values($this->getOptions());

		$this->fields['rubric_section_id']['type'] = 'select';
		$this->fields['rubric_section_id']['options'] = $sectionOptions;

		$this->fields['type']['type'] = 'select';
		$this->fields['type']['options'] = $criteriaTypeOptions;
		$this->fields['type']['attr'] = ['onchange' => "$('#reload').click()"];

		$this->ControllerAction->addField('criterias', [
			'type' => 'element',
			'order' => 5,
			'element' => 'Rubric.criterias',
			'visible' => false
		]);

		$order = 1;
		$this->ControllerAction->setFieldOrder('rubric_section_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('type', $order++);
		$this->ControllerAction->setFieldOrder('criterias', $order++);
	}

	public function setFieldValues($entity) {
		list(, $selectedSection, , $selectedCriteriaType) = array_values($this->getOptions());

		$entity->rubric_section_id = $selectedSection;
		$entity->type = $selectedCriteriaType;

		return $entity;
	}

	public function	setCriteriaVisible($entity) {
		$selectedCriteriaType = $entity->type;

		if ($selectedCriteriaType == 1) {	//1-> Section Break, 2 -> Dropdown
			$this->fields['criterias']['visible'] = false;
		} else if ($selectedCriteriaType == 2) {
			$this->fields['criterias']['visible'] = true;
		}
	}

	public function	setCriteriaElement($entity, $data, $options) {
		$selectedSection = $data[$this->alias()]['rubric_section_id'];
		$selectedCriteriaType = $data[$this->alias()]['type'];
		$selectedTemplate = $this->RubricSections->find('all')->where([$this->RubricSections->aliasField('id') => $selectedSection])->first()->rubric_template_id;

		if ($selectedCriteriaType == 1) {	//1-> Section Break, 2 -> Dropdown
		} else if ($selectedCriteriaType == 2) {
			$RubricTemplateOptions = $this->RubricCriteriaOptions->RubricTemplateOptions;
			$templateOptions = $RubricTemplateOptions->find('all')
	        	->find('order')
	        	->where([$RubricTemplateOptions->aliasField('rubric_template_id') => $selectedTemplate])
	        	->toArray();

	        $criteriaOptions = [];
			foreach ($templateOptions as $key => $obj) {
			    $criteriaOptions[$key] = [
			    	'name' => '',
			    	'rubric_template_option_id' => $obj->id,
			    	'rubric_template_option' => [
			    		'name' => $obj->name,
			    		'weighting' => $obj->weighting
			    	]
			    ];
			}

			$data[$this->alias()]['rubric_criteria_options'] = $criteriaOptions;
			//$options['associated'] = ['RubricCriteriaOptions.RubricTemplateOptions'];
			$options['associated'] = [
				'RubricCriteriaOptions' => ['validate' => false, 'associated' => 'RubricTemplateOptions']
			];
		}
		return compact('entity', 'data', 'options');
	}
}
