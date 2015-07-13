<?php
namespace Assessment\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class AssessmentsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Assessments'		=> ['className' => 'Assessment.Assessments'],
			'GradingTypes'		=> ['className' => 'Assessment.AssessmentGradingTypes'],
			'GradingOptions'	=> ['className' => 'Assessment.AssessmentGradingOptions'],
			'Status'			=> ['className' => 'Assessment.AssessmentStatuses']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Assessments' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Assessments'],
				'text' => __('Assessments')
			],
			'GradingTypes' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingTypes'],
				'text' => __('Grading Types')
			],
			'GradingOptions' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'GradingOptions'],
				'text' => __('Grading Options')
			],
			'Status' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Status'],
				'text' => __('Status')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

	public function onInitialize(Event $event, Table $model) {
		$header = __('Assessment');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Assessments', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $this->request->action]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}
