<?php
namespace Area\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class AreasController extends AppController
{
	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Levels' => ['className' => 'Area.AreaLevels'],
			'Areas' => ['className' => 'Area.Areas'],
			'AdministrativeLevels' => ['className' => 'Area.AreaAdministrativeLevels'],
			'Administratives' => ['className' => 'Area.AreaAdministratives']
		];
		$this->loadComponent('Paginator');
    }

    public function beforeFilter(Event $event) {
    	parent::beforeFilter($event);

		$tabElements = [
			'Levels' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Levels'],
				'text' => __('Area Levels (Education)')
			],
			'Areas' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Areas'],
				'text' => __('Areas (Education)')
			],
			'AdministrativeLevels' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'AdministrativeLevels'],
				'text' => __('Area Levels (Administrative)')
			],
			'Administratives' => [
				'url' => ['plugin' => 'Area', 'controller' => 'Areas', 'action' => 'Administratives'],
				'text' => __('Areas (Administrative)')
			]
		];

        $this->set('tabElements', $tabElements);
        $this->set('selectedAction', $this->request->action);
	}

	public function onInitialize(Event $event, Table $model) {
		$header = __('Area');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Administrative Boundaries', ['plugin' => 'Area', 'controller' => 'Areas', 'action' => $model->alias]);
		$this->Navigation->addCrumb($this->viewVars['tabElements'][$model->alias]['text']);

		$this->set('contentHeader', $header);
    }

    public function ajaxGetArea() {
    	// Getting the first part of the pass value as the model name
    	$passedValue = $this->request->pass;
    	$tableName = $passedValue[0];
    	$id = $passedValue[1];
    	//pr($this->ControllerAction->model->alias);
    	$tableItems = TableRegistry::get($passedValue[0]);
    	$list = $tableItems
    		->find('list')
    		->where(['parent_id'=>$id])
    		->toArray();
    		
		
    	// $this->set('id', $id);
    	// $this->set('code', $code);
    	// $this->set('name', $name);

    	$this->set(compact('list', 'tableName'));
    	$this->layout = false;
	}
}
