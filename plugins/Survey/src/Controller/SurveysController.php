<?php
namespace Survey\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class SurveysController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Questions' => ['className' => 'Survey.SurveyQuestions'],
			'Templates' => ['className' => 'Survey.SurveyTemplates']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Questions' => [
				'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Questions'],
				'text' => __('Questions')
			],
			'Templates' => [
				'url' => ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => 'Templates'],
				'text' => __('Templates')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

	public function onInitialize(Event $event, Table $model) {
		$header = __('Survey');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Survey', ['plugin' => 'Survey', 'controller' => 'Surveys', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }

    public function beforePaginate(Event $event, Table $model, array $options) {
    	return $options;
    }
}
