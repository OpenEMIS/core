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
			'Status'			=> ['className' => 'Assessment.AssessmentStatuses']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Assessments', ['plugin' => 'Assessment', 'controller' => 'Assessments', 'action' => $this->request->action]);
		$this->Navigation->addCrumb(Inflector::humanize(Inflector::underscore($this->request->action)));

    	$header = __('Assessment');
    	$controller = $this;

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
			],
			'Status' => [
				'url' => ['plugin' => $plugin, 'controller' => $this->name, 'action' => 'Status'],
				'text' => __('Status')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
