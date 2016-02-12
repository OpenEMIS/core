<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class InstitutionInfrastructuresTable extends AppTable {
	private $_fieldOrder = [
		'institution_id', 'parent_id', 'infrastructure_level_id', 'code', 'name', 'infrastructure_type_id', 'size', 'infrastructure_ownership_id', 'year_acquired', 'year_disposed', 'infrastructure_condition_id', 'comment'
	];

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Parents', ['className' => 'Institution.InstitutionInfrastructures']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		$this->belongsTo('Types', ['className' => 'Infrastructure.InfrastructureTypes', 'foreignKey' => 'infrastructure_type_id']);
		$this->belongsTo('InfrastructureOwnerships', ['className' => 'FieldOption.InfrastructureOwnerships']);
		$this->belongsTo('InfrastructureConditions', ['className' => 'FieldOption.InfrastructureConditions']);
		$this->hasMany('ChildInfrastructures', ['className' => 'Institution.InstitutionInfrastructures', 'foreignKey' => 'parent_id']);

		$this->addBehavior('Tree');
		$this->addBehavior('CustomField.Record', [
			'fieldKey' => 'infrastructure_custom_field_id',
			'tableColumnKey' => 'infrastructure_custom_table_column_id',
			'tableRowKey' => 'infrastructure_custom_table_row_id',
			'formKey' => 'infrastructure_custom_form_id',
			'filterKey' => 'infrastructure_custom_filter_id',
			'formFieldClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFields'],
			'formFilterClass' => ['className' => 'Infrastructure.InfrastructureCustomFormsFilters'],
			'recordKey' => 'institution_infrastructure_id',
			'fieldValueClass' => ['className' => 'Infrastructure.InfrastructureCustomFieldValues', 'foreignKey' => 'institution_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true],
			'tableCellClass' => ['className' => 'Infrastructure.InfrastructureCustomTableCells', 'foreignKey' => 'institution_infrastructure_id', 'dependent' => true, 'cascadeCallbacks' => true]
		]);
	}

	public function onGetParentId(Event $event, Entity $entity) {
		return $this->getParentPath($entity->parent_id);
	}

	public function onGetCode(Event $event, Entity $entity) {
		return $event->subject()->Html->link($entity->code, [
			'plugin' => $this->controller->plugin,
			'controller' => $this->controller->name,
			'action' => $this->alias,
			'index',
			'parent' => $entity->id
		]);
	}

	public function beforeAction(Event $event) {
		// recover
		$count = $this->find()->where([
				'OR' => [
					[$this->aliasField('lft').' IS NULL'],
					[$this->aliasField('rght').' IS NULL']
				]
			])
			->count();

		if ($count) {
			$this->recover();
		}
		// End

		// Add breadcrumb
		$toolbarElements = [
            ['name' => 'Institution.Infrastructure/breadcrumb', 'data' => [], 'options' => []]
        ];
		$this->controller->set('toolbarElements', $toolbarElements);

		$this->ControllerAction->field('parent_id');
		$this->ControllerAction->field('lft', ['visible' => false]);
		$this->ControllerAction->field('rght', ['visible' => false]);
		$this->ControllerAction->field('year_acquired');
		$this->ControllerAction->field('year_disposed');

		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function indexBeforeAction(Event $event) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$crumbs = $this
				->find('path', ['for' => $parentId])
				->order([$this->aliasField('lft')])
				->toArray();
			$this->controller->set('crumbs', $crumbs);
		}

		$this->fields['parent_id']['visible'] = false;
		$this->fields['infrastructure_level_id']['visible'] = false;
		$this->fields['size']['visible'] = false;
		$this->fields['infrastructure_ownership_id']['visible'] = false;
		$this->fields['year_acquired']['visible'] = false;
		$this->fields['year_disposed']['visible'] = false;
		$this->fields['infrastructure_condition_id']['visible'] = false;
		$this->fields['comment']['visible'] = false;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$parentId = $this->request->query('parent');
		if (!is_null($parentId)) {
			$query->where([$this->aliasField('parent_id') => $parentId]);
		} else {
			$query->where([$this->aliasField('parent_id IS NULL')]);
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('infrastructure_level_id');
		$this->ControllerAction->field('infrastructure_type_id');
		$this->ControllerAction->field('infrastructure_ownership_id', ['type' => 'select']);
		$this->ControllerAction->field('infrastructure_condition_id', ['type' => 'select']);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['level'] = $entity->infrastructure_level_id;
	}

	public function onGetConvertOptions(Event $event, Entity $entity, Query $query) {
		$query->where([
			$this->aliasField('institution_id') => $entity->institution_id,
			$this->aliasField('parent_id') => $entity->parent_id
		]);	
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$entity = $this->get($id);
		$transferTo = $this->request->data['transfer_to'];
		$transferFrom = $id;

		if (empty($transferTo) && $this->ControllerAction->hasAssociatedRecords($this, $entity)) {
			$event->stopPropagation();
			$this->Alert->error('general.deleteTransfer.restrictDelete');
			$url = $this->ControllerAction->url('remove');
			return $this->controller->redirect($url);
		} else {
			// Require to update the parent id of the children before removing the node from the tree
			$this->updateAll(
					[
						'parent_id' => $transferTo, 
						'lft' => null,
						'rght' => null
					],
					['parent_id' => $transferFrom]
				);

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

			$this->recover();

			$process = function($model, $id, $options) {
				$entity = $model->get($id);
				$model->removeFromTree($entity);
				return $model->delete($entity, $options->getArrayCopy());
			};

			return $process;
		}
	}

	public function onUpdateFieldParentId(Event $event, array $attr, $action, Request $request) {
		$parentId = $this->request->query('parent');
		
		if (is_null($parentId)) {
			$attr['type'] = 'hidden';
			$attr['value'] = null;
		} else {
			if ($action == 'add') {
				// $crumbs = $this
				// 	->find('path', ['for' => $parentId])
				// 	->order([$this->aliasField('lft')])
				// 	->toArray();

				// $parentPath = '';
				// foreach ($crumbs as $crumb) {
				// 	$parentPath .= $crumb->name;
				// 	$parentPath .= $crumb === end($crumbs) ? '' : ' > ';
				// }

				$attr['type'] = 'readonly';
				$attr['value'] = $parentId;
				// $attr['attr']['value'] = $parentPath;
				$attr['attr']['value'] = $this->getParentPath($parentId);
			} else if ($action == 'edit') {
				$session = $request->session();
				$institutionId = $session->read('Institution.Institutions.id');

				$grandParentId = $this->get($parentId)->parent_id;
				$where = [$this->Parents->aliasField('institution_id') => $institutionId];
				if (is_null($grandParentId)) {
					$where[] = $this->Parents->aliasField('parent_id IS NULL');
				} else {
					$where[$this->Parents->aliasField('parent_id')] = $grandParentId;
					$crumbs = $this
						->find('path', ['for' => $grandParentId])
						->order([$this->aliasField('lft')])
						->toArray();
					$this->controller->set('crumbs', $crumbs);
				}
				$parents = $this->Parents->find()->where($where)->all();

				$parentOptions = [];
				foreach ($parents as $key => $parent) {
					$parentOptions[$parent->id] = $parent->code . " - " . $parent->name;
				}
				$this->advancedSelectOptions($parentOptions, $parentId);

				$attr['type'] = 'select';
				$attr['options'] = $parentOptions;
			}
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureLevelId(Event $event, array $attr, $action, Request $request) {
		$parentId = $this->request->query('parent');
		$levelQuery = $this->Levels->find('list');
		if (is_null($parentId)) {
			$levelQuery->where([$this->Levels->aliasField('parent_id') => 0]);
		} else {
			$levelId = $this->get($parentId)->infrastructure_level_id;
			$levelQuery->where([$this->Levels->aliasField('parent_id') => $levelId]);
		}
		$levelOptions = $levelQuery->toArray();
		$selectedLevel = $this->queryString('level', $levelOptions);
		$this->advancedSelectOptions($levelOptions, $selectedLevel);

		$attr['options'] = $levelOptions;
		$attr['onChangeReload'] = 'changeLevel';

		$submit = isset($request->data['submit']) ? $request->data['submit'] : 'save';
		if ($submit != 'save') {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('custom_field_values', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_field_values']);
				}
				if (array_key_exists('custom_table_cells', $request->data[$this->alias()])) {
					unset($request->data[$this->alias()]['custom_table_cells']);
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldInfrastructureTypeId(Event $event, array $attr, $action, Request $request) {
		$selectedLevel = $request->query('level');
		$typeOptions = $this->Types
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->Types->aliasField('infrastructure_level_id') => $selectedLevel])
			->toArray();

		$attr['options'] = $typeOptions;
		return $attr;
	}

	public function onUpdateFieldYearAcquired(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getYearOptionsByConfig();
		return $attr;
	}

	public function onUpdateFieldYearDisposed(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->getYearOptionsByConfig();
		return $attr;
	}

	public function addEditOnChangeLevel(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['level']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('infrastructure_level_id', $request->data[$this->alias()])) {
					$request->query['level'] = $request->data[$this->alias()]['infrastructure_level_id'];
				}
			}
		}
	}

	public function getParentPath($parentId=null) {
		$crumbs = $this
			->find('path', ['for' => $parentId])
			->order([$this->aliasField('lft')])
			->toArray();

		$parentPath = '';
		foreach ($crumbs as $crumb) {
			$parentPath .= $crumb->name;
			$parentPath .= $crumb === end($crumbs) ? '' : ' > ';
		}

		return $parentPath;
	}

	public function getYearOptionsByConfig() {
		$ConfigItems = TableRegistry::get('ConfigItems');
		$lowestYear = $ConfigItems->value('lowest_year');
		$currentYear = date("Y");
		
		for($i=$currentYear; $i >= $lowestYear; $i--){
			$yearOptions[$i] = $i;
		}

		return $yearOptions;
	}
}
