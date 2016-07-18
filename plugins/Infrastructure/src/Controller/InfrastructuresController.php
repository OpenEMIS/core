<?php
namespace Infrastructure\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;

class InfrastructuresController extends AppController
{
	public function initialize() {
		parent::initialize();
		// pr($this->request->query);
		$this->ControllerAction->models = [
			'Fields' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'Pages' => ['className' => 'Infrastructure.InfrastructureCustomForms']
		];
		$this->loadComponent('Paginator');
    }

    // CAv4
    public function Levels() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.InfrastructureLevels']); }
    public function Types() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.InfrastructureTypes']); }
    public function RoomTypes() { $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Infrastructure.RoomTypes']); }
    // End

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Fields' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Fields'],
				'text' => __('Fields')
			],
			'Pages' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Pages'],
				'text' => __('Pages')
			],
			'Levels' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Levels'],
				'text' => __('Levels')
			],
			'Types' => [
				'url' => ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => 'Types'],
				'text' => __('Types')
			]
		];

		// Types and RoomTypes share one tab
		$selectedAction = ($this->request->action == 'Types' || $this->request->action == 'RoomTypes') ? 'Types' : $this->request->action;
        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $selectedAction);
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Infrastructure');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Infrastructure', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}
