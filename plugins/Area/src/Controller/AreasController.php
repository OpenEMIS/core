<?php
namespace Area\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\UtilityTrait;

class AreasController extends AppController
{
	use UtilityTrait;

	public function initialize() {
		parent::initialize();

		$this->ControllerAction->models = [
			'Levels' => ['className' => 'Area.AreaLevels', 'options' => ['deleteStrategy' => 'transfer']],
			'Areas' => ['className' => 'Area.Areas', 'options' => ['deleteStrategy' => 'transfer']],
			'AdministrativeLevels' => ['className' => 'Area.AreaAdministrativeLevels', 'options' => ['deleteStrategy' => 'transfer']],
			'Administratives' => ['className' => 'Area.AreaAdministratives', 'options' => ['deleteStrategy' => 'transfer']]
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

	public function onInitialize(Event $event, Table $model, ArrayObject $extra) {
		$header = __('Area');

		$header .= ' - ' . $model->getHeader($model->alias);
		$this->Navigation->addCrumb('Administrative Boundaries', ['plugin' => 'Area', 'controller' => 'Areas', 'action' => $model->alias]);
		$this->Navigation->addCrumb($this->viewVars['tabElements'][$model->alias]['text']);

		$this->set('contentHeader', $header);
	}

	public function ajaxGetArea($tableName, $targetModel, $id, $displayCountry = true) {
		$this->getView()->layout('ajax');
		$rootId = -1; // Root node

		$condition = [];
		$accessControlAreaCount = 0;
		$AccessControl = $this->AccessControl;
		$Table = TableRegistry::get($tableName);	
		$areaEntity = $Table->get($id);
		$pathId = $areaEntity->id;
		$hasChildren = false;
		$formError = $this->request->query('formerror');
		if (!$displayCountry) {
			if ($tableName == 'Area.AreaAdministratives') {
				$worldId = $Table->find()->where([$Table->aliasField('parent_id') => -1])->first()->id;
				$condition[] = [
					'OR' => [
						[$Table->aliasField('is_main_country') => 1],
						[$Table->aliasField('parent_id').' IS NOT ' => $worldId]
					]
				];
			} 
		}
		if (! $AccessControl->isAdmin()) {
			if ($tableName == 'Area.Areas') {

				// Display the list of the areas that are authorised to the user
				$authorisedArea = $this->AccessControl->getAreasByUser();

				if ($displayCountry !== true) {
					// Using the display country variable here which is passed over from the institution table
					$areaId = $Table->find()
						->select([
							'area_id' => $Table->aliasField('id'), 
							'lft' => $Table->aliasField('lft'), 
							'rght'=> $Table->aliasField('rght')
						])
						->where([$Table->aliasField('id') => $displayCountry])
						->first()
						->toArray();

					$authorisedArea[] = $areaId;
					$accessControlAreaCount = count($authorisedArea);
				}

				$areaCondition = [];
				$parentIds = [];
				foreach ($authorisedArea as $area) {
					$areaCondition[] = [
						$Table->aliasField('lft').' >= ' => $area['lft'],
						$Table->aliasField('rght').' <= ' => $area['rght']
					];

					// Find all parent ids
					$parentIds = array_merge($parentIds, $Table
						->find('path', ['for' => $area['area_id']])
						->find('list', [
								'keyField' => 'id',
								'valueField' => 'id'
							])
						->order([$Table->aliasField('lft')])
						->toArray());
				}
				$areaCondition[] = [
						$Table->aliasField('id').' IN' => $parentIds
					];
				$condition['OR'] = $areaCondition;
			}
		}

		// Get the id of any one of the children
		$children = $Table
			->find('children',['for' => $pathId, 'direct' => true])
			->find('threaded')
			->toArray();
		if(!empty($children)){
			$pathId = $children[0]->id;
			$hasChildren = true;
		}

		$levelAssociation = Inflector::singularize($Table->alias()).'Levels';

		// Find the path of the tree from the children to the root
		$path = $Table
			->find('path', ['for' => $pathId])
			->contain([$levelAssociation])
			->order([$Table->aliasField('lft')])
			->all();
		$count = 1;
		$prevousOptionId=-1;
		$pathToUnset = [];
		foreach ($path as $obj) {
			if (! $AccessControl->isAdmin() && $tableName == 'Area.Areas') {
				if (!in_array($obj->id, $parentIds)) {
					$pathToUnset[] = $count - 1;
					continue;
				}
			}	
			$parentId = $obj->parent_id;
			$list = $Table
				->find('list')
				->where([$Table->aliasField('parent_id') => $parentId])
				->order([$Table->aliasField('order')])
				->where($condition)
				->toArray();

			switch($tableName){
				case "Area.AreaAdministratives":
					if( $count > 2 ){
						$list = [$previousOptionId => '--'.__('Select Area').'--'] + $list;
					}
					break;
				default:
					if( $count > 1 ){
						if (! $AccessControl->isAdmin()) {
							if (in_array($parentId, $this->array_column($authorisedArea, 'area_id'))) {
								$list = [$previousOptionId => '--'.__('Select Area').'--'] + $list;	
							}
						} else {
							$list = [$previousOptionId => '--'.__('Select Area').'--'] + $list;
						}
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

		$path = $path->toArray();

		foreach ($pathToUnset as $arrIndex) {
			unset($path[$arrIndex]);
		}
		$levelAssociation = Inflector::underscore(Inflector::singularize($levelAssociation));
		$this->set(compact('path', 'targetModel', 'tableName', 'formError', 'displayCountry', 'levelAssociation'));
	}
}
