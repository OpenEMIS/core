<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class CustomFieldsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'CustomField.CustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'CustomField.CustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'CustomField.CustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'CustomField.CustomForms',
			'joinTable' => 'custom_field_forms',
			'foreignKey' => 'custom_field_id',
			'targetForeignKey' => 'custom_form_id'
		]);

		$this->addBehavior('CustomField.FieldType');
		$this->addBehavior('CustomField.Mandatory');
		$this->addBehavior('CustomField.Unique');
	}

	public function viewBeforeAction(Event $event) {
		$this->setFieldOrder();
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($fieldTypeOptions, , $mandatoryOptions, , $uniqueOptions) = array_values($this->getSelectOptions());

		$this->fields['field_type']['type'] = 'select';
		$this->fields['field_type']['options'] = $fieldTypeOptions;
		$this->fields['field_type']['onChangeReload'] = true;

		$this->fields['is_mandatory']['type'] = 'select';
		$this->fields['is_mandatory']['options'] = $mandatoryOptions;

		$this->fields['is_unique']['type'] = 'select';
		$this->fields['is_unique']['options'] = $uniqueOptions;

		$this->setFieldOrder();
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->fields['is_mandatory']['visible'] = $this->getMandatoryVisibility($entity->field_type);
		$this->fields['is_unique']['visible'] = $this->getUniqueVisibility($entity->field_type);

		return $entity;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		//Initialize field values
		list(, $selectedFieldType, , $selectedMandatory, , $selectedUnique) = array_values($this->getSelectOptions());
		$entity->field_type = $selectedFieldType;
		$entity->is_mandatory = $selectedMandatory;
		$entity->is_unique = $selectedUnique;
		$this->loadBehavior($selectedFieldType);

		return $entity;
	}

	public function addEditOnReload(Event $event, Entity $entity, array $data, array $options) {
		$selectedFieldType = $data[$this->alias()]['field_type'];
		$this->loadBehavior($selectedFieldType);

		return compact('entity', 'data', 'options');
	}

	/*
	public function addEditOnAddOption(Event $event, Entity $entity, array $data, array $options) {
		//pr('addEditOnAddOption');die;
		return compact('entity', 'data', 'options');
	}
	*/

	public function getSelectOptions() {
		//Return all required options and their key
		$fieldTypeOptions = $this->getFieldTypeList();
        $selectedFieldType = key($fieldTypeOptions);

        $mandatoryOptions = $this->getMandatoryList();
        $selectedMandatory = key($mandatoryOptions);

        $uniqueOptions = $this->getUniqueList();
        $selectedUnique = key($uniqueOptions);

		return compact('fieldTypeOptions', 'selectedFieldType', 'mandatoryOptions', 'selectedMandatory', 'uniqueOptions', 'selectedUnique');
	}

	public function setFieldOrder() {
		$order = 1;
		$this->ControllerAction->setFieldOrder('field_type', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('is_mandatory', $order++);
		$this->ControllerAction->setFieldOrder('is_unique', $order++);
	}

	public function loadBehavior($selectedFieldType) {
		$this->addBehavior('CustomField.'.Inflector::camelize(strtolower($selectedFieldType)), [
			'events' => [
				'ControllerAction.Model.addEdit.onReload' => 'addEditOnAddOption'
			]
		]);
	}
}
