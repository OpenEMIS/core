<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class CustomFormsTable extends AppTable {
	use OptionsTrait;
	const APPLY_TO_ALL_YES = 1;
	const APPLY_TO_ALL_NO = 0;

	private $filterClass = [
		'className' => 'FieldOption.FieldOptionValues',
		'joinTable' => 'custom_forms_filters',
		'foreignKey' => 'custom_form_id',
		'targetForeignKey' => 'custom_filter_id',
		'through' => 'CustomField.CustomFormsFilters',
		'dependent' => true
	];

	public function initialize(array $config) {
		if (array_key_exists('custom_filter', $config)) {
			$this->filterClass = array_merge($this->filterClass, $config['custom_filter']);
		}
		
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFilters', $this->filterClass);
		$this->belongsToMany('CustomFields', [
			'className' => 'CustomField.CustomFields',
			'joinTable' => 'custom_forms_fields',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_field_id',
			'through' => 'CustomField.CustomFormsFields',
			'dependent' => true
		]);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->has('apply_to_all') && $entity->apply_to_all == self::APPLY_TO_ALL_YES) {
			$customFormIds = $this
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$this->aliasField('custom_module_id') => $entity->custom_module_id
				])
				->toArray();

			$CustomFormsFilters = TableRegistry::get($this->filterClass['through']);
			$CustomFormsFilters->deleteAll([
				'OR' => [
					[
						$CustomFormsFilters->aliasField($this->filterClass['foreignKey'] . ' IN') => $customFormIds,
						$CustomFormsFilters->aliasField($this->filterClass['targetForeignKey']) => 0
					],
					$CustomFormsFilters->aliasField($this->filterClass['foreignKey']) => $entity->id
				]
			]);

			$filterData = [
				$this->filterClass['foreignKey'] => $entity->id,
				$this->filterClass['targetForeignKey'] => 0
			];
			$filterEntity = $CustomFormsFilters->newEntity($filterData);

			if ($CustomFormsFilters->save($filterEntity)) {
			} else {
				$CustomFormsFilters->log($filterEntity->errors(), 'debug');
			}
		}
	}

	public function onGetApplyToAll(Event $event, Entity $entity) {
		$selectedApplyToAll = $this->getApplyToAll($entity);

		if (!is_null($selectedApplyToAll)) {
			$applyToAllOptions = $this->getSelectOptions('general.yesno');
			return $applyToAllOptions[$selectedApplyToAll];
		} else {
			return '<i class="fa fa-minus"></i>';
		}
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupFields();
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$moduleQuery = $this->getModuleQuery();
		$moduleOptions = $moduleQuery->toArray();

		if (!empty($moduleOptions)) {
			$selectedModule = $this->queryString('module', $moduleOptions);
			$this->advancedSelectOptions($moduleOptions, $selectedModule);

			$query->where([$this->aliasField('custom_module_id') => $selectedModule]);

			//Add controls filter to index page
			$toolbarElements = [
	            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
	        ];
	        $this->controller->set(compact('toolbarElements', 'moduleOptions'));
		}

        $query->contain(['CustomFilters', 'CustomFields']);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['CustomFilters', 'CustomFields']);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->query['module'] = $entity->custom_module_id;
		$this->request->query['apply_all'] = $this->getApplyToAll($entity);

		$this->setupFields($entity);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['module'] = $entity->custom_module_id;
		$this->request->query['apply_all'] = $this->getApplyToAll($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request) {
		$moduleQuery = $this->getModuleQuery();
		$moduleOptions = $moduleQuery->toArray();
		$selectedModule = $this->queryString('module', $moduleOptions);
		$this->advancedSelectOptions($moduleOptions, $selectedModule);

		$attr['type'] = 'select';
		$attr['options'] = $moduleOptions;
		$attr['onChangeReload'] = 'changeModule';

		return $attr;
	}

	public function onUpdateFieldApplyToAll(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			// default hide
			$attr['visible'] = false;
			$attr['type'] = 'hidden';
			$attr['value'] = 0;

			$selectedModule = $request->query('module');
			$filterAlias = $this->getFilterAlias($selectedModule);

			if (!empty($filterAlias)) {
				$applyToAllOptions = $this->getSelectOptions('general.yesno');
				$selectedApplyToAll = $this->queryString('apply_all', $applyToAllOptions);
				$this->advancedSelectOptions($applyToAllOptions, $selectedApplyToAll);

				// show selection if the module has filter
				$attr['visible'] = true;
				$attr['type'] = 'select';
				$attr['options'] = $applyToAllOptions;
				$attr['onChangeReload'] = 'changeApplyAll';
			}
		}

		return $attr;
	}

	public function onUpdateFieldCustomFilters(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			// default hide
			$attr['visible'] = false;
			$attr['type'] = 'hidden';
			$attr['value'] = 0;

			$selectedModule = $request->query('module');
			$filterAlias = $this->getFilterAlias($selectedModule);

			if (!empty($filterAlias)) {
				$selectedApplyToAll = $request->query('apply_all');

				if ($selectedApplyToAll == self::APPLY_TO_ALL_NO) {
					list($plugin, $modelAlias) = explode('.', $filterAlias, 2);
					$labelText = Inflector::underscore(Inflector::singularize($modelAlias));
					$filterOptions = TableRegistry::get($filterAlias)->getList()->toArray();

					// show selection if the module has filter and not apply to all
					$attr['visible'] = true;
					$attr['type'] = 'chosenSelect';
					$attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
					$attr['options'] = $filterOptions;
					$attr['attr']['label'] = __(Inflector::humanize($labelText));
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldCustomFields(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$customFieldOptions = $this->CustomFields
				->find('list')
				->toArray();

			$attr['options'] = $customFieldOptions;
		}

		return $attr;
	}

	public function addEditOnChangeModule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['module']);
		unset($request->query['apply_all']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('custom_module_id', $request->data[$this->alias()])) {
					$this->request->query['module'] = $request->data[$this->alias()]['custom_module_id'];
				}
			}
		}
	}

	public function addEditOnChangeApplyAll(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['apply_all']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('apply_to_all', $request->data[$this->alias()])) {
					$this->request->query['apply_all'] = $request->data[$this->alias()]['apply_to_all'];
				}
			}
		}
	}

	public function getModuleQuery() {
		return $this->CustomModules
			->find('list')
			->find('visible');
	}

	private function setupFields(Entity $entity=null) {
		$selectedModule = $this->request->query('module');
		$filterAlias = $this->getFilterAlias($selectedModule);

		$fieldOrder = [];
		$this->ControllerAction->field('custom_module_id');
		$fieldOrder[] = 'custom_module_id';

		if (!empty($filterAlias)) {
			$this->ControllerAction->field('apply_to_all');
			$this->ControllerAction->field('custom_filters', [
				'type' => 'chosenSelect',
				'placeholder' => __('Select Filters')
			]);

			$fieldOrder[] = 'apply_to_all';
			$fieldOrder[] = 'custom_filters';
		}

		$this->ControllerAction->field('custom_fields', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Fields')
		]);
		$fieldOrder[] = 'name';
		$fieldOrder[] = 'description';
		$fieldOrder[] = 'custom_fields';

		$this->ControllerAction->setFieldOrder($fieldOrder);
	}

	private function getFilterAlias($selectedModule=null) {
		if (!is_null($selectedModule)) {
			$customModule = $this->CustomModules->get($selectedModule);
			return $customModule->filter;
		}

		return null;
	}

	private function getApplyToAll(Entity $entity) {
		$filterAlias = $this->getFilterAlias($entity->custom_module_id);

		if (!empty($filterAlias)) {
			$CustomFormsFilters = TableRegistry::get($this->filterClass['through']);
			$results = $CustomFormsFilters
				->find()
				->where([
					$CustomFormsFilters->aliasField($this->filterClass['foreignKey']) => $entity->id,
					$CustomFormsFilters->aliasField($this->filterClass['targetForeignKey']) => 0
				])
				->all();

			if ($results->isEmpty()) {
				return self::APPLY_TO_ALL_NO;
			} else {
				return self::APPLY_TO_ALL_YES;
			}
		}

		return null;
	}
}
