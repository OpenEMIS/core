<?php
namespace CustomField\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use Cake\Log\Log;
use Cake\I18n\Time;

class RecordBehavior extends Behavior {
	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.viewEdit.beforeQuery'	=> ['callable' => 'viewEditBeforeQuery', 'priority' => 100],
			'ControllerAction.Model.view.afterAction'		=> ['callable' => 'viewAfterAction', 'priority' => 100],
			'ControllerAction.Model.addEdit.beforePatch' 	=> ['callable' => 'addEditBeforePatch', 'priority' => 100],
			'ControllerAction.Model.addEdit.afterAction' 	=> ['callable' => 'addEditAfterAction', 'priority' => 100],
            'ControllerAction.Model.add.beforeSave' 		=> ['callable' => 'addBeforeSave', 'priority' => 100],
            'ControllerAction.Model.edit.afterQuery'		=> ['callable' => 'editAfterQuery', 'priority' => 100],
            'ControllerAction.Model.edit.beforeSave' 		=> ['callable' => 'editBeforeSave', 'priority' => 100],
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
		'fieldClass' => ['className' => 'CustomField.CustomFields'],
		'formKey' => 'custom_form_id',
		'filterKey' => 'custom_filter_id',
		'formClass' => ['className' => 'CustomField.CustomForms'],
		'formFieldClass' => ['className' => 'CustomField.CustomFormsFields'],
		'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
		'recordKey' => 'custom_record_id',
		'fieldValueClass' => ['className' => 'CustomField.CustomFieldValues', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true],
		'tableCellClass' => ['className' => 'CustomField.CustomTableCells', 'foreignKey' => 'custom_record_id', 'dependent' => true, 'cascadeCallbacks' => true]
	];

	// value for these field types will be saved on custom_field_values
	private $fieldValueArray = ['TEXT', 'NUMBER', 'TEXTAREA', 'DROPDOWN', 'CHECKBOX', 'DATE', 'TIME'];

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
	private $_tableCellValues = [];

	public function initialize(array $config) {
		parent::initialize($config);
		if (is_null($this->config('moduleKey'))) {
			$this->_table->belongsTo('CustomForms', $this->config('formClass'));
		}
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

		$this->_table->addBehavior('CustomField.RenderText');
		$this->_table->addBehavior('CustomField.RenderNumber');
		$this->_table->addBehavior('CustomField.RenderTextarea');
		$this->_table->addBehavior('CustomField.RenderDropdown');
		$this->_table->addBehavior('CustomField.RenderCheckbox');
		$this->_table->addBehavior('CustomField.RenderTable');
		$this->_table->addBehavior('CustomField.RenderDate');
		$this->_table->addBehavior('CustomField.RenderTime');
		$this->_table->addBehavior('CustomField.RenderStudentList');

		// If tabSection is not set, added to handle Section Header
		if (!$this->config('tabSection')) {
			$this->_table->addBehavior('OpenEmis.Section');
		}

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

	public function viewEditBeforeQuery(Event $event, Query $query) {
		// do not contain CustomFieldValues
		$query->contain(['CustomTableCells']);
	}

	public function editAfterQuery(Event $event, Entity $entity) {
		$this->formatEntity($entity);
	}

    public function viewAfterAction(Event $event, Entity $entity) {
    	// add here to make view has the same format in edit
    	$this->formatEntity($entity);
    	$this->setupCustomFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$alias = $this->_table->alias();

    	if (array_key_exists('custom_field_values', $data[$alias])) {
			$values = $data[$alias]['custom_field_values'];
			$fieldValues = $this->_table->array_column($values, $this->config('fieldKey'));

			$CustomFields = TableRegistry::get($this->config('fieldClass.className'));
			$fields = $CustomFields->find()->where(['id IN' => $fieldValues])->all();

			foreach ($values as $key => $attr) {
				foreach ($fields as $f) {
					if ($f->id == $attr[$this->config('fieldKey')]) {
						$data[$alias]['custom_field_values'][$key]['field_type'] = $f->field_type;
						$data[$alias]['custom_field_values'][$key]['mandatory'] = $f->is_mandatory;
						$data[$alias]['custom_field_values'][$key]['unique'] = $f->is_unique;
						$data[$alias]['custom_field_values'][$key]['params'] = $f->params;
					}
				}
			}
		}

		$arrayOptions = $options->getArrayCopy();
		if (!empty($arrayOptions)) {
			$arrayOptions = array_merge_recursive($arrayOptions, ['associated' => ['CustomFieldValues', 'CustomTableCells']]);
			$options->exchangeArray($arrayOptions);
		}
    }

	public function addEditAfterAction(Event $event, Entity $entity) {
    	$this->setupCustomFields($entity);
	}

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		return $this->processSave($entity, $data);
	}

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
    	return $this->processSave($entity, $data);
    }

    private function processSave(Entity $entity, ArrayObject $data) {
    	$model = $this->_table;
    	$process = function($model, $entity) use ($data, $model) {
    		$errors = $entity->errors();

			if (empty($errors)) {
				$settings = new ArrayObject([
					'recordKey' => $this->config('recordKey'),
					'fieldKey' => $this->config('fieldKey'),
					'tableColumnKey' => $this->config('tableColumnKey'),
					'tableRowKey' => $this->config('tableRowKey'),
					'valueKey' => null,
					'customValue' => null,
					'fieldValues' => [],
					'tableCells' => [],
					'deleteFieldIds' => []
				]);

				if (array_key_exists('custom_field_values', $data[$model->alias()])) {
					$values = $data[$model->alias()]['custom_field_values'];
					foreach ($values as $key => $obj) {
						$fieldType = Inflector::camelize(strtolower($obj['field_type']));
						$settings['customValue'] = $obj;

						$event = $model->dispatchEvent('Render.process'.$fieldType.'Values', [$entity, $data, $settings], $model);
						if ($event->isStopped()) { return $event->result; }
					}
				}

				if ($this->_table->hasBehavior('RenderTable')) {
					if (array_key_exists('custom_table_cells', $data[$model->alias()])) {
						$event = $model->dispatchEvent('Render.processTableValues', [$entity, $data, $settings], $model);
						if ($event->isStopped()) { return $event->result; }
					}
				}

				// when edit always delete all the checkbox values before reinsert,
				// also delete previously saved records with empty value
				if (isset($entity->id)) {
					$id = $entity->id;
					$deleteFieldIds = $settings['deleteFieldIds'];

					if (!empty($deleteFieldIds)) {
						$this->CustomFieldValues->deleteAll([
							$this->CustomFieldValues->aliasField($settings['recordKey']) => $id,
							$this->CustomFieldValues->aliasField($settings['fieldKey'] . ' IN ') => $deleteFieldIds
						]);

						// when edit always delete all the cell values before reinsert
			            $this->CustomTableCells->deleteAll([
			                $this->CustomTableCells->aliasField($settings['recordKey']) => $id,
			                $this->CustomTableCells->aliasField($settings['fieldKey'] . ' IN ') => $deleteFieldIds
			            ]);
		            }
				}

				// repatch $entity for saving, turn off validation
	            $data[$model->alias()]['custom_field_values'] = $settings['fieldValues'];
				$data[$model->alias()]['custom_table_cells'] = $settings['tableCells'];

				$requestData = $data->getArrayCopy();
				$patchOptions['associated'] = [
					'CustomFieldValues' => ['validate' => false],
					'CustomTableCells' => ['validate' => false]
				];
        		$entity = $model->patchEntity($entity, $requestData, $patchOptions);
        		// End

        		return $model->save($entity);
			} else {
				if (array_key_exists('custom_field_values', $errors)) {
					$fields = ['text_value', 'number_value', 'textarea_value', 'date_value', 'time_value'];
					$indexedErrors = [];
					if ($entity->has('custom_field_values')) {
						foreach ($entity->custom_field_values as $key => $obj) {
							$fieldId = $obj->{$this->config('fieldKey')};

							if (array_key_exists($key, $errors['custom_field_values'])) {
								$indexedErrors[$fieldId] = $errors['custom_field_values'][$key];
								foreach ($fields as $field) {
									$entity->custom_field_values[$key]->dirty($field, true);
								}
							}
						}
					}

					if (array_key_exists('custom_field_values', $data[$model->alias()])) {
						foreach ($data[$model->alias()]['custom_field_values'] as $key => $obj) {
							$fieldId = $obj[$this->config('fieldKey')];

							if (array_key_exists($fieldId, $indexedErrors)) {
								foreach ($fields as $field) {
									if (array_key_exists($field, $indexedErrors[$fieldId])) {
										$error = $indexedErrors[$fieldId][$field];
										$entity->custom_field_values[$key]->errors($field, $error, true);
									}
								}
							}
						}
					}
				}
				Log::write('debug', $entity->errors());

				return false;
			}
    	};

		return $process;
    }

    /**
	 *	Function to get the filter key from the filter specified
     *
     *	@param string $filter The filter provided by the custom module
     *	@param string $model The model provided by the custom module
     *	@return The filter foreign key name if found. If not it will return empty.
     */
	public function getFilterKey($filterAlias, $modelAlias) {
		$filterKey = '';
		$associations = TableRegistry::get($filterAlias)->associations();
		foreach ($associations as $assoc) {
			if ($assoc->registryAlias() == $modelAlias) {
				$filterKey = $assoc->foreignKey();
				return $filterKey;
			}
		}
		return $filterKey;
	}

	public function getCustomFieldQuery($entity, $params=[]) {
		$query = null;
		$withContain = array_key_exists('withContain', $params) ? $params['withContain'] : true;

		// For Institution Survey
		if (is_null($this->config('moduleKey'))) {
			if ($entity->has($this->config('formKey'))) {
				$customFormId = $entity->{$this->config('formKey')};

				if (isset($customFormId)) {
					$customFormQuery = $this->CustomForms
						->find('list', ['keyField' => 'id', 'valueField' => 'id'])
						->where([$this->CustomForms->aliasField('id') => $customFormId]);
				}
			}
		} else {
			$where = [$this->CustomModules->aliasField('model') => $this->config('model')];
			if ($this->config('behavior')) {
				$where[$this->CustomModules->aliasField('behavior')] = $this->config('behavior');
			}

			$results = $this->CustomModules
				->find('all')
				->select([
					$this->CustomModules->aliasField('id'),
					$this->CustomModules->aliasField('filter')
				])
				->where($where)
				->first();

			if (!empty($results)) {
				$moduleId = $results->id;
				$filterAlias = $results->filter;

				$customFormQuery = $this->CustomForms
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([$this->CustomForms->aliasField($this->config('moduleKey')) => $moduleId]);

				if (!empty($filterAlias)) {
					$filterKey = $this->getFilterKey($filterAlias, $this->config('model'));
					if (empty($filterKey)) {
						list($modelplugin, $modelAlias) = explode('.', $filterAlias, 2);
						$filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
					}

					$filterId = $entity->$filterKey;
					$customFormQuery
						->join([
							'table' => $this->CustomFormsFilters->table(),
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

			$query = $this->CustomFormsFields
				->find('all')
				->find('order')
				->where([
					$this->CustomFormsFields->aliasField($this->config('formKey') . ' IN') => $customFormIds
				])
				->group([
					$this->CustomFormsFields->aliasField($this->config('fieldKey'))
				]);

			if ($withContain) {
				if (is_array($withContain)) {
					$query->contain($withContain);
				} else {
					$query->contain([
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
					]);
				}
			}
		}

		return $query;
	}

	public function formatEntity(Entity $entity) {
		$model = $this->_table;
		$primaryKey = $model->primaryKey();
		$idKey = $model->aliasField($primaryKey);
		$id = $entity->id;

		$values = [];
		if ($model->exists([$idKey => $id])) {
			$query = $model->find()->contain(['CustomFieldValues.CustomFields'])->where([$idKey => $id]);

			$newEntity = $query->first();
			if ($newEntity->has('custom_field_values')) {
				foreach ($newEntity->custom_field_values as $key => $obj) {
					$fieldId = $obj->{$this->config('fieldKey')};
					$customField = $obj->custom_field;

					if ($customField->field_type == 'CHECKBOX') {
						$checkboxValues = [$obj['number_value']];
						if (array_key_exists($fieldId, $values)) {
							$checkboxValues = array_merge($checkboxValues, $values[$fieldId]['number_value']);
						}
						$obj['number_value'] = $checkboxValues;
					}
					$values[$fieldId] = $obj;
				}
			}
		}

		$query = $this->getCustomFieldQuery($entity, ['withContain' => ['CustomFields']]);

		$fieldValues = [];	// values of custom field must be in sequence for validation errors to be placed correctly
		if (!is_null($query)) {
    		$customFields = $query->toArray();

			foreach ($customFields as $key => $obj) {
				$customField = $obj->custom_field;
				$fieldTypeCode = $customField->field_type;

				// only apply for field type store in custom_field_values
				if (in_array($fieldTypeCode, $this->fieldValueArray)) {
					$fieldId = $customField->id;

					if (array_key_exists($fieldId, $values)) {
						$fieldValues[] = $values[$fieldId];
					} else {
						$valueData = [
							'text_value' => null,
							'number_value' => null,
							'textarea_value' => null,
							'date_value' => null,
							'time_value' => null,
							$this->config('fieldKey') => $fieldId,
							$this->config('recordKey') => $entity->id,
							'custom_field' => null // set after data is patched else will lost
						];
						$valueEntity = $this->CustomFieldValues->newEntity($valueData, ['validate' => false]);
						$valueEntity->custom_field = $customField;
						$fieldValues[] = $valueEntity;
					}
				} else {
					$fieldType = Inflector::camelize(strtolower($fieldTypeCode));
					$settings = new ArrayObject([
						'fieldKey' => $this->config('fieldKey'),
						'formKey' => $this->config('formKey'),
						'customField' => $customField
					]);

					$event = $model->dispatchEvent('Render.format'.$fieldType.'Entity', [$entity, $settings], $model);
					if ($event->isStopped()) { return $event->result; }
				}
			}
    	}

    	$entity->set('custom_field_values', $fieldValues);
	}

	public function setupCustomFields(Entity $entity) {
		$model = $this->_table;
		$query = $this->getCustomFieldQuery($entity);

		// If tabSection is set, setup Tab Section
		if ($this->config('tabSection')) {
			$customFields = $query->toArray();

			$tabElements = [];
			$action = $model->ControllerAction->action();
			$url = $model->ControllerAction->url($action);
			$sectionName = null;
			foreach ($customFields as $key => $obj) {
				if (isset($obj->section)) {
					if ($sectionName != $obj->section) {
						$sectionName = $obj->section;
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
				$selectedAction = !is_null($model->request->query('tab_section')) ? $model->request->query('tab_section') : $selectedAction;
				$model->controller->set('tabElements', $tabElements);
				$model->controller->set('selectedAction', $selectedAction);

				$query->where([
					$this->CustomFormsFields->aliasField('section') => $tabElements[$selectedAction]['text']
				]);
			}
		}
		// End

		if (!is_null($query)) {
			$customFields = $query->toArray();

			$order = 0;
			$fieldOrder = [];
			// temporary fix: to make custom fields appear before map in Institutions > General > Overview
			$ignoreFields = ['id', 'map_section', 'map', 'modified_user_id', 'modified', 'created_user_id', 'created'];
			foreach ($model->fields as $fieldName => $field) {
				if (!in_array($fieldName, $ignoreFields)) {
					$order = $field['order'] > $order ? $field['order'] : $order;
					$fieldOrder[$field['order']] = $fieldName;
				}
			}

			// retrieve saved values
			$values = new ArrayObject([]);
			$cells = new ArrayObject([]);

			if (isset($entity->id)) {
				$fieldKey = $this->config('fieldKey');
				$tableRowKey = $this->config('tableRowKey');
				$tableColumnKey = $this->config('tableColumnKey');

				if ($entity->has('custom_field_values')) {
					foreach ($entity->custom_field_values as $key => $obj) {
						if (isset($obj->id)) {
							$fieldId = $obj->{$fieldKey};
							$fieldData = ['id' => $obj->id];

							if ($model->request->is(['get'])) {
								// onGet
								$fieldData['text_value'] = $obj->text_value;
								$fieldData['number_value'] = $obj->number_value;
								$fieldData['textarea_value'] = $obj->textarea_value;
								$fieldData['date_value'] = $obj->date_value;
								$fieldData['time_value'] = $obj->time_value;
							} else if ($model->request->is(['post', 'put'])) {
					        	// onPost, no actions
					        }
					        $values[$fieldId] = $fieldData;
						}
					}
				}

				if ($entity->has('custom_table_cells')) {
					foreach ($entity->custom_table_cells as $key => $obj) {
						$fieldId = $obj->{$fieldKey};
						$rowId = $obj->{$tableRowKey};
						$columnId = $obj->{$tableColumnKey};

            			$cells[$fieldId][$rowId][$columnId] = $obj['text_value'];
            		}
				}
			}

	        $valuesArray = $values->getArrayCopy();
	        $cellsArray = $cells->getArrayCopy();
			// End

	        $count = 0;
	        $sectionName = null;
			foreach ($customFields as $key => $obj) {
				// If tabSection is not set, setup Section Header
				if (!$this->config('tabSection')) {
					if (isset($obj->section)) {
						if ($sectionName != $obj->section) {
							$sectionName = $obj->section;
							$fieldName = "section_".$key."_header";

							$model->ControllerAction->field($fieldName, ['type' => 'section', 'title' => $sectionName]);
							$fieldOrder[++$order] = $fieldName;
						}
					}
				}
				// End

				$customField = $obj->custom_field;

				$fieldType = $customField->field_type;
				$fieldName = "custom_".$key."_field";
				$valueClass = strtolower($fieldType) == 'table' || strtolower($fieldType) == 'student_list' ? 'table-full-width' : '';

				$attr = [
					'type' => 'custom_'. strtolower($fieldType),
					'attr' => [
						'label' => $customField->name,
						'fieldKey' => $this->config('fieldKey'),
						'formKey' => $this->config('formKey')
					],
					'valueClass' => $valueClass,
					'customField' => $customField,
					'customFieldValues' => $valuesArray,
					'customTableCells' => $cellsArray
				];

				// for label of mandatory *
				if ($customField->is_mandatory == 1) {
					$attr['attr']['required'] = 'required';
				}

				// seq is very important for validation errors
				if (in_array($fieldType, $this->fieldValueArray)) {
					$attr['attr']['seq'] = $count++;
				}

				$model->ControllerAction->field($fieldName, $attr);
				$fieldOrder[++$order] = $fieldName;
			}

			foreach ($ignoreFields as $key => $field) {
				// add checking (map_section, map) to append ignore fields only if exists
				if (array_key_exists($field, $this->_table->fields)) {
					$fieldOrder[++$order] = $field;
				}
			}
			ksort($fieldOrder);
			$model->ControllerAction->setFieldOrder($fieldOrder);
		}
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

    	$tableCustomFieldIds = [];
		$customFields = $this->getCustomFieldQuery($entity)->toArray();
		foreach ($customFields as $customField) {
			$_customField = $customField->custom_field;
			$_field_type = $_customField->field_type;
			$_id = $_customField->id;
			$_name = $_customField->name;

			if ($_field_type != 'TABLE') {
				$field['key'] = 'CustomField';
				$field['field'] = 'custom_field';
				$field['type'] = 'custom_field';
				$field['label'] = $_name;
				$field['customField'] = ['id' => $_id, 'field_type' => $_field_type];
				$fields[] = $field;
			} else {
				$tableCustomFieldIds[] = $_id;
				$tableRow = $_customField->custom_table_rows;
				$tableCol = $_customField->custom_table_columns;
				$row = [];
				foreach ($tableRow as $r) {
					$row[$r['order']] = $r;
				}
				ksort($row);
				$row = array_values($row);
				$col = [];
				foreach ($tableCol as $c) {
					$col[$c['order']] = $c;
				}
				ksort($col);
				$col = array_values($col);

				if (sizeof($row) !=0 && sizeof($col) !=0 ) {
					for($i = 1; $i < sizeof($col); $i++) {
						foreach ($row as $rw) {
							$field['key'] = 'CustomField';
							$field['field'] = 'custom_field';
							$field['type'] = 'custom_field';
							$field['label'] = $_name . ' ('.$col[$i]['name'].', '.$rw['name'].')';
							$field['customField'] = ['id' => $_id, 'field_type' => $_field_type, 'col_id' => $col[$i]['id'], 'row_id' => $rw['id']];
							$fields[] = $field;
						}
					}
				}
			}
		}

		// Set the available options for dropdown and checkbox type
		$this->_customFieldOptions = $settings['sheet']['customFieldOptions'];

		// Set the fetched table cell values to avoid multiple call to the database
		$tableCellValues = $this->getTableCellValues($tableCustomFieldIds, $entity->id);

		// Set the fetched field values to avoid multiple call to the database
		$fieldValues = $this->getFieldValue($entity->id) + $tableCellValues;
		ksort($fieldValues);	
		$this->_fieldValues = $fieldValues;	
	}

	private function getTableCellValues($tableCustomFieldIds, $recordId) {
		if (!empty($tableCustomFieldIds)) {
			$TableCellTable = $this->CustomTableCells;
			$customFieldsForeignKey = $TableCellTable->CustomFields->foreignKey();
			$customRecordsForeignKey = $TableCellTable->CustomRecords->foreignKey();
			$customColumnForeignKey = $TableCellTable->CustomTableColumns->foreignKey();
			$customRowForeignKey = $TableCellTable->CustomTableRows->foreignKey();
			$tableCellData = new ArrayObject();
			$TableCellTable
					->find()
					->where([$TableCellTable->aliasField($customFieldsForeignKey).' IN ' => $tableCustomFieldIds, $TableCellTable->aliasField($customRecordsForeignKey) => $recordId])
				    ->map(function ($row) use ($tableCellData, $customFieldsForeignKey, $customColumnForeignKey, $customRowForeignKey) {
				        $tableCellData[$row[$customFieldsForeignKey]][$row[$customColumnForeignKey]][$row[$customRowForeignKey]] = $row['text_value'];
				        return $row;
				    })
				    ->toArray();
			$tableCellData = $tableCellData->getArrayCopy();
			return $tableCellData;
		}
		return [];
	}

	// Model.excel.onExcelRenderCustomField
	public function onExcelRenderCustomField(Event $event, Entity $entity, array $attr) {
		if (!empty($this->_fieldValues)) {
			$answer = '';
			$type = strtolower($attr['customField']['field_type']);
			if (method_exists($this, $type)) {
				$ans = $this->$type($this->_fieldValues, $attr['customField'], $this->_customFieldOptions);
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

	private function text($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			return $data[$fieldInfo['id']];
		} else {
			return '';
		}
	}

	private function number($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			return $data[$fieldInfo['id']];
		} else {
			return '';
		}
	}
	
	private function textarea($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			return $data[$fieldInfo['id']];
		} else {
			return '';
		}
	}

	private function dropdown($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			if (isset($options[$data[$fieldInfo['id']]])) {
				return $options[$data[$fieldInfo['id']]];
			} else {
				return '';
			}
		} else {
			return '';
		}
	}
	
	private function checkbox($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			$values = explode(",", $data[$fieldInfo['id']]);
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

	private function date($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			$date = date_create_from_format('Y-m-d', $data[$fieldInfo['id']]);
			return $this->_table->formatDate(new Time($date));
		} else {
			return '';
		}
	}

	private function time($data, $fieldInfo, $options=[]) {
		if (isset($data[$fieldInfo['id']])) {
			$time = date_create_from_format('G:i:s', $data[$fieldInfo['id']]);
			return $this->_table->formatTime(new Time($time));
		} else {
			return '';
		}
	}

	private function student_list($data, $fieldInfo, $options=[]) {
		return null;
	}

	private function table($data, $fieldInfo, $options=[]) {
		$id = $fieldInfo['id'];
		$colId = $fieldInfo['col_id'];
		$rowId = $fieldInfo['row_id'];
		if (isset($data[$id][$colId][$rowId])) {
			return $data[$id][$colId][$rowId];
		}
		return '';
	}
}
