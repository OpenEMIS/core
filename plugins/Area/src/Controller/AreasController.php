<?php
namespace Area\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class AreasController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->model('Area.Areas');
		$this->ControllerAction->models = [
			'AreaLevels' => ['className' => 'Area.AreaLevels'],
			'Areas' => ['className' => 'Area.Areas'],
			'AreaAdministrativeLevels' => ['className' => 'Area.AreaAdministrativeLevels'],
			'AreaAdministratives' => ['className' => 'Area.AreaAdministratives']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

    	$header = __('Area');
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
			'AreaLevels' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AreaLevels'],
				'text' => __('Area Levels (Education)')
			],
			'Areas' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Areas'],
				'text' => __('Areas (Education)')
			],
			'AreaAdministrativeLevels' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AreaAdministrativeLevels'],
				'text' => __('Area Levels (Administrative)')
			],
			'AreaAdministratives' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AreaAdministratives'],
				'text' => __('Area Levels (Administrative)')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
