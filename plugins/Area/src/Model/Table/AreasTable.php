<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
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

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$query->where([
			$this->aliasField('area_level_id') => $entity->area_level_id
		]);

		if ($query->count() == 1) {
			if ((($entity->rght) - ($entity->lft)) != 1) {
				$this->Alert->warning('general.notTransferrable');
				$event->stopPropagation();
				return $this->controller->redirect($this->ControllerAction->url('index'));
			} 
		}
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
		return $event->subject()->Html->link($entity->name, [
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
}
