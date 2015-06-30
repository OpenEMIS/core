<?php
namespace Assessment\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class AssessmentsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Assessment.Assessments');
		$this->ControllerAction->models = [
			'GradingTypes'		=> ['className' => 'Assessment.AssessmentGradingTypes'],
			'GradingOptions'	=> ['className' => 'Assessment.AssessmentGradingOptions'],
			// 'Items' => ['className' => 'Assessment.AssessmentItems'],
			// 'Results' => ['className' => 'Assessment.AssessmentResults'],
			// 'ItemResults' => ['className' => 'Assessment.AssessmentItemResults']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Assessments', ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => $this->request->action]);
		$this->Navigation->addCrumb(Inflector::humanize(Inflector::underscore($this->request->action)));

    	$header = __('Assessment');
    	$controller = $this;
  //   	$this->ControllerAction->onInitialize = function($model) use ($controller, $header) {
  //   		$header .= ' - ' . Inflector::humanize(Inflector::underscore($model->alias));

		// 	$controller->set('contentHeader', $header);
		// };

		$this->set('contentHeader', $header);

		$plugin = $this->plugin;

		$tabElements = [
			'Assessments' => [
				'url' => ['plugin' => $plugin, 'controller' => $this->name, 'action' => 'index'],
				'text' => __('Assessments')
			],
			'GradingTypes' => [
				'url' => ['plugin' => $plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
				'text' => __('Grading Types')
			],
			'GradingOptions' => [
				'url' => ['plugin' => $plugin, 'controller' => $this->name, 'action' => 'GradingOptions'],
				'text' => __('Grading Options')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
