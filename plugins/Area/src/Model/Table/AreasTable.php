<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;

class AreasTable extends AppTable {
	private $_fieldOrder = ['visible', 'code', 'name', 'area_level_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Area.Areas']);
		$this->belongsTo('Levels', ['className' => 'Area.AreaLevels', 'foreignKey' => 'area_level_id']);
		$this->addBehavior('Tree');
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('area_level_id');

		$this->fields['lft']['visible'] = false;
		$this->fields['rght']['visible'] = false;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		// Add breadcrumb
		$toolbarElements = [
            ['name' => 'Area.breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$this->fields['parent_id']['visible'] = false;

		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : -1;
		if ($parentId != -1) {
			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();
			$this->controller->set('crumbs', $crumbs);
		} else {
			$results = $this
				->find('all')
				->select([$this->aliasField('id')])
				->where([$this->aliasField('parent_id') => -1])
				->all();

			if ($results->count() == 1) {
				$parentId = $results
					->first()
					->id;

				$action = $this->ControllerAction->buttons['index']['url'];
				$action['parent'] = $parentId;
				return $this->controller->redirect($action);
			}
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : -1;

		$options['conditions'][] = [
        	$this->aliasField('parent_id') => $parentId
        ];
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$this->_fieldOrder = ['area_level_id', 'code', 'name'];

		$this->fields['parent_id']['type'] = 'hidden';
		$parentId = $this->request->query('parent');

		if (is_null($parentId)) {
			$this->fields['parent_id']['attr']['value'] = -1;
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

			array_unshift($this->_fieldOrder, "parent");
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
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : -1;
		$results = $this
			->find()
			->select([$this->aliasField('area_level_id')])
			->where([$this->aliasField('id') => $parentId])
			->all();

		$attr['type'] = 'select';
		if (!$results->isEmpty()) {
			$data = $results->first();
			$areaLevelId = $data->area_level_id;

			$levelResults = $this->Levels
				->find()
				->select([$this->Levels->aliasField('level')])
				->where([$this->Levels->aliasField('id') => $areaLevelId])
				->all();

			if (!$levelResults->isEmpty()) {
				$levelData = $levelResults->first();
				$level = $levelData->level;

				$levelOptions = $this->Levels
					->find('list')
					->where([$this->Levels->aliasField('level >') => $level])
					->toArray();
				$attr['options'] = $levelOptions;
			}
		}

		return $attr;
	}
}
