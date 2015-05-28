<?php
namespace Infrastructure\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class InfrastructuresController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Levels' => ['className' => 'Infrastructure.InfrastructureLevels'],
			'Types' => ['className' => 'Infrastructure.InfrastructureTypes']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);
    	$this->Navigation->addCrumb('Infrastructure', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => $this->request->action]);
		$this->Navigation->addCrumb($this->request->action);

    	$header = __('Infrastructure');
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
				'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Levels'],
				'text' => __('Levels')
			],
			'Types' => [
				'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Types'],
				'text' => __('Types')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
