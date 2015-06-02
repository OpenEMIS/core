<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class RubricSectionsTable extends AppTable {
	private $selectedTemplate = null;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->hasMany('RubricCriterias', ['className' => 'Rubric.RubricCriterias', 'dependent' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('name', [
	    		'unique' => [
			        'rule' => ['validateUnique', ['scope' => 'rubric_template_id']],
			        'provider' => 'table',
			        'message' => 'This name is already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['ControllerAction.beforeAction'] = 'beforeAction';
		$events['ControllerAction.beforeAdd'] = 'beforeAdd';
		return $events;
	}

	public function beforeAction($event) {
		if ($this->action == 'index') {
            $toolbarElements = [
                ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
            ];

            $this->controller->set('toolbarElements', $toolbarElements);
		} else if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['rubric_template_id']['type'] = 'select';
			$this->ControllerAction->setFieldOrder('rubric_template_id', 1);
		}
	}

	public function beforeAdd($event, $entity) {
		$query = $this->request->query;

		$templateOptions = $this->RubricTemplates->getList();
		$selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

		$entity->rubric_template_id = $selectedTemplate;

		return $entity;
	}
}
