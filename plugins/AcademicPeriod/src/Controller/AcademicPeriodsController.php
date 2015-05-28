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

    	$header = __('AcademicPeriod');
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
}
