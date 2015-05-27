<?php
namespace Infrastructure\Controller;

use App\Controller\AppController;
use Cake\Event\Event;

class InfrastructuresController extends AppController
{
	public function initialize() {
		parent::initialize();

		//$this->ControllerAction->model('Infrastructure.InfrastructureLevels');
		$this->ControllerAction->models = [
			'Levels' => ['className' => 'Infrastructure.InfrastructureLevels'],
			'Types' => ['className' => 'Infrastructure.InfrastructureTypes']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

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
	}
}
