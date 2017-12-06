<?php
namespace Area\Controller;

use ArrayObject;
use App\Controller\AppController;
use Cake\ORM\Table;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\UtilityTrait;
use App\Model\Traits\MessagesTrait;

class AreasController extends AppController
{
	use UtilityTrait;
	use MessagesTrait;

	public function initialize() {
		parent::initialize();
		$this->loadComponent('Paginator');
	}

    public function Levels() 				{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Area.AreaLevels']); }
    public function AdministrativeLevels() 	{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Area.AreaAdministrativeLevels']); }
    public function Areas() 				{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Area.Areas']); }
    public function Administratives() 		{ $this->ControllerAction->process(['alias' => __FUNCTION__, 'className' => 'Area.AreaAdministratives']); }

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
		$tabElements = $this->TabPermission->checkTabPermission($tabElements);
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
		$this->viewBuilder()->layout('ajax');
		$rootId = null; // Root node

		$condition = [];
		$accessControlAreaCount = 0;
		$AccessControl = $this->AccessControl;
		$Table = TableRegistry::get($tableName);
		if ($id == 0) {
			$areaEntity = $Table->find()->first();
		} else {
			$areaEntity = $Table->get($id);
		}
		$pathId = $areaEntity->id;
		$hasChildren = false;
		$formError = $this->request->query('formerror');
		if (!$displayCountry) {
			if ($tableName == 'Area.AreaAdministratives') {
				$worldId = $Table->find()->where([$Table->aliasField('parent_id') . ' IS NULL'])->first()->id;
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

				if ($displayCountry !== true && $displayCountry != 0) {
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
				$authorisedParentIds = [];
				foreach ($authorisedArea as $area) {
					$areaCondition[] = [
						$Table->aliasField('lft').' >= ' => $area['lft'],
						$Table->aliasField('rght').' <= ' => $area['rght']
					];

					// Find all parent ids
					$authorisedParentIds = array_merge($authorisedParentIds, $Table
						->find('path', ['for' => $area['area_id']])
						->find('list', [
								'keyField' => 'id',
								'valueField' => 'id'
							])
						->order([$Table->aliasField('lft')])
						->toArray());
				}

				if (!empty($authorisedArea)) {
					$authorisedAreaId = $Table
						->find('list', [
								'keyField' => 'id',
								'valueField' => 'id'
							])
						->where(['OR' => $areaCondition])
						->toArray();
				} else {
					$authorisedAreaId = [];
				}

				if (!empty($authorisedParentIds)) {
					$areaCondition[] = [
							$Table->aliasField('id').' IN' => $authorisedParentIds
						];
					$authorisedParentIds = $authorisedParentIds;
					$condition['OR'] = $areaCondition;
				}
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
		$previousOptionId = null;
		$pathToUnset = [];
		$objParentIds = [];
		foreach ($path as $obj) {
			if (! $AccessControl->isAdmin() && $tableName == 'Area.Areas') {
				if (!in_array($obj->id, $authorisedAreaId) && !in_array($obj->id, $authorisedParentIds)) {
					$pathToUnset[] = $count - 1;
					$count++;
					continue;
				}
			}
			$parentId = $obj->parent_id;
			$listQuery = $Table
				->find('list')
				->order([$Table->aliasField('order')])
				->where($condition);

			if (is_null($parentId)) {
				$listQuery->where([$Table->aliasField('parent_id') . ' IS NULL']);
			} else {
				$listQuery->where([$Table->aliasField('parent_id') => $parentId]);
			}

			$list = $listQuery->toArray();

			$newList = [];
			foreach ($list as $key => $area) {
				$newList[$key] = __($area);
			}
			$list = $newList;

			switch($tableName){
				case "Area.AreaAdministratives":
					if( $count > 2 ){
						$list = [$previousOptionId => '--'.__('Select Area').'--'] + $list;
					}
					break;
				default:
					if( $count > 1 ){
						if (! $AccessControl->isAdmin()) {
							if (array_intersect($this->array_column($authorisedArea, 'area_id'), $objParentIds)) {
								$list = [$previousOptionId => '--'.__('Select Area').'--'] + $list;
							}
						} else {
							$list = [$previousOptionId => '--'.__('Select Area').'--'] + $list;
						}
					}
					break;
			}

			$objParentIds [] = $obj->id;

			if(! ($count == count($path)) || ! $hasChildren){
				$obj->selectedId = $obj->id;
			} else{
				$obj->selectedId = $previousOptionId;
			}

			$previousOptionId = $obj->id;
			$obj->list = $list;
			$count++;
		}

		$path = $path->toArray();

		$this->unsetUnauthorisedPath($path, $pathToUnset);

		$levelAssociation = Inflector::underscore(Inflector::singularize($levelAssociation));
		$this->set(compact('path', 'targetModel', 'tableName', 'formError', 'displayCountry', 'levelAssociation'));
	}

	// Function to unset the unauthorised path
	private function unsetUnauthorisedPath(&$path, $pathToUnset) {
		$firstItem = true;
		foreach ($pathToUnset as $arrIndex) {
			if (count($path) == count($pathToUnset)) {
				if ($firstItem) {
					$path[$arrIndex]['list'] = ['0' => $this->getMessage('Areas.noAccessToAreas')];
					$path[$arrIndex]['readonly'] = true;
					$firstItem = false;
					continue;
				}
			}
			unset($path[$arrIndex]);
		}
	}
}
