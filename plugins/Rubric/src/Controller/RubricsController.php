<?php
namespace Rubric\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

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

    	$header = __('Rubric');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
			$header .= ' - ' . $model->alias;

			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);

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
}
