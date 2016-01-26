<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;

class RecordBehavior extends Behavior {
	protected $_contain = ['CustomFieldValues', 'CustomTableCells'];
	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.view.afterAction'		=> ['callable' => 'viewAfterAction', 'priority' => 100],
			'ControllerAction.Model.addEdit.beforePatch' 	=> ['callable' => 'addEditBeforePatch', 'priority' => 100],
			'ControllerAction.Model.addEdit.afterAction' 	=> ['callable' => 'addEditAfterAction', 'priority' => 100],
			'ControllerAction.Model.edit.afterSave' 		=> ['callable' => 'editAfterSave', 'priority' => 100],
			'Model.custom.onUpdateToolbarButtons'			=> 'onUpdateToolbarButtons',
			'Model.excel.onExcelUpdateFields'				=> ['callable' => 'onExcelUpdateFields', 'priority' => 110],
			'Model.excel.onExcelBeforeStart'				=> 'onExcelBeforeStart',
			'Model.excel.onExcelRenderCustomField'			=> 'onExcelRenderCustomField'
		],
		'model' => null,
		'behavior' => null,
		'tabSection' => false,
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

	// Use for excel only
	private $_fieldValues = [];
	private $_customFieldOptions = [];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->_table->hasMany('CustomFieldValues', $this->config('fieldValueClass'));
		$this->_table->hasMany('CustomTableCells', $this->config('tableCellClass'));

		$this->CustomFieldValues = $this->_table->CustomFieldValues;
		$this->CustomTableCells = $this->_table->CustomTableCells;

		$this->CustomModules = TableRegistry::get('CustomField.CustomModules');
		$this->CustomFieldTypes = TableRegistry::get('CustomField.CustomFieldTypes');

		$this->CustomFields = $this->CustomFieldValues->CustomFields;
		$this->CustomFieldOptions = $this->CustomFieldValues->CustomFields->CustomFieldOptions;
		$this->CustomForms = $this->CustomFields->CustomForms;
		$this->CustomFormsFields = TableRegistry::get($this->config('formFieldClass.className'));
		$this->CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));

		$model = $this->config('model');
		if (empty($model)) {
			$this->config('model', $this->_table->registryAlias());
		}

		$this->_table->addBehavior('CustomField.Table');
    }

    public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events = array_merge($events, $this->config('events'));
    	return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($this->config('tabSection')) {
			$currentAction = $this->_table->ControllerAction->action();
			if ($currentAction == 'view') {
				if ($toolbarButtons->offsetExists('back')) {
					if (array_key_exists('tab_section', $toolbarButtons['back']['url'])) {
						unset($toolbarButtons['back']['url']['tab_section']);
					}
				}
			}
		}
	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	$this->buildCustomFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	// Checking : skip insert if value is empty
    	if (array_key_exists('custom_field_values', $data[$this->_table->alias()])) {
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

				// Delete existing answer and reinsert
				if (isset($obj['id'])) {
					$this->CustomFieldValues->deleteAll([
						'id' => $obj['id']
					]);
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

				$redirectUrl = null;
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
    							$surveyObj['status_id'] = $entity->status_id;
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

									// Delete existing answer and reinsert
									if (isset($fieldObj['id'])) {
										$StudentSurveys->CustomFieldValues->deleteAll([
											'id' => $fieldObj['id']
										]);
									}
    							}

								$surveyEntity = $StudentSurveys->newEntity($surveyObj);
								if ($StudentSurveys->save($surveyEntity)) {
								} else {
									$this->log($surveyEntity->errors(), 'debug');
								}
    						}
    					}

    					if (is_null($redirectUrl)) {
    						$redirectUrl = $this->_table->ControllerAction->url('edit');
    					}
    				}
    			}
    		}
    	}
    }

    /**
	 *	Function to get the filter key from the filter specified
     *
     *	@param string $filter The filter provided by the custom module
     *	@param string $model The model provided by the custom module
     *	@return The filter foreign key name if found. If not it will return empty.
     */
	public function getFilterKey($filter, $model) {
		$filterKey = '';
		$associations = TableRegistry::get($filter)->associations();
		foreach ($associations as $assoc) {
			if ($assoc->registryAlias() == $model) {
				$filterKey = $assoc->foreignKey();
				return $filterKey;
			}
		}
		return $filterKey;
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

			if (!empty($customModuleResults)) {
				$customModuleId = $customModuleResults->id;
				$filter = $customModuleResults->filter;

				$customFormQuery = $this->CustomForms
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([$this->CustomForms->aliasField($this->config('moduleKey')) => $customModuleId]);

				if (!empty($filter)) {
					$modelAlias = $this->getModel($filter)['model'];
					$filterKey = $this->getFilterKey($filter, $this->config('model'));

					$filterId = $entity->$filterKey;
					$filterModelAlias = $this->getModel($this->CustomFormsFilters->registryAlias())['model'];
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
		}

		if (!empty($customFormQuery)) {
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
				    }
				])
				->where([
					$this->CustomFormsFields->aliasField($this->config('formKey') . ' IN') => $customFormIds
				])
				->group([
					$this->CustomFormsFields->aliasField($this->config('fieldKey'))
				]);
		}

		return $customFieldQuery;
	}

	public function getModel($model) {
		$split = explode('.', $model);
		$plugin = null;
		$modelClass = $model;
		if (count($split) > 1) {
			$plugin = $split[0];
			$modelClass = $split[1];
		}
		return ['plugin' => $plugin, 'model' => $modelClass];
	}

	// Model.excel.onExcelBeforeStart
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
    	$optionsValues = $this->CustomFieldOptions->find('list')->toArray();
    	$sheets[] = [
    		'name' => $this->_table->alias(),
			'table' => $this->_table,
			'query' => $this->_table->find(),
			'customFieldOptions' => $optionsValues,
    	];
    }

	// Model.excel.onExcelUpdateFields
	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$recordId = $settings['id'];
    	$entity = $this->_table->get($recordId);

		$customFields = $this->getCustomFieldQuery($entity)->toArray();
		foreach ($customFields as $customField) {
			$_customField = $customField->custom_field;
			$_field_type = $_customField->field_type;
			$_id = $_customField->id;
			$_name = $_customField->name;

			$field['key'] = 'CustomField';
			$field['field'] = 'custom_field';
			$field['type'] = 'custom_field';
			$field['label'] = $_name;
			$field['customField'] = ['id' => $_id, 'field_type' => $_field_type];
			$fields[] = $field;
		}

		// Set the available options for dropdown and checkbox type
		$this->_customFieldOptions = $settings['sheet']['customFieldOptions'];

		// Set the fetched field values to avoid multiple call to the database
		$this->_fieldValues = $this->getFieldValue($entity->id);
	}

	// Model.excel.onExcelRenderCustomField
	public function onExcelRenderCustomField(Event $event, Entity $entity, array $attr) {
		if (!empty($this->_fieldValues)) {
			$answer = '';
			$type = strtolower($attr['customField']['field_type']);
			if (method_exists($this, $type)) {
				$ans = $this->$type($this->_fieldValues, $attr['customField']['id'], $this->_customFieldOptions);
				if (!(is_null($ans))) {
					$answer = $ans;
				}
			}
			return $answer;
		} else {
			return '';
		}
	}

	/**
	 *	Function to get the field values base on a given record id
	 *
	 *	@param int $recordId The record id of the entity
	 *	@return array The field values of that given record id
	 */
	public function getFieldValue($recordId) {
		$customFieldValueTable = $this->CustomFieldValues;
		$customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();

		$selectedColumns = [
			$customFieldValueTable->aliasField($customFieldsForeignKey),
			'field_value' => '(GROUP_CONCAT((CASE WHEN '.$customFieldValueTable->aliasField('text_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('text_value')
				.' WHEN '.$customFieldValueTable->aliasField('number_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('number_value')
				.' WHEN '.$customFieldValueTable->aliasField('textarea_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('textarea_value')
				.' WHEN '.$customFieldValueTable->aliasField('date_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('date_value')
				.' WHEN '.$customFieldValueTable->aliasField('time_value').' IS NOT NULL THEN '.$customFieldValueTable->aliasField('time_value')
				.' END) SEPARATOR \',\'))'
		];
		
		// Getting the custom field table
		$customFieldsTable = $customFieldValueTable->CustomFields;

		// Getting the custom field values group by the record id, and then group by the field ids
		// Record with similar record id and field ids will be group concat together
		// For example: for checkbox, record id: 1, field id: 1, value: 1 and record id: 1, field id: 1, value: 2 will be
		// group as record id: 1, field id: 1, value: 1,2
		$fieldValue = $customFieldsTable
			->find('list', [
				'keyField' => $customFieldValueTable->aliasField($customFieldsForeignKey),
				'valueField' => 'field_value',
			])
			->innerJoin(
				[$customFieldValueTable->alias() => $customFieldValueTable->table()],
				[$customFieldValueTable->aliasField($customFieldsForeignKey).'='.$customFieldsTable->aliasField('id')]
			)
			->select($selectedColumns)
			->where([$customFieldValueTable->aliasField($customRecordsForeignKey) => $recordId])
			->group([$customFieldValueTable->aliasField($customFieldsForeignKey)])
			->toArray();

		return $fieldValue;
	}

	public function buildCustomFields($entity) {
		$customFieldQuery = $this->getCustomFieldQuery($entity, true);

		if ($this->config('tabSection')) {
			$customFields = $customFieldQuery
				->toArray();

			$tabElements = [];
			$action = $this->_table->ControllerAction->action();
			$url = $this->_table->ControllerAction->url($action);
			$sectionName = null;
			foreach ($customFields as $customFieldOrder => $customField) {
				if (isset($customField->section)) {
					if ($sectionName != $customField->section) {
						$sectionName = $customField->section;
						$tabName = Inflector::slug($sectionName);
						if (empty($tabElements)) {
							$selectedAction = $tabName;
						}
						$url['tab_section'] = $tabName;
						$tabElements[$tabName] = [
							'url' => $url,
							'text' => $sectionName,
						];
					}
				}
			}

			if (!empty($tabElements)) {
				$selectedAction = !is_null($this->_table->controller->request->query('tab_section')) ? $this->_table->controller->request->query('tab_section') : $selectedAction;
				$this->_table->controller->set('tabElements', $tabElements);
				$this->_table->controller->set('selectedAction', $selectedAction);

				$customFieldQuery->where([
					$this->CustomFormsFields->aliasField('section') => $tabElements[$selectedAction]['text']
				]);
			}
		}

		if (isset($customFieldQuery)) {
			$customFields = $customFieldQuery
				->toArray();

			$order = 0;
			$fieldOrder = [];
			// temporary fix: to make custom fields appear before map in Institutions > General > Overview
			$ignoreFields = ['id', 'map_section', 'map', 'modified_user_id', 'modified', 'created_user_id', 'created'];
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
				$valueClass = strtolower($_field_type) == 'table' || strtolower($_field_type) == 'student_list' ? 'table-full-width' : '';

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
	
	private function text($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			return $data[$fieldId];
		} else {
			return '';
		}
	}

	private function number($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			return $data[$fieldId];
		} else {
			return '';
		}
	}
	
	private function textarea($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			return $data[$fieldId];
		} else {
			return '';
		}
	}

	private function dropdown($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			if (isset($options[$data[$fieldId]])) {
				return $options[$data[$fieldId]];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	
	private function checkbox($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			$values = explode(",", $data[$fieldId]);
			$returnValue = '';
			foreach ($values as $value) {
				if (isset($options[$value])) {
					if (empty($returnValue)) {
						$returnValue = $options[$value];
					} else {
						$returnValue = $returnValue.', '.$options[$value];						
					}
				}
			}
			return $returnValue;
		} else {
			return '';
		}
	}

	private function date($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			$date = date_create_from_format('Y-m-d', $data[$fieldId]);
			return $this->_table->formatDate($date);
		} else {
			return '';
		}
	}

	private function time($data, $fieldId, $options=[]) {
		if (isset($data[$fieldId])) {
			$time = date_create_from_format('G:i:s', $data[$fieldId]);
			return $this->_table->formatTime($date);
		} else {
			return '';
		}
	}

	private function student_list($data, $fieldId, $options=[]) {
		return null;
	}

	private function table($data, $fieldId, $options=[]) {
		return null;
	}
}
