<?php
namespace CustomField\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class CustomFormsTable extends AppTable {
	private $_contain = ['FieldOptionValues', 'CustomFields'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->hasMany('CustomFormTypes', ['className' => 'CustomField.CustomFormTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('FieldOptionValues', [
			'className' => 'FieldOptionValues',
			'joinTable' => 'custom_form_types',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_type_id'
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
			$this->CustomFormTypes->deleteAll([
				$this->CustomFormTypes->aliasField('custom_form_id IN') => $customFormIds,
				$this->CustomFormTypes->aliasField('custom_type_id') => 0
			]);

			$CustomFormTypesTable = $this->CustomFormTypes;
			$CustomFormType = $CustomFormTypesTable->newEntity();
			$CustomFormType->custom_form_id = $entity->id;
			$CustomFormType->custom_type_id = 0;
			if ($CustomFormTypesTable->save($CustomFormType)) {
			} else {
				$this->CustomFormTypes->log($CustomFormType->errors(), 'debug');
			}
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->addField('apply_to_all', [
			'type' => 'select',
			'order' => 2,
			'visible' => true
		]);

		$this->ControllerAction->addField('custom_fields', [
			'type' => 'chosen_select',
			'fieldNameKey' => 'custom_fields',
			'fieldName' => $this->alias() . '.custom_fields._ids',
			'placeholder' => __('Select Fields'),
			'order' => 5,
			'visible' => true
		]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Table $model, array $options) {
		list($moduleOptions, $selectedModule) = array_values($this->getSelectOptions());

        $this->controller->set(compact('moduleOptions', 'selectedModule'));
		$options['conditions'][] = [
        	$model->aliasField('custom_module_id') => $selectedModule
        ];
        $options['contain'] = array_merge($options['contain'], $this->_contain);

		return $options;
	}

	public function viewBeforeAction(Event $event) {
		$this->setFieldOrder();
	}

	public function viewBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$selectedModule = $entity->custom_module_id;
		$fieldOption = $this->CustomModules->findById($selectedModule)->first()->field_option;

		if (is_null($fieldOption)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;
		}

		return $entity;
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($moduleOptions, , $applyToAllOptions) = array_values($this->getSelectOptions());

		$this->fields['custom_module_id']['type'] = 'select';
		$this->fields['custom_module_id']['options'] = $moduleOptions;
		$this->fields['custom_module_id']['onChangeReload'] = true;

		$this->fields['apply_to_all']['options'] = $applyToAllOptions;
		$this->fields['apply_to_all']['attr'] = [
			'onchange' => 'if(this.value == 1){$("#customforms-field-option-values-ids").val("").trigger("chosen:updated");};'
		];

		$fieldOptions = $this->CustomFields->find('list')->toArray();
		$this->fields['custom_fields']['options'] = $fieldOptions;

		$this->setFieldOrder();
	}

	public function addEditBeforePatch(Event $event, Entity $entity, array $data, array $options) {
		//Required by patchEntity for associated data
		$options['associated'] = $this->_contain;
		return compact('entity', 'data', 'options');
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$selectedModule = $entity->custom_module_id;
		$fieldOption = $this->CustomModules->findById($selectedModule)->first()->field_option;

		if (is_null($fieldOption)) {
			$this->fields['apply_to_all']['visible'] = false;
		} else {
			$this->fields['apply_to_all']['visible'] = true;

			$modelAlias = $this->ControllerAction->getModel($fieldOption)['model'];
			$labelText = Inflector::underscore(Inflector::singularize($modelAlias));

			$fieldOptionTable = TableRegistry::get($fieldOption);
			$fieldOptions = $fieldOptionTable->getList()->toArray();

			$this->ControllerAction->addField($labelText, [
				'type' => 'chosen_select',
				'fieldNameKey' => 'field_option_values',
				'fieldName' => $this->alias() . '.field_option_values._ids',
				'placeholder' => __('Select ') . __(Inflector::humanize($labelText)),
				'options' => $fieldOptions,
				'order' => 3,
				'visible' => true,
				'attr' => [
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
		list(, $selectedModule) = array_values($this->getSelectOptions());
		$entity->custom_module_id = $selectedModule;
		return $entity;
	}

	public function editBeforeQuery(Event $event, Query $query, array $contain) {
		//Retrieve associated data
		$contain = array_merge($contain, $this->_contain);
		return compact('query', 'contain');
	}

    public function onGetApplyToAll(Event $event, Entity $entity) {
    	$list = [];
		if (!empty($entity->field_option_values)) {
			foreach ($entity->field_option_values as $obj) {
				$list[] = $obj->name;
			}
		} else {
			$where = [
				$this->CustomFormTypes->aliasField('custom_form_id') => $entity->id,
				$this->CustomFormTypes->aliasField('custom_type_id') => 0
			];
			$result = $this->CustomFormTypes->find('all')->where($where)->toArray();
			if (!empty($result)) {
				$list[] = __('Apply To All');
			}
		}

        return implode(', ', $list);
    }

	public function getSelectOptions() {
		//Return all required options and their key
		$query = $this->request->query;

		$moduleOptions = $this->CustomModules->find('list')->toArray();
		$selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

		$applyToAllOptions = [0 => __('No'), 1 => __('Yes')];
		$selectedApplyToAll = key($applyToAllOptions);

		return compact('moduleOptions', 'selectedModule', 'applyToAllOptions', 'selectedApplyToAll');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('custom_module_id', $order++);
		$this->ControllerAction->setFieldOrder('apply_to_all', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('custom_fields', $order++);
	}
}
