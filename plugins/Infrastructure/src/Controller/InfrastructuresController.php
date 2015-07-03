<?php
namespace Infrastructure\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;

class InfrastructuresController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Fields' => ['className' => 'Infrastructure.InfrastructureCustomFields'],
			'Levels' => ['className' => 'Infrastructure.InfrastructureLevels'],
			'Types' => ['className' => 'Infrastructure.InfrastructureTypes']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Fields' => [
				'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Fields'],
				'text' => __('Fields')
			],
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

	public function onInitialize(Event $event, Table $model) {
		$header = __('Infrastructure');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Infrastructure', ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => $model->alias]);
		$this->Navigation->addCrumb($model->getHeader($model->alias));

		$this->set('contentHeader', $header);
    }
}
