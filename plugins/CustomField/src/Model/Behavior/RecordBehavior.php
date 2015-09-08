<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;

class RecordBehavior extends Behavior {
	protected $_contain = ['CustomFieldValues', 'CustomTableCells'];
	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.view.afterAction' 		=> 'viewAfterAction',
			'ControllerAction.Model.addEdit.beforePatch' 	=> 'addEditBeforePatch',
			'ControllerAction.Model.addEdit.afterAction' 	=> 'addEditAfterAction',
			'ControllerAction.Model.edit.afterSave' 		=> 'editAfterSave'
		],
		'model' => null,
		'behavior' => null,
		'moduleKey' => 'custom_module_id',
		'fieldKey' => 'custom_field_id',
		'fieldOptionKey' => 'custom_field_option_id',
		'tableColumnKey' => 'custom_table_column_id',
		'tableRowKey' => 'custom_table_row_id',
		'formKey' => 'custom_form_id',
		'filterKey' => 'custom_filter_id',
		'formFieldClass' => ['className' => 'CustomField.CustomFormsFields'],
		'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
		'recordKey' => 'custom_record_id',
		'fieldValueClass' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
		'tableCellClass' => ['className' => 'CustomField.CustomTableCells', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true]
	];

	private $CustomFieldValues = null;
	private $CustomTableCells = null;

	private $CustomModules = null;
	private $CustomFieldTypes = null;

	private $CustomFields = null;
	private $CustomForms = null;
	private $CustomFormsFields = null;
	private $CustomFormsFilters = null;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->_table->hasMany('CustomFieldValues', $this->config('fieldValueClass'));
		$this->_table->hasMany('CustomTableCells', $this->config('tableCellClass'));

		$this->CustomFieldValues = $this->_table->CustomFieldValues;
		$this->CustomTableCells = $this->_table->CustomTableCells;

		$this->CustomModules = TableRegistry::get('CustomField.CustomModules');
		$this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');

		$this->CustomFields = $this->CustomFieldValues->CustomFields;
		$this->CustomForms = $this->CustomFields->CustomForms;
		$this->CustomFormsFields = TableRegistry::get($this->config('formFieldClass.className'));
		$this->CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));

		$model = $this->config('model');
		if (empty($model)) {
			$this->config('model', $this->_table->registryAlias());
		}
    }

    public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	// Checking : skip insert if value is empty
    	if (array_key_exists('custom_field_values', $data[$this->_table->alias()]) || array_key_exists('custom_table_cells', $data[$this->_table->alias()])) {
			$fieldTypes = $this->CustomFieldTypes
				->find('list', ['keyField' => 'code', 'valueField' => 'value'])
				->toArray();
		}

		if (array_key_exists('custom_field_values', $data[$this->_table->alias()])) {
			$count = 0;
			$checkboxes = [];
			$fieldIds = [];

			foreach ($data[$this->_table->alias()]['custom_field_values'] as $key => $obj) {
				$fieldType = $this->CustomFields
					->find('all')
					->select([$this->CustomFields->aliasField('field_type')])
					->where([$this->CustomFields->aliasField('id') => $obj[$this->config('fieldKey')]])
					->first()
					->field_type;

				$fieldValue = $fieldTypes[$fieldType];

				if (isset($obj[$fieldValue])) {
					// For checkbox
					if (is_array($obj[$fieldValue])) {
						$checkboxes[] = $data[$this->_table->alias()]['custom_field_values'][$key];
						$fieldIds[$obj[$this->config('fieldKey')]] = $obj[$this->config('fieldKey')];
						$obj[$fieldValue] = '';
					}
					// End
				} else {
					$obj[$fieldValue] = '';
				}

				// Will move the logic to StudentListBehavior eventually
				if ($fieldType != 'STUDENT_LIST' && strlen($obj[$fieldValue]) == 0) {
					unset($data[$this->_table->alias()]['custom_field_values'][$key]);
				}
				$count++;
			}

			foreach ($checkboxes as $checkbox) {
				$fieldType = $this->CustomFields
					->find('all')
					->select([$this->CustomFields->aliasField('field_type')])
					->where([$this->CustomFields->aliasField('id') => $checkbox[$this->config('fieldKey')]])
					->first()
					->field_type;

				$fieldValue = $fieldTypes[$fieldType];
				$answers = $checkbox[$fieldValue];

				foreach ($answers as $key => $checked) {
					if ($checked) {
						$checkbox[$fieldValue] = $key;
						$data[$this->_table->alias()]['custom_field_values'][++$count] = $checkbox;
					}
				}
			}

			if (array_key_exists('id', $data[$this->_table->alias()])) {
				if (!empty($fieldIds)) {
					$id = $data[$this->_table->alias()]['id'];
					$CustomFieldValues = $this->_table->CustomFieldValues;

					$CustomFieldValues->deleteAll([
						$CustomFieldValues->aliasField($this->config('recordKey')) => $id,
						$CustomFieldValues->aliasField($this->config('fieldKey') . ' IN') => $fieldIds
					]);
				}
			}
		}

		if (array_key_exists('custom_table_cells', $data[$this->_table->alias()])) {
			foreach ($data[$this->_table->alias()]['custom_table_cells'] as $key => $obj) {
				$fieldType = $this->CustomFields
					->find('all')
					->select([$this->CustomFields->aliasField('field_type')])
					->where([$this->CustomFields->aliasField('id') => $obj[$this->config('fieldKey')]])
					->first()
					->field_type;

				$fieldValue = $fieldTypes[$fieldType];

				if (strlen($obj[$fieldValue]) == 0) {
					unset($data[$this->_table->alias()]['custom_table_cells'][$key]);
				}
			}
		}
		// End Checking

    	$associatedOptions = $options->offsetExists('associated') ? $options->offsetGet('associated') : [];
    	$associatedOptions = array_merge_recursive($associatedOptions, $this->_contain);
    	$options->offsetSet('associated', $associatedOptions);
    }

    public function addEditAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
	}

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	// Will move the logic to StudentListBehavior eventually
    	if (array_key_exists($this->_table->alias(), $data)) {
    		if (array_key_exists('custom_field_values', $data[$this->_table->alias()])) {
    			$StudentSurveys = TableRegistry::get('Student.StudentSurveys');
    			$fieldTypes = $this->CustomFieldTypes
					->find('list', ['keyField' => 'code', 'valueField' => 'value'])
					->toArray();

    			foreach ($data[$this->_table->alias()]['custom_field_values'] as $key => $obj) {
    				if (array_key_exists($StudentSurveys->alias(), $obj)) {
    					$fieldId = $obj[$this->config('fieldKey')];
    					// Student List field type no need to store
    					$this->CustomFieldValues->deleteAll([
							$this->CustomFieldValues->aliasField($this->config('recordKey')) => $entity->id,
							$this->CustomFieldValues->aliasField($this->config('fieldKey')) => $fieldId
						]);

    					foreach ($obj[$StudentSurveys->alias()] as $studentId => $surveyObj) {
    						if (array_key_exists('custom_field_values', $surveyObj)) {
    							$surveyObj['status'] = $entity->status;
    							foreach ($surveyObj['custom_field_values'] as $fieldkey => $fieldObj) {
		    						$fieldType = $this->CustomFields
										->find('all')
										->select([$this->CustomFields->aliasField('field_type')])
										->where([$this->CustomFields->aliasField('id') => $fieldObj[$this->config('fieldKey')]])
										->first()
										->field_type;
		    						$fieldValue = $fieldTypes[$fieldType];

		    						if (!isset($fieldObj[$fieldValue])) {
		    							$fieldObj[$fieldValue] = '';
		    						}

		    						if (strlen($fieldObj[$fieldValue]) == 0) {
		    							unset($surveyObj['custom_field_values'][$fieldkey]);
									}
    							}

    							// Delete all answer and reinsert
    							if (isset($surveyObj['id'])) {
    								$recordKey = Inflector::underscore(Inflector::singularize($StudentSurveys->table())) . '_id';
	    							$StudentSurveys->CustomFieldValues->deleteAll([
										$StudentSurveys->CustomFieldValues->aliasField($recordKey) => $surveyObj['id']
									]);
								}

								$surveyEntity = $StudentSurveys->newEntity($surveyObj);
								if ($StudentSurveys->save($surveyEntity)) {
								} else {
									$this->log($surveyEntity->errors(), 'debug');
								}
    						}
    					}
    				}
    			}
    		}
    	}
    }

	public function getCustomFieldQuery($entity) {
		$customFieldQuery = null;
		//For Institution Survey
		if (is_null($this->config('moduleKey'))) {
			$customFormId = $entity->{$this->config('formKey')};

			if (isset($customFormId)) {
				$customFormQuery = $this->CustomForms
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([$this->CustomForms->aliasField('id') => $customFormId]);
			}
		} else {
			$where = [$this->CustomModules->aliasField('model') => $this->config('model')];
			if ($this->config('behavior')) {
				$where[$this->CustomModules->aliasField('behavior')] = $this->config('behavior');
			}

			$customModuleResults = $this->CustomModules
				->find('all')
				->select([
					$this->CustomModules->aliasField('id'),
					$this->CustomModules->aliasField('filter')
				])
				->where($where)
				->first();
			$customModuleId = $customModuleResults->id;
			$filter = $customModuleResults->filter;

			$customFormQuery = $this->CustomForms
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([$this->CustomForms->aliasField($this->config('moduleKey')) => $customModuleId]);

			if (!empty($filter)) {
				$modelAlias = $this->_table->ControllerAction->getModel($filter)['model'];
				$filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';

				$filterId = $entity->$filterKey;
				$filterModelAlias = $this->_table->ControllerAction->getModel($this->CustomFormsFilters->registryAlias())['model'];
				$customFormQuery
					->join([
						'table' => Inflector::tableize($filterModelAlias),
						'alias' => $this->CustomFormsFilters->alias(),
						'conditions' => [
							'OR' => [
								[
									$this->CustomFormsFilters->aliasField($this->config('formKey') . ' = ') . $this->CustomForms->aliasField('id'),
									$this->CustomFormsFilters->aliasField($this->config('filterKey')) => 0
								],
								[
									$this->CustomFormsFilters->aliasField($this->config('formKey') . ' = ') . $this->CustomForms->aliasField('id'),
									$this->CustomFormsFilters->aliasField($this->config('filterKey')) => $filterId
								]
							]
						]
					]);
			}
		}

		$customFormIds = $customFormQuery
			->toArray();

		$customFieldQuery = $this->CustomFormsFields
			->find('all')
			->find('order')
			->contain([
				'CustomFields.CustomFieldOptions' => function($q) {
					return $q
						->find('visible')
						->find('order');
				},
				'CustomFields.CustomTableColumns' => function ($q) {
			       return $q
			       		->find('visible')
			       		->find('order');
			    },
				'CustomFields.CustomTableRows' => function ($q) {
			       return $q
			       		->find('visible')
			       		->find('order');
			    },
			    'CustomFields.CustomFieldParams'
			])
			->where([
				$this->CustomFormsFields->aliasField($this->config('formKey') . ' IN') => $customFormIds
			])
			->group([
				$this->CustomFormsFields->aliasField($this->config('fieldKey'))
			]);

		return $customFieldQuery;
	}

	public function buildCustomFields($entity) {
		$customFieldQuery = $this->getCustomFieldQuery($entity);

		if (isset($customFieldQuery)) {
			$customFields = $customFieldQuery
				->toArray();

			$order = 0;
			$fieldOrder = [];
			$ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
			foreach ($this->_table->fields as $fieldName => $field) {
				if (!in_array($fieldName, $ignoreFields)) {
					$order = $field['order'] > $order ? $field['order'] : $order;
					$fieldOrder[$field['order']] = $fieldName;
				}
			}

			foreach ($customFields as $customFieldOrder => $customField) {
				$_customField = $customField->custom_field;

				$_field_type = $_customField->field_type;
				$_name = $_customField->name;
				$_attr = ['label' => $_name];
				if ($_customField->is_mandatory == 1) {
					$_attr['required'] = 'required';
				}

				$_id = null;
				$_value = null;
				if (isset($entity->id)) {
					$fieldValueData = $this->CustomFieldTypes
						->find('all')
						->select([$this->CustomFieldTypes->aliasField('value')])
						->where([$this->CustomFieldTypes->aliasField('code') => $_field_type])
						->first();
					$fieldValue = $fieldValueData->value;

					$results = $this->CustomFieldValues
						->find('all')
						->select([
							$this->CustomFieldValues->aliasField('id'),
							$this->CustomFieldValues->aliasField($fieldValue),
						])
						->where([
							$this->CustomFieldValues->aliasField($this->config('fieldKey')) => $_customField->id,
							$this->CustomFieldValues->aliasField($this->config('recordKey')) => $entity->id
						])
						->all();

					if (!$results->isEmpty()) {
						if ($_field_type == 'CHECKBOX') {
							$_value = [];
							$data = $results->toArray();
							foreach ($data as $obj) {
								$_value[] = [
									'id' => $obj->id,
									'value' => $obj->$fieldValue
								];
							}
						} else {
							$data = $results
								->first();

							$_id = $data->id;
							$_value = $data->$fieldValue;
						}

						$_attr['value'] = $_value;
					}
				}

				$this->_table->addBehavior(
					'CustomField.'.Inflector::camelize(strtolower($_field_type))
				);

				$fieldName = "custom_.$customFieldOrder._field";
				$fieldOrder[$order++] = $fieldName;
				$valueClass = strtolower($_field_type) == 'table' ? 'table-full-width' : '';

				$this->_table->ControllerAction->field($fieldName, [
		            'type' => 'custom_'. strtolower($_field_type),
		            'visible' => true,
		            'field' => $customFieldOrder,
		            'attr' => $_attr,
		            'recordKey' => $this->config('recordKey'),
		            'fieldKey' => $this->config('fieldKey'),
		            'tableColumnKey' => $this->config('tableColumnKey'),
		            'tableRowKey' => $this->config('tableRowKey'),
		            'customField' => $_customField,
		            'id' => $_id,
		            'value' => $_value,
		            'valueClass' => $valueClass
		        ]);
			}

			foreach ($ignoreFields as $key => $field) {
				$fieldOrder[++$order] = $field;
			}
			ksort($fieldOrder);
			$this->_table->ControllerAction->setFieldOrder($fieldOrder);
		}
	}
}
