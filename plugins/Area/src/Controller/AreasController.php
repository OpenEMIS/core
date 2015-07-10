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

	public function ajaxGetArea($tableName, $id) {

		//pr($this->ControllerAction->model->alias);
		$rootId = -1; // Root node
		// Get the default table item
		$Table = TableRegistry::get($tableName);

		// Getting the parent id of the item that is posted over
		$areaEntity = $Table->get($id);
		$parentId = $areaEntity['parent_id'];

		$path = $Table
			->find('path', ['for' => $areaEntity->id])
			->contain(['Levels'])
			->order([$Table->aliasField('lft')])
			->all();

		// $findChildren = $Table
		// 	->find('children', ['for' => $areaEntity->id, true])
		// 	->contain(['Levels'])
		// 	->order([$Table->aliasField('lft')])
		// 	->all();

		//pr($path);
		//$stack = [];
		foreach ($path as $obj) {
			// pr($obj->level->name . ' - ' . $obj->name);
			$parentId = $obj->parent_id;
			// pr($parentId);

			$list = $Table
				->find('list')
				->where([$Table->aliasField('parent_id') => $parentId])
				->order([$Table->aliasField('order')])
				->toArray();

			$obj->list = $list;
			 //pr($obj);
		}

		// $children = $Table
		// 	->find('list')
		// 	->where(['parent_id'=>$id])
		// 	->contain(['Levels']);
		// $children = $query->all();

		// pr($path);
   //  	$arrayToPrint = [];
   //  	$parentIdArray = [];
   //  	$listOfAreaLevel = [];
	  // 	array_push($parentIdArray, $id);
   //	 	array_push($parentIdArray, $parentId);	  	
   //  	// Get the parent nodes
   //	 	while( $parentId > $rootId ){
   //  		$parentItem = $tableItems->get($parentId)->toArray();
			// $parentId = $parentItem['parent_id'];
			// array_push($parentIdArray, $parentId);
	  // 	}

	  // 	// Handle Area.AreaAdministratives
	  // 	if($tableName == 'Area.AreaAdministratives'){
	  // 		array_pop($parentIdArray);
	  // 	}

	  // 	$idToPass = $parentIdArray;



	  // 	while(!empty($parentIdArray)){
	  // 		$id = array_pop($parentIdArray);

	  // 		// If there is no children already, disable checkbox
	  //   	$query = $tableItems
	  //   		->find('list')
	  //   		->where(['parent_id'=>$id])
	  //   		->contain(['Levels']);
	  //   	$results = $query->toArray();

	  //   	// Add a select item into the list
   //  		$firstOption = ["-1"=>"--Select Area--"];
   //  		$results = $firstOption + $results;
   //  		$arrayToPrint[] = $results;
   //  	}

		// if($results){
		// 	// Add a select item into the list
		// 	$firstOption = ["-1"=>"--Select Area--"];
		// 	$results = $firstOption + $results;
		// 	$arrayToPrint[] = $results;
		// }else{
		// 	$arrayToPrint[] = [];
		// 	$disabled = true;
		// }

		// $this->set('id', $id);
		// $this->set('code', $code);
		// $this->set('name', $name);

		$this->set(compact('path', 'children', 'tableName'));
		$this->layout = false;
	}
}
