<?php
namespace Assessment\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class AssessmentsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Types' => ['className' => 'Assessment.AssessmentItemTypes'],
			'Items' => ['className' => 'Assessment.AssessmentItems'],
			'Results' => ['className' => 'Assessment.AssessmentResults'],
			'ItemResults' => ['className' => 'Assessment.AssessmentItemResults']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Assessments', ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => $this->request->action]);
		$this->Navigation->addCrumb(Inflector::humanize(Inflector::underscore($this->request->action)));

    	$header = __('Assessment');
    	$controller = $this;
    	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
    		$header .= ' - ' . Inflector::humanize(Inflector::underscore($model->alias));

			$controller->set('contentHeader', $header);
		};

		$this->ControllerAction->beforePaginate = function($model, $options) {
			// logic here
			return $options;
		};

		$this->set('contentHeader', $header);

		$tabElements = [
			'Types' => [
				'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'Types'],
				'text' => __('Types')
			],
			'Items' => [
				'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'Items'],
				'text' => __('Items')
			],
			'Results' => [
				'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'Results'],
				'text' => __('Results')
			],
			'ItemResults' => [
				'url' => ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => 'ItemResults'],
				'text' => __('Item Results')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
