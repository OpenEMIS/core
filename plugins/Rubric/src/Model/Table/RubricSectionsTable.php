<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class RubricSectionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
		$this->hasMany('RubricCriterias', ['className' => 'Rubric.RubricCriterias']);
	}

	public function validationDefault(Validator $validator) {
		$validator
		->requirePresence('name')
		->notEmpty('name', 'Please enter a name.')
    	->add('name', [
    		'unique' => [
		        'rule' => ['validateUnique', ['scope' => 'rubric_template_id']],
		        'provider' => 'table',
		        'message' => 'This name is already exists in the system'
		    ]
	    ])
	    ->requirePresence('rubric_template_id')
		->notEmpty('rubric_template_id', 'Please select a template.');

		return $validator;
	}

	public function beforeAction() {
		$this->ControllerAction->setFieldOrder('rubric_template_id', 1);

		if($this->action == 'index') {
			$query = $this->request->query;

            $toolbarElements = [
                ['name' => 'Rubric.controls', 'data' => [], 'options' => []]
            ];

            $templates = $this->RubricTemplates->getList();
            $this->selectedTemplate = isset($query['template']) ? $query['template'] : key($templates);

            $templateOptions = array();
            foreach ($templates as $key => $template) {
                $templateOptions['template=' . $key] = $template;
            }

            $this->ControllerAction->beforePaginate = function($model, $options) {
                if (!is_null($this->selectedTemplate)) {
                    $options['conditions'][] = [
                        $model->alias().'.rubric_template_id' => $this->selectedTemplate
                    ];
                    $options['order'] = [
                        $model->alias().'.name'
                    ];
                }

                return $options;
            };

            $this->controller->set('toolbarElements', $toolbarElements);
            $this->controller->set('selectedTemplate', $this->selectedTemplate);
            $this->controller->set('templateOptions', $templateOptions);
		} else if($this->action == 'add' || $this->action == 'edit') {
			$templateOptions = $this->RubricTemplates->getList();

			$this->fields['rubric_template_id']['type'] = 'select';
			$this->fields['rubric_template_id']['options'] = $templateOptions;
		}
	}
}
