<?php
namespace Map\Controller;

use App\Controller\AppController;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class MapController extends AppController
{
	public function initialize()
	{
		parent::initialize();

		$this->attachAngularModules();
	}

	public function beforeFilter(Event $event)
	{
		parent::beforeFilter($event);

		$header = __('Maps');
		$this->set('contentHeader', $header);
	}

	public function index()
	{
		$this->set('ngController', 'MapCtrl as MapController');
        $this->set('noBreadcrumb', true);
	}

	private function attachAngularModules()
	{
		$action = $this->request->action;

		switch ($action) {
			case 'index':
				$this->Angular->addModules([
					'map.ctrl',
					'map.svc'
				]);
			break;
		}
	}
}
