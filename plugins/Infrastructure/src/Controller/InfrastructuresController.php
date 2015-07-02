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
			'Levels' => ['className' => 'Infrastructure.InfrastructureLevels'],
			'Types' => ['className' => 'Infrastructure.InfrastructureTypes'],
			'CustomFields' => ['className' => 'Infrastructure.InfrastructureCustomFields']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Levels' => [
				'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Levels'],
				'text' => __('Levels')
			],
			'Types' => [
				'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'Types'],
				'text' => __('Types')
			],
			'CustomFields' => [
				'url' => ['plugin' => 'Infrastructure', 'controller' => 'Infrastructures', 'action' => 'CustomFields'],
				'text' => __('Custom Fields')
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

    public function beforePaginate(Event $event, Table $model, ArrayObject $options) {
    	if ($model->alias == 'Levels') {
			$parentId = !is_null($this->request->query('parent_id')) ? $this->request->query('parent_id') : 0;

			$options['conditions'][] = [
	        	$model->aliasField('parent_id') => $parentId
	        ];
    	} else if ($model->alias == 'Types' || $model->alias == 'CustomFields') {
			$query = $this->request->query;

			$levelOptions = TableRegistry::get('Infrastructure.InfrastructureLevels')->find('list')->toArray();
	        $selectedLevel = isset($query['level']) ? $query['level'] : key($levelOptions);
	        $options['conditions'][] = [
	        	$model->aliasField('infrastructure_level_id') => $selectedLevel
	        ];

			$this->set(compact('levelOptions', 'selectedLevel'));
    	}
    }
}
