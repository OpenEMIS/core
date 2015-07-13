<?php
namespace CustomField\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class CustomFieldsTable extends AppTable {
	private $_contain = ['CustomFieldOptions', 'CustomTableColumns', 'CustomTableRows'];
	protected $_fieldOrder = ['field_type', 'name', 'is_mandatory', 'is_unique'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFieldTypes', ['className' => 'CustomField.CustomFieldTypes', 'foreignKey' => 'field_type']);
		$this->hasMany('CustomFieldOptions', ['className' => 'CustomField.CustomFieldOptions', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'CustomField.CustomTableColumns', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'CustomField.CustomTableRows', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'CustomField.CustomForms',
			'joinTable' => 'custom_form_fields',
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

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['CustomFieldOptions']);
		$query->contain([
		    'CustomTableColumns' => function ($q) {
		       return $q
		       		->find('visible');
		    }
		]);
		$query->contain([
		    'CustomTableRows' => function ($q) {
		       return $q
		       		->find('visible');
		    }
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->loadBehavior($entity->field_type);
		return $entity;
	}

	public function addEditBeforeAction(Event $event) {
		//Setup fields
		list($fieldTypeOptions, , $mandatoryOptions, , $uniqueOptions) = array_values($this->getSelectOptions());

		$this->fields['field_type']['type'] = 'select';
		$this->fields['field_type']['options'] = $fieldTypeOptions;
		$this->fields['field_type']['onChangeReload'] = true;
		$this->fields['field_type']['labelKey'] = 'general';

		$this->fields['is_mandatory']['type'] = 'select';
		$this->fields['is_mandatory']['options'] = $mandatoryOptions;
		$this->fields['is_mandatory']['labelKey'] = 'general';

		$this->fields['is_unique']['type'] = 'select';
		$this->fields['is_unique']['options'] = $uniqueOptions;
		$this->fields['is_unique']['labelKey'] = 'general';

		$this->setFieldOrder();

		if ($this->request->is(['post', 'put'])) {
			$selectedFieldType = $this->request->data($this->aliasField('field_type'));
			$this->loadBehavior($selectedFieldType);
		}
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$fieldKey = Inflector::underscore(Inflector::singularize($this->alias())) . '_id';
		foreach ($this->_contain as $_contain) {
			// Should put here instead of in beforeSave()
			$this->{$_contain}->updateAll(
				['visible' => 0],
				[$fieldKey => $entity->id]
			);

			// To handle when delete all field_options
			if (!array_key_exists(Inflector::underscore($_contain), $data[$this->alias()])) {
				$data[$this->alias()][Inflector::underscore($_contain)] = [];
			}
		}

		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
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

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->loadBehavior($entity->field_type);
		return $entity;
	}

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
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}

	public function loadBehavior($selectedFieldType) {
		$this->fields['is_mandatory']['visible'] = $this->getMandatoryVisibility($selectedFieldType);
		$this->fields['is_unique']['visible'] = $this->getUniqueVisibility($selectedFieldType);
		$this->addBehavior(
			'CustomField.'.Inflector::camelize(strtolower($selectedFieldType)),
			['setup' => true]
		);
	}
}
