<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class RubricTemplateOptionsTable extends AppTable {
	private $selectedTemplate = null;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplates', ['className' => 'Rubric.RubricTemplates']);
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

	public function beforeAction() {
		$this->fields['color']['type'] = 'color';

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
                    	$model->aliasField('rubric_template_id') => $this->selectedTemplate
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
		} else if($this->action == 'add' || $this->action == 'edit') {
			$templateOptions = $this->RubricTemplates->getList();

			if ($this->request->is(array('post', 'put'))) {
			} else {
				if ($this->action == 'add') {
					$query = $this->request->query;
					$selectedTemplate = isset($query['template']) ? $query['template'] : key($templateOptions);

					$this->request->data[$this->alias()]['rubric_template_id'] = $selectedTemplate;
					$this->request->data[$this->alias()]['color'] = '#ff00ff';
					$data = $this->newEntity($this->request->data, ['validate' => false]);
					$this->controller->set('data', $data);
				}
			}

			$this->fields['rubric_template_id']['type'] = 'select';
			$this->fields['rubric_template_id']['options'] = $templateOptions;

			$this->ControllerAction->setFieldOrder('rubric_template_id', 1);
		}
	}
}
