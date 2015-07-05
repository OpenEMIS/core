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
		$this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
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
		$this->fields['parent_id']['visible'] = false;
		// Add breadcrumb
		$toolbarElements = [
            ['name' => 'Area.breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$parentId = !is_null($this->request->query('parent_id')) ? $this->request->query('parent_id') : -1;
		if ($parentId != -1) {
			$crumbs = $this
				->find('path', ['for' => $parentId])
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
				$action['parent_id'] = $parentId;
				return $this->controller->redirect($action);
			}
		}
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$parentId = !is_null($this->request->query('parent_id')) ? $this->request->query('parent_id') : -1;

		$options['conditions'][] = [
        	$this->aliasField('parent_id') => $parentId
        ];
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$this->fields['parent_id']['type'] = 'hidden';
		$parentId = $this->request->query('parent_id');

		if (is_null($parentId)) {
			$this->fields['parent_id']['attr']['value'] = -1;
		} else {
			$this->fields['parent_id']['attr']['value'] = $parentId;
			
			$crumbs = $this
				->find('path', ['for' => $parentId])
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

			$this->_fieldOrder = ['parent', 'area_level_id', 'code', 'name'];
		}
	}

	public function onGetName(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->name, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent_id' => $entity->id
		]);
	}

	public function onUpdateFieldAreaLevelId(Event $event, array $attr, $action, Request $request) {
		$parentId = !is_null($this->request->query('parent_id')) ? $this->request->query('parent_id') : -1;
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
}
