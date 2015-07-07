<?php
namespace AcademicPeriod\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class AcademicPeriodsController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Levels' => ['className' => 'AcademicPeriod.AcademicPeriodLevels'],
			'Periods' => ['className' => 'AcademicPeriod.AcademicPeriods']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Levels' => [
				'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Levels'],
				'text' => __('Levels')
			],
			'Periods' => [
				'url' => ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => 'Periods'],
				'text' => __('Periods')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

    public function onInitialize($event, $model) {
		$header = __('Academic Period');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Academic Period', ['plugin' => 'AcademicPeriod', 'controller' => 'AcademicPeriods', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}
