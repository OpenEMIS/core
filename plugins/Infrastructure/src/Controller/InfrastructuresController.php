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

		$this->ControllerAction->models = [
			'Fields' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'Pages' => ['className' => 'Infrastructure.InfrastructureCustomForms'],
			'Levels' => ['className' => 'Infrastructure.InfrastructureLevels'],
			'Types' => ['className' => 'Infrastructure.InfrastructureTypes']
		];
		$this->loadComponent('Paginator');
    }

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

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Infrastructure');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Infrastructure', ['plugin' => $this->plugin, 'controller' => $this->name, 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}
