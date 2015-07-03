<?php
namespace CustomField\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class CustomFormsTable extends AppTable {
	private $_contain = ['FieldOptionValues', 'CustomFields'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->hasMany('CustomFormFilters', ['className' => 'CustomField.CustomFormFilters', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomFormFields', ['className' => 'CustomField.CustomFormFields', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('FieldOptionValues', [
			'className' => 'FieldOptionValues',
			'joinTable' => 'custom_form_filters',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_filter_id'
		]);
		$this->belongsToMany('CustomFields', [
			'className' => 'CustomField.CustomFields',
			'joinTable' => 'custom_form_fields',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_field_id'
		]);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (isset($entity->apply_to_all) && $entity->apply_to_all == 1) {
			$where = [
				$this->aliasField('custom_module_id') => $entity->custom_module_id,
				$this->aliasField('id !=') => $entity->id
			];

			$customFormIds = $this->find('list', ['keyField' => 'id', 'valueField' => 'id'])->where($where)->toArray();
			$this->CustomFormFilters->deleteAll([
				$this->CustomFormFilters->aliasField('custom_form_id IN') => $customFormIds,
				$this->CustomFormFilters->aliasField('custom_filter_id') => 0
			]);

			$CustomFormFiltersTable = $this->CustomFormFilters;
			$CustomFormFilter = $CustomFormFiltersTable->newEntity();
			$CustomFormFilter->custom_form_id = $entity->id;
			$CustomFormFilter->custom_filter_id = 0;
			if ($CustomFormFiltersTable->save($CustomFormFilter)) {
			} else {
				$this->CustomFormFilters->log($CustomFormFilter->errors(), 'debug');
			}
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('apply_to_all', ['type' => 'select']);
		$this->ControllerAction->field('custom_fields', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Fields')
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($moduleOptions, $selectedModule) = array_values($this->getSelectOptions());
        $this->controller->set(compact('moduleOptions', 'selectedModule'));

		$options['conditions'][] = [
        	$this->aliasField('custom_module_id') => $selectedModule
        ];
        $options['contain'] = array_merge($options['contain'], $this->_contain);
	}

	public function indexAfterAction(Event $event, $data) {
		list(, $selectedModule) = array_values($this->getSelectOptions());
		$filter = $this->CustomModules->findById($selectedModule)->first()->filter;

		if (is_null($filter)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;
		}

		return $data;
	}

	public function viewBeforeAction(Event $event) {
		$this->setFieldOrder();
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$selectedModule = $entity->custom_module_id;
		$filter = $this->CustomModules->findById($selectedModule)->first()->filter;

		if (is_null($filter)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;
		}

		return $entity;
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($moduleOptions, , $applyToAllOptions) = array_values($this->getSelectOptions());

		$this->fields['custom_module_id']['options'] = $moduleOptions;
		$this->fields['custom_module_id']['onChangeReload'] = true;

		$this->fields['apply_to_all']['options'] = $applyToAllOptions;
		$this->fields['apply_to_all']['attr'] = [
			'onchange' => 'if(this.value == 1){$("#customforms-field-option-values-ids").val("").trigger("chosen:updated");};'
		];

		$customFieldOptions = $this->CustomFields->find('list')->toArray();
		$this->fields['custom_fields']['options'] = $customFieldOptions;

		$this->setFieldOrder();
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$selectedModule = $entity->custom_module_id;
		$filter = $this->CustomModules->findById($selectedModule)->first()->filter;

		if (is_null($filter)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;

			$modelAlias = $this->ControllerAction->getModel($filter)['model'];
			$labelText = Inflector::underscore(Inflector::singularize($modelAlias));

			$filterOptionTable = TableRegistry::get($filter);
			$filterOptions = $filterOptionTable->getList()->toArray();

			$this->ControllerAction->addField($labelText, [
				'type' => 'chosenSelect',
				'fieldNameKey' => 'field_option_values',
				'fieldName' => $this->alias() . '.field_option_values._ids',
				'placeholder' => __('Select ') . __(Inflector::humanize($labelText)),
				'options' => $filterOptions,
				'order' => 3,
				'visible' => true,
				'attr' => [
					'label' => __(Inflector::humanize($labelText)),
					'onchange' => 'if($(this).val()){$("#customforms-apply-to-all").val(0);};'
				]
			]);

			$order = 2;
			$this->ControllerAction->setFieldOrder('apply_to_all', $order++);
			$this->ControllerAction->setFieldOrder($labelText, $order++);
		}

		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedModule, , $selectedApplyToAll) = array_values($this->getSelectOptions());
		$entity->custom_module_id = $selectedModule;
		$entity->apply_to_all = $selectedApplyToAll;

		return $entity;
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, , , $selectedApplyToAll) = array_values($this->getSelectOptions());

		$results = $this->CustomFormFilters
			->find('all')
			->where([
				$this->CustomFormFilters->aliasField('custom_form_id') => $entity->id,
				$this->CustomFormFilters->aliasField('custom_filter_id') => 0		
			]);

		if (!$results->isEmpty()) {
			$selectedApplyToAll = 1;
		}
		$entity->apply_to_all = $selectedApplyToAll;

		return $entity;
	}

    public function onGetApplyToAll(Event $event, Entity $entity) {
    	$list = [];
		if (!empty($entity->field_option_values)) {
			foreach ($entity->field_option_values as $obj) {
				$list[] = $obj->name;
			}
		} else {
			$results = $this->CustomFormFilters
				->find('all')
				->where([
					$this->CustomFormFilters->aliasField('custom_form_id') => $entity->id,
					$this->CustomFormFilters->aliasField('custom_filter_id') => 0
				]);

			if (!$results->isEmpty()) {
				$list[] = __('Apply To All');
			}
		}

        return implode(', ', $list);
    }

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$moduleOptions = $this->CustomModules
			->find('list')
			->find('visible')
			->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		$applyToAllOptions = [0 => __('No'), 1 => __('Yes')];
		$selectedApplyToAll = key($applyToAllOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}

	public function setFieldOrder() {
		$this->ControllerAction->setFieldOrder([
			'custom_module_id', 'apply_to_all', 'name', 'custom_fields'
		]);
	}
}
