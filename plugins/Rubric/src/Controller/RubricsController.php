<?php
namespace Rubric\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RubricsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Templates' => ['className' => 'Rubric.RubricTemplates'],
			'Sections' => ['className' => 'Rubric.RubricSections'],
			'Criterias' => ['className' => 'Rubric.RubricCriterias'],
			'Options' => ['className' => 'Rubric.RubricTemplateOptions']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Templates' => [
				'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Templates'],
				'text' => __('Templates')
			],
			'Sections' => [
				'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Sections'],
				'text' => __('Sections')
			],
			'Criterias' => [
				'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Criterias'],
				'text' => __('Criterias')
			],
			'Options' => [
				'url' => ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => 'Options'],
				'text' => __('Options')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

    public function onInitialize(Event $event, Table $model) {
		$header = __('Rubric');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Rubric', ['plugin' => 'Rubric', 'controller' => 'Rubrics', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, array $options) {
    	if ($model->alias == 'Sections' || $model->alias == 'Criterias' || $model->alias == 'Options') {
			$query = $this->request->query;

			$templates = TableRegistry::get('Rubric.RubricTemplates')->find('list')->toArray();
	        $selectedTemplate = isset($query['template']) ? $query['template'] : key($templates);

	        $templateOptions = [];
	        foreach ($templates as $key => $template) {
	            $templateOptions['template=' . $key] = $template;
	        }

			$columns = $model->schema()->columns();
			if (in_array('rubric_section_id', $columns)) {
				$RubricSections = TableRegistry::get('Rubric.RubricSections');
				$sections = $RubricSections->find('list')
		        	->find('order')
		        	->where([$RubricSections->aliasField('rubric_template_id') => $selectedTemplate])
		        	->toArray();
		        
		        $selectedSection = isset($query['section']) ? $query['section'] : key($sections);

		        $sectionOptions = [];
		        foreach ($sections as $key => $section) {
		            $sectionOptions['section=' . $key] = $section;
		        }

		        $options['conditions'][] = [
		        	$model->aliasField('rubric_section_id') => $selectedSection
		        ];

				$this->set('selectedSection', $selectedSection);
				$this->set('sectionOptions', $sectionOptions);
			} else {
				$options['conditions'][] = [
		        	$model->aliasField('rubric_template_id') => $selectedTemplate
		        ];
			}

	        $options['order'] = [
	        	$model->aliasField('order')
	        ];

	        $this->set('selectedTemplate', $selectedTemplate);
	        $this->set('templateOptions', $templateOptions);
    	}

    	return $options;
    }
}
