<?php
namespace Area\Controller;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class AreasController extends AppController
{
	public function initialize() {
		parent::initialize();

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
    	$this->Navigation->addCrumb('Administrative Boundaries', ['plugin' => 'Area', 'controller' => 'Areas', 'action' => $this->request->action]);
		$this->Navigation->addCrumb(Inflector::humanize(Inflector::underscore($this->request->action)));

    	$header = __('Area');
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
				'text' => __('Areas (Administrative)')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}
}
