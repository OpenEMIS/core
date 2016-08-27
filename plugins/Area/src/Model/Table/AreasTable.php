<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Event\Event;

class AreasTable extends AppTable {
	private $_fieldOrder = ['visible', 'code', 'name', 'area_level_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AreaParents', ['className' => 'Area.Areas', 'foreignKey' => 'parent_id']);
		$this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels', 'foreignKey' => 'area_level_id']);
		$this->hasMany('Areas', ['className' => 'Area.Areas', 'foreignKey' => 'parent_id']);
		$this->hasMany('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsToMany('SecurityGroups', [
			'className' => 'Security.UserGroups',
			'joinTable' => 'security_group_areas',
			'foreignKey' => 'area_id',
			'targetForeignKey' => 'security_group_id',
			'through' => 'Security.SecurityGroupAreas',
			'dependent' => false,
		]);
		$this->addBehavior('Tree');
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'parent_id',
			]);
		}
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
		return $events;
	}

	public function sync() {

		//  Temporary hardcoded the source of the json.
		// will be set on the sys_config later.
		// $url = 'http://devinfo-cloud.com/reporting/scinfo_data_admin/api/area';
		$url = 'https://demo.openemis.org/datamanager/api/area';

		$securityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
		$model = $this;
		$request = $this->request;
		$entity = $model->newEntity();
		$extra=[];

		$missingAreaArray = [];
		$updateAreaArray = [];
		$addAreaArray = [];
		$associatedRecords = [];

		$areasTableArray = $this->onGetAreasTableArrays();
		$areaCodeLists = $this->onGetAreaCodeLists();
		$jsonArray = $this->onGetJsonArrays($url);
		$newAreaLists = $this->onGetNewAreaLists($url);
		$jsonCodeLists = $this->onGetJsonCodeLists($url);
		// pr($jsonCodeLists);die;

		foreach ($this->fields as $field => $attr) {
			$this->fields[$field]['visible'] = false;
		}

		$this->ControllerAction->field('data_will_be_synced_from', [
			'type' => 'readonly',
			'attr' => ['value' => $url]
		]);

		foreach ($areasTableArray as $key => $obj) {
			if ((!empty($areasTableArray)) && (!array_key_exists($obj['id'], $jsonArray))) {
				$missingAreaArray[$key] = $obj;
			}
		}

		foreach ($missingAreaArray as $key => $obj) {
			if (array_key_exists($obj['code'], $jsonCodeLists)) {
				$updateAreaArray[$key] = $obj;
			}
		}

		foreach ($jsonArray as $key => $obj) {

		}
// pr($jsonArray);
// pr($areasTableArray);
// pr($missingAreaArray);
// pr($addAreaArray);
// pr($updateAreaArray);
// die;

		if ($this->request->is(['get'])) {
// Pass data to ctp file to be displayed (sync_server.ctp)
			$primaryKey = $this->ControllerAction->getPrimaryKey($model);
        	$idKey = $model->aliasField($primaryKey);

			$extra = new ArrayObject([]);
			$extra['deleteStrategy'] = 'transfer';
			$extra['excludedModels'] = [$this->Areas->alias()];

			foreach ($missingAreaArray as $key => $obj) {
				$id = $obj['id'];

				if ($model->exists([$idKey => $id])) {
					$entity = $model->find()->where([$idKey => $id])->first();
					$records = $this->ControllerAction->getAssociatedRecords($model, $entity, $extra);
					$associatedRecords[$key] = [
						'id' => $id,
						'code' => $obj['code'],
						'name' => $obj['name'],
						'institution' => $records['Institutions']['count'],
						'security_group' => $records['SecurityGroups']['count'],
					];
				}
			}

			$this->ControllerAction->field('sync_server', [
				'type' => 'element',
				'element' => 'Area.Areas/sync_server'
			]);
			$this->controller->set('associatedRecords', $associatedRecords);
			$this->controller->set('newAreaLists', $newAreaLists);
// end pass data
		} else if ($this->request->is(['post', 'put'])) {
			$submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
			if ($submit == 'save') {

// updating the association records (institution and security group area)
				$requestData = $this->request->data;
				// pr($requestData);
				// die;
				if (array_key_exists($this->alias(), $requestData)) {
					if (array_key_exists('transfer_areas', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['transfer_areas'] as $key => $obj) {
							$areaId = $obj['area_id'];
							$newAreaId = $obj['new_area_id'];
							$query = $this->Institutions->updateAll(
								['area_id' => $newAreaId],
								['area_id' => $areaId]
							);
				// 			// pr($query);

							$securityGroupAreas->updateAll(
								['area_id' => $newAreaId],
								['area_id' => $areaId]
							);

				// 			// UPDATE `Institutions` SET `area_id` = $obj['new_area_id'] WHERE `area_id` = $obj['area_id'];
						}
					}
				}
				// die;
// End of updating the association records


// Update areas table
				// pr($areaCodeLists);die;
				if (!empty($updateAreaArray)) {
					$areaTable = TableRegistry::get('areas');
					foreach ($updateArray as $key => $obj) {
						// pr($key);
						// pr($obj['new_area_id']);

						// $articlesTable = TableRegistry::get('Articles');

						$article = $areaTable->get($obj['area_id']);
						// pr($article);
						// $article->id = $obj['new_area_id'];
						// $article->name = 'testing';
						// $areaTable->save($article);

						$areaTable->updateAll(
							['id' => $obj['new_area_id']],
							['id' => $obj['area_id']]
						);


					}
// die;
				}

				if (!empty($missingAreaArray)) {
					foreach ($missingAreaArray as $key => $obj) {
						$this->deleteAll([
							$this->aliasField('code') => $obj['code']
						]);
					}
				}


// copy jsonArray to the AreasTable
				if (!empty($jsonArray)) {
					$areaTable = TableRegistry::get('areas');
					foreach ($jsonArray as $key => $obj) {
						$areasArray = $areaTable->newEntity([
							'id' => $obj['id'],
							'parent_id' => $obj['parent_id'],
							'code' => $obj['code'],
							'name' => $obj['name'],
							'area_level_id' => $obj['area_level_id'],
							'order' => $obj['order']
						]);
						$areaTable->save($areasArray);
					}
				}

				$this->rebuildLftRght();

// redirect to index page
				$url = $this->ControllerAction->url('index');
				unset($url['section']);

				return $this->controller->redirect($url);
// End of redirect to index page
			} else {
				pr('reload');
			}
		}

		$this->controller->set('data', $entity);

		$this->ControllerAction->renderView('/ControllerAction/edit');
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('area_level_id');
		$count = $this->find()->where([
				'OR' => [
					[$this->aliasField('lft').' IS NULL'],
					[$this->aliasField('rght').' IS NULL']
				]
			])
			->count();
		if ($count) {
			$this->rebuildLftRght();
		}
		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
	}

	public function rebuildLftRght() {
		$this->recover();
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$transferTo = $this->request->data['transfer_to'];
		$transferFrom = $id;
		// Require to update the parent id of the children before removing the node from the tree
		$this->updateAll(
				[
					'parent_id' => $transferTo,
					'lft' => null,
					'rght' => null
				],
				['parent_id' => $transferFrom]
			);

		$entity = $this->get($id);
		$left = $entity->lft;
		$right = $entity->rght;

		// The left and right value of the children will all have to be rebuilt
		$this->updateAll(
				[
					'lft' => null,
					'rght' => null
				],
				[
					'lft > ' => $left,
					'rght < ' => $right
				]
			);

		$this->rebuildLftRght();
	}

	public function onGetConvertOptions(Event $event, Entity $entity, Query $query) {
		$level = $entity->area_level_id;
		$query->where([
			$this->aliasField('area_level_id') => $level
		]);

		// if do not have any siblings but have child, can not be deleted
		if ($query->count() == 0 && $this->childCount($entity, true) > 0) {
			$this->Alert->warning('general.notTransferrable');
			$event->stopPropagation();
			return $this->controller->redirect($this->ControllerAction->url('index'));
		}
	}

	public function onGetAreasTableArrays()
	{
		$areasTableArray = [];

		$query = $this->find()
			->where([$this->aliasField('visible') => 1])
			->toArray()
			;

		foreach ($query as $key => $obj) {
			$areasTableArray[$obj->id] = [
				'id' => $obj->id,
				'parent_id' => $obj->parent_id,
				'code' => $obj->code,
				'name' => $obj->name,
				'area_level_id' => $obj->area_level_id,
				'order' => $obj->order
			];
		}

		return $areasTableArray;
	}

	public function onGetAreaCodeLists()
	{
		$areasTableArray = $this->onGetAreasTableArrays();
		$areaCodeLists = [];
		foreach ($areasTableArray as $key => $obj) {
			$areaCodeLists[$obj['code']] = $obj;
		}
		return $areaCodeLists;
	}

	public function onGetJsonArrays($url)
	{
		$obj = json_decode(file_get_contents($url), true);

		$objArray = $obj['areas'];

		$jsonArray = [];
		$orderArray = [];
		foreach ($objArray as $key => $obj) {
			// Null is the root parent id, as per cake update
			if ($obj['pnid'] === '-1') {
				$obj['pnid'] = null;
			}
			$level = $obj['lvl'];
			$orderArray[$level] = array_key_exists($level, $orderArray) ? ++$orderArray[$level] : 1;

			$jsonArray[$obj['nid']] = [
				'id' => $obj['nid'],
				'parent_id' => $obj['pnid'],
				'code' => $obj['id'],
				'name' => $obj['name'],
				'area_level_id' => $level,
				'order' => $orderArray[$level]
			];
		}
		return $jsonArray;
	}

	public function onGetJsonCodeLists($url)
	{
		$jsonArray = $this->onGetJsonArrays($url);
		$jsonCodeLists = [];
		foreach ($jsonArray as $key => $obj) {
			$jsonCodeLists[$obj['code']] = $obj;
		}
		return $jsonCodeLists;
	}

	public function onGetNewAreaLists($url)
	{
		$jsonArray = $this->onGetJsonArrays($url);
		$newAreaLists = [];
		foreach ($jsonArray as $key => $obj) {
			$newAreaLists[$obj['id']] = $obj['name'];
		}
		return $newAreaLists;
	}

	public function indexBeforeAction(Event $event) {
		// Add breadcrumb
		$toolbarElements = [
            ['name' => 'Area.breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$this->fields['parent_id']['visible'] = false;

		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
		if ($parentId != null) {
			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();
			$this->controller->set('crumbs', $crumbs);
		} else {
			$results = $this
				->find('all')
				->select([$this->aliasField('id')])
				->where([$this->aliasField('parent_id') . ' IS NULL'])
				->all();

			if ($results->count() == 1) {
				$parentId = $results
					->first()
					->id;

				$action = $this->ControllerAction->url('index');
				$action['parent'] = $parentId;
				return $this->controller->redirect($action);
			}
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
        if ($parentId != null) {
        	$query->where([$this->aliasField('parent_id') => $parentId]);
        } else {
        	$query->where([$this->aliasField('parent_id') . ' IS NULL']);
        }
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$this->_fieldOrder = ['area_level_id', 'code', 'name'];

		$this->fields['parent_id']['type'] = 'hidden';
		$parentId = $this->request->query('parent');

		if (is_null($parentId)) {
			$this->fields['parent_id']['attr']['value'] = null;
		} else {
			$this->fields['parent_id']['attr']['value'] = $parentId;

			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();

			$parentPath = '';
			foreach ($crumbs as $crumb) {
				$parentPath .= $crumb->name;
				$parentPath .= $crumb === end($crumbs) ? '' : ' > ';
			}

			$this->ControllerAction->field('parent', [
				'type' => 'readonly',
				'attr' => ['value' => $parentPath]
			]);

			//array_unshift($this->_fieldOrder, "parent");
		}
	}

	public function onGetName(Event $event, Entity $entity) {
		return $event->subject()->HtmlField->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);
	}

	public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : null;
		$results = $this
			->find()
			->select([$this->aliasField('area_level_id')])
			->where([$this->aliasField('id') => $parentId])
			->all();

		$attr['type'] = 'select';
		if (!$results->isEmpty()) {
			$data = $results->first();
			$areaLevelId = $data->area_level_id;

			$levelResults = $this->AreaLevels
				->find()
				->select([$this->AreaLevels->aliasField('level')])
				->where([$this->AreaLevels->aliasField('id') => $areaLevelId])
				->all();
			if (!$levelResults->isEmpty()) {
				$levelData = $levelResults->first();
				$level = $levelData->level;

				$levelOptions = $this->AreaLevels
					->find('list')
					->where([$this->AreaLevels->aliasField('level >') => $level])
					->toArray();
				$attr['options'] = $levelOptions;
			}
		}

		return $attr;
	}

	// autocomplete used for UserGroups
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);

		$list = $this
			->find()
			->contain('AreaLevels')
			->where([
				'OR' => [
					$this->aliasField('name') . ' LIKE' => $search,
					$this->aliasField('code') . ' LIKE' => $search,
					'AreaLevels.name LIKE' => $search
				]
			])
			->order(['AreaLevels.level', $this->aliasField('order')])
			->all();

		$data = array();
		foreach($list as $obj) {
			$data[] = [
				'label' => sprintf('%s - %s (%s)', $obj->area_level->name, $obj->name, $obj->code),
				'value' => $obj->id
			];
		}
		return $data;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		// will check the system config, if its set, edit & delete not available and add will be replaced by Sync button
		if ($action == 'index') {
			$toolbarButtons['edit'] = $buttons['edit'];
			$toolbarButtons['edit']['label'] = '<i class="fa fa-refresh"></i>';
			$toolbarButtons['edit']['type'] = 'button';
			$toolbarButtons['edit']['attr']['title'] = __('Synchronize');
			$toolbarButtons['edit']['url'][0] = 'sync';
			$toolbarButtons['edit']['attr'] = $attr;
		}

	}
}
