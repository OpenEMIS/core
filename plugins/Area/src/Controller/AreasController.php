<?php
namespace Area\Controller;

use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

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

	public function ajaxGetArea($tableName, $targetModel, $fieldName, $id) {
		$rootId = -1; // Root node

		$Table = TableRegistry::get($tableName);	

		$areaEntity = $Table->get($id);
		$pathId = $areaEntity->id;
		$hasChildren = false;

		// Get the id of any one of the children
		$children = $Table
			->find('children',['for' => $pathId, 'direct' => true])
			->find('threaded')
			->toArray();
		if(!empty($children)){
			$pathId = $children[0]->id;
			$hasChildren = true;
		}

		// Find the path of the tree from the children to the root
		$path = $Table
			->find('path', ['for' => $pathId])
			->contain(['Levels'])
			->order([$Table->aliasField('lft')])
			->all();
		$count = 1;
		$prevousOptionId=-1;

		foreach ($path as $obj) {
			// pr($obj->level->name . ' - ' . $obj->name);
			$parentId = $obj->parent_id;
			$list = $Table
				->find('list')
				->where([$Table->aliasField('parent_id') => $parentId])
				->order([$Table->aliasField('order')])
				->toArray();

			switch($tableName){
				case "Area.AreaAdministratives":
					if( $count > 2 ){
						$list = [$previousOptionId => '--Select Area--'] + $list;
					}
					break;
				default:
					if( $count > 1 ){
						$list = [$previousOptionId => '--Select Area--'] + $list;
					}
					break;
			}

			if(! ($count == count($path)) || ! $hasChildren){
				$obj->selectedId = $obj->id;
			}else{
				$obj->selectedId = $previousOptionId;
			}
			
			$previousOptionId = $obj->id;
			$obj->list = $list;
			$count++;
		}

		// Format the field label
		$fieldNameForFormat = $fieldName;
		if (strpos($fieldName, '.') !== false) {
			$fieldElements = explode('.', $fieldName);
			$fieldNameForFormat = array_pop($fieldElements);
		} else {
			$fieldNameForFormat = $fieldName;
		}
		if (substr($fieldNameForFormat, -3) ==='_id') {
			$fieldNameForFormat = substr($fieldNameForFormat, 0, -3);
		}
		$fieldNameForFormat = __(Inflector::humanize(Inflector::underscore($fieldNameForFormat)));


		$this->set(compact('path', 'targetModel', 'fieldName', 'tableName', 'fieldNameForFormat'));
		$this->layout = false;
	}
}
