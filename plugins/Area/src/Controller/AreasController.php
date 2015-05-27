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
			'Areas' => ['className' => 'Area.Areas'],
			'AreaLevels' => ['className' => 'Area.AreaLevels'],
			'AreaAdministratives' => ['className' => 'Area.AreaAdministratives'],
			'AreaAdministrativeLevels' => ['className' => 'Area.AreaAdministrativeLevels']
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
	}
}
