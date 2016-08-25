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
		$model = $this;
		$request = $this->request;
		$entity = $model->newEntity();
		$extra=[];

		// Temporary hardcoded the source of the json.
		// will be set on the sys_config later.
		// $url = 'http://devinfo-cloud.com/reporting/scinfo_data_admin/api/area';
		$url = 'https://demo.openemis.org/datamanager/api/area';
		$obj = json_decode(file_get_contents($url), true);

		$objArray = $obj['areas'];
		$query = $this->find()
			->where([$this->aliasField('visible') => 1])
			->toArray()
			;

		// Array from areas table (target)
		$areasTableArray = [];
		foreach ($query as $key => $value) {
			$areasTableArray[$key] = [
				'id' => $value->id,
				'parent_id' => $value->parent_id,
				'code' => $value->code,
				'name' => $value->name,
				'area_level_id' => $value->area_level_id
			];
		}
		// pr($areasTableArray);die;

		// Array from json (source)
		$jsonArray = [];
		$orderArray = [];
		foreach ($objArray as $key => $value) {
			// Null is the root parent id, as per cake update
			if ($value['pnid'] === '-1') {
				$value['pnid'] = null;
			}
			$level = $value['lvl'];
			$orderArray[$level] = array_key_exists($level, $orderArray) ? ++$orderArray[$level] : 1;

			$jsonArray[$key] = [
				'id' => $value['nid'],
				'parent_id' => $value['pnid'],
				'code' => $value['id'],
				'name' => $value['name'],
				'area_level_id' => $level,
				'order' => $orderArray[$level]
			];
		}

		foreach ($this->fields as $field => $attr) {
			$this->fields[$field]['visible'] = false;
		}

		$this->ControllerAction->field('data_will_be_synced_from', [
			'type' => 'readonly',
			'attr' => ['value' => $url]
		]);

		// Target empty, Source will update the target.
		if(empty($areasTableArray)){
			if ($this->request->is(['get'])) {
				$this->ControllerAction->field('local_server', [
					'type' => 'element',
					'element' => 'Area.Areas/local_server'
				]);

				$this->controller->set('areasTableArray', $areasTableArray);


			} else if ($this->request->is(['post', 'put'])) {
				$submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
				if ($submit == 'save') {
					$areaTable = TableRegistry::get('areas');

					foreach ($jsonArray as $key => $value) {
						$areasArray = $areaTable->newEntity([
							'id' => $value['id'],
							'parent_id' => $value['parent_id'],
							'code' => $value['code'],
							'name' => $value['name'],
							'area_level_id' => $value['area_level_id'],
							'order' => $value['order']
						]);
						$areaTable->save($areasArray);
					}
					$this->rebuildLftRght();

					$url = $this->ControllerAction->url('index');
					unset($url['section']);

					return $this->controller->redirect($url);
				} else {
					pr('reload');
				}
			}
		}

		// Target < source, source will append the target.
		if(!empty($areasTableArray)){
		// 	if ($this->request->is(['get'])) {
		// 		// pr('get');
		// 		// pr($this->request);

			$this->ControllerAction->field('local_server', [
				'type' => 'element',
				'element' => 'Area.Areas/local_server'
			]);

			$this->ControllerAction->field('remote_server', [
				'type' => 'element',
				'element' => 'Area.Areas/remote_server'
			]);

			$this->controller->set('areasTableArray', $areasTableArray);
			$this->controller->set('jsonArray', $jsonArray);

		// 		if ($this->hasAssociatedRecords($model, $entity, $extra)) {
		// 			pr('hasAssociatedRecords');
		// 		}

			foreach ($jsonArray as $key => $value) {
				pr($value['code']);
				$jsonCode = $value['code'];
				foreach ($areasTableArray as $key => $value) {
					pr($value['code']);
					$tableCode = $value['code'];

				}

			}


		// 	} else if ($this->request->is(['post', 'put'])) {
		// 		// pr($this->request);die;
		// 		$submit = isset($this->request->data['submit']) ? $this->request->data['submit'] : 'save';
		// 		if ($submit == 'save') {
		// 			// pr('save');
		// 			$areaTable = TableRegistry::get('areas');

		// 			foreach ($jsonArray as $key => $value) {
		// 				$article = $areaTable->newEntity([
		// 					'id' => $value['id'],
		// 					'parent_id' => $value['parent_id'],
		// 					'code' => $value['code'],
		// 					'name' => $value['name'],
		// 					'area_level_id' => $value['area_level_id']
		// 				]);
		// 				$this->rebuildLftRght();

		// 				$areaTable->save($article);
		// 			}
		// 			$this->rebuildLftRght();
		// 		} else {
		// 			pr('reload');
		// 		}
		// 	}
		}

		// target > source, some data already deleted, need to show the page of affected area and security group.




		//


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
