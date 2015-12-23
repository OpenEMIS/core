<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class AreaAdministrativesTable extends AppTable {
	private $_fieldOrder = ['visible', 'code', 'name', 'area_administrative_level_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Area.AreaAdministratives']);
		$this->belongsTo('Levels', ['className' => 'Area.AreaAdministrativeLevels', 'foreignKey' => 'area_administrative_level_id']);
		$this->addBehavior('Tree');
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'parent_id',
			]);
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('area_administrative_level_id');
		$this->ControllerAction->field('is_main_country', ['visible' => false]);
		$this->ControllerAction->field('name');
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
		$this->updateAll(
			['parent_id' => null],
			['parent_id' => -1]
		);
		$this->recover();
		$this->updateAll(
			['parent_id' => -1],
			['parent_id IS NULL']
		);
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
			$crumbs = $this->prepareCrumbs($crumbs);

			$this->controller->set('crumbs', $crumbs);
		} else {
			// Always redirect by selecting World as the parent
			$results = $this
				->find()
				->select([$this->aliasField('id')])
				->where([$this->aliasField('parent_id') => -1])
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

	public function editAfterAction(Event $event, Entity $entity) {
		$this->request->data[$this->alias()]['area_administrative_level_id'] = $entity->area_administrative_level_id;
		$this->ControllerAction->field('is_main_country');
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('is_main_country');
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : -1;
        $query->where([$this->aliasField('parent_id') => $parentId]);
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		$this->_fieldOrder = ['area_administrative_level_id', 'code', 'name'];

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
			$crumbs = $this->prepareCrumbs($crumbs);

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

	public function onUpdateFieldIsMainCountry(Event $event, array $attr, $action, Request $request) {
		if ($action=='add') {
			$attr['visible'] = true;
			$areaAdministrativeLevelId = $request->data[$this->alias()]['area_administrative_level_id'];
			if ($areaAdministrativeLevelId == 1) {
				$attr['options'] = $this->getSelectOptions('general.yesno');
				return $attr;
			} else {
				$attr['value'] = 0;
				$attr['type'] = 'hidden';
				return $attr;
			}
		} elseif ($action == 'edit') {
			$attr['visible'] = true;
			$areaAdministrativeLevelId = $request->data[$this->alias()]['area_administrative_level_id'];
			if ($areaAdministrativeLevelId == 1) {
				$attr['options'] = $this->getSelectOptions('general.yesno');
				return $attr;
			} else {
				$attr['value'] = 0;
				$attr['type'] = 'hidden';
				return $attr;
			}
		}
	}

	public function onUpdateFieldAreaAdministrativeLevelId(Event $event, array $attr, $action, Request $request) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : -1;
		$results = $this
			->find()
			->select([
				$this->aliasField('parent_id'),
				$this->aliasField('area_administrative_level_id')
			])
			->where([$this->aliasField('id') => $parentId])
			->all();

		$attr['type'] = 'select';
		if (!$results->isEmpty()) {
			$data = $results
				->first();
			// $parentId = $data->parent_id;
			$levelId = $data->area_administrative_level_id;

			if ($data->parent_id == -1) {	//World
				$levelOptions = $this->Levels
					->find('list')
					->where([$this->Levels->aliasField('level') => 0])
					->toArray();

				$attr['options'] = $levelOptions;
			} else {
				// Filter levelOptions by Country
				$levelResults = $this->Levels
					->find()
					->select([
						$this->Levels->aliasField('level'),
						$this->Levels->aliasField('area_administrative_id')
					])
					->where([$this->Levels->aliasField('id') => $levelId])
					->all();

				if (!$levelResults->isEmpty()) {
					$level = $levelResults
						->first()
						->level;
					$countryId = $levelResults
						->first()
						->area_administrative_id;
					$countryId = $level < 1 ? $parentId : $countryId;	//-1 => World, 0 => Country

					$levelOptions = $this->Levels
						->find('list')
						->where([
							$this->Levels->aliasField('area_administrative_id') => $countryId,
							$this->Levels->aliasField('level >') => $level
						])
						->toArray();

					$attr['options'] = $levelOptions;
				}
			}
			if (!isset($request->data[$this->alias()]['area_administrative_level_id'])) {
				$request->data[$this->alias()]['area_administrative_level_id'] = key($attr['options']);
			}
		}

		return $attr;
	}

	public function onUpdateFieldName(Event $event, array $attr, $action, Request $request) {
		$parentId = !is_null($this->request->query('parent')) ? $this->request->query('parent') : -1;
		$results = $this
			->find()
			->select([$this->aliasField('parent_id'), $this->aliasField('area_administrative_level_id')])
			->where([$this->aliasField('id') => $parentId])
			->all();

		if (!$results->isEmpty()) {
			$data = $results
				->first();
			$parentId = $data->parent_id;

			if ($parentId == -1) {	//World
				$Countries = TableRegistry::get('FieldOption.Countries');
				$countryOptions = $Countries
					->find('list', ['keyField' => 'name', 'valueField' => 'name'])
					->find('visible')
					->find('order')
					->toArray();

				$attr['type'] = 'select';
				$attr['options'] = $countryOptions;
			}
		}

		return $attr;
	}

	public function prepareCrumbs(array $crumbs) {
		// Replace the code and name of World with All
		foreach ($crumbs as $key => $crumb) {
			if ($crumb->parent_id == -1) {
				$crumb->code = __('All');
				$crumb->name = __('All');
				$crumbs[$key] = $crumb;
				break;
			}
		}

		return $crumbs;
	}
}
