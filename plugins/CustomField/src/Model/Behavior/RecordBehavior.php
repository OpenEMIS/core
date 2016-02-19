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

class RecordBehavior extends Behavior {
	protected $_defaultConfig = [
		'events' => [
			'ControllerAction.Model.viewEdit.beforeQuery'	=> ['callable' => 'viewEditBeforeQuery', 'priority' => 100],
			'ControllerAction.Model.view.afterAction'		=> ['callable' => 'viewAfterAction', 'priority' => 100],
			'ControllerAction.Model.addEdit.beforePatch' 	=> ['callable' => 'addEditBeforePatch', 'priority' => 100],
			'ControllerAction.Model.addEdit.afterAction' 	=> ['callable' => 'addEditAfterAction', 'priority' => 100],
            'ControllerAction.Model.add.beforeSave' 		=> ['callable' => 'addBeforeSave', 'priority' => 100],
            'ControllerAction.Model.edit.afterQuery'		=> ['callable' => 'editAfterQuery', 'priority' => 100],
            'ControllerAction.Model.edit.beforeSave' 		=> ['callable' => 'editBeforeSave', 'priority' => 100]
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
	private $fieldValueArray = ['TEXT', 'NUMBER', 'TEXTAREA', 'DROPDOWN', 'CHECKBOX'];

	public function initialize(array $config) {
		parent::initialize($config);
		if (is_null($this->config('moduleKey'))) {
			$this->_table->belongsTo('CustomForms', $this->config('formClass'));
		}
		$this->_table->hasMany('CustomFieldValues', $this->config('fieldValueClass'));
		$this->_table->hasMany('CustomTableCells', $this->config('tableCellClass'));

		$this->_table->addBehavior('CustomField.RenderText');
		$this->_table->addBehavior('CustomField.RenderNumber');
		$this->_table->addBehavior('CustomField.RenderTextarea');
		$this->_table->addBehavior('CustomField.RenderDropdown');
		$this->_table->addBehavior('CustomField.RenderCheckbox');
		$this->_table->addBehavior('CustomField.RenderTable');
		// $this->_table->addBehavior('CustomField.RenderDate');
		// $this->_table->addBehavior('CustomField.RenderTime');
		// $this->_table->addBehavior('CustomField.RenderStudentList');

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

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['CustomFieldValues.CustomFields', 'CustomTableCells']);
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
				}
			}
		}
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
					if (!empty($settings['deleteFieldIds'])) {
						$CustomFieldValues = $this->_table->CustomFieldValues;
						$CustomFieldValues->deleteAll([
							$CustomFieldValues->aliasField($settings['recordKey']) => $id,
							$CustomFieldValues->aliasField($settings['fieldKey'] . ' IN ') => $settings['deleteFieldIds']
						]);
		            }

					// when edit always delete all the cell values before reinsert
		            $CustomTableCells = $this->_table->CustomTableCells;
		            $CustomTableCells->deleteAll([
		                $CustomTableCells->aliasField($settings['recordKey']) => $id
		            ]);
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
				Log::write('debug', $errors);
				return false;
			}
    	};

		return $process;
    }

	public function addBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		return $this->processSave($entity, $data);
	}

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
    	return $this->processSave($entity, $data);
    }

    public function addEditAfterAction(Event $event, Entity $entity) {
    	$this->setupCustomFields($entity);
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

	public function getCustomFieldQuery($entity) {
		$query = null;

		$CustomModules = TableRegistry::get('CustomField.CustomModules');
		$CustomFieldValues = $this->_table->CustomFieldValues;
		$CustomTableCells = $this->_table->CustomTableCells;
		$CustomForms = $CustomFieldValues->CustomFields->CustomForms;
		$CustomFormsFields = TableRegistry::get($this->config('formFieldClass.className'));
		$CustomFormsFilters = TableRegistry::get($this->config('formFilterClass.className'));

		if (is_null($this->config('moduleKey'))) {
			if ($entity->has($this->config('formKey'))) {
				$customFormId = $entity->{$this->config('formKey')};

				if (isset($customFormId)) {
					$customFormQuery = $CustomForms
						->find('list', ['keyField' => 'id', 'valueField' => 'id'])
						->where([$CustomForms->aliasField('id') => $customFormId]);
				}
			}
		} else {
			$where = [$CustomModules->aliasField('model') => $this->config('model')];
			if ($this->config('behavior')) {
				$where[$CustomModules->aliasField('behavior')] = $this->config('behavior');
			}

			$results = $CustomModules
				->find('all')
				->select([
					$CustomModules->aliasField('id'),
					$CustomModules->aliasField('filter')
				])
				->where($where)
				->first();

			if (!empty($results)) {
				$moduleId = $results->id;
				$filterAlias = $results->filter;

				$customFormQuery = $CustomForms
					->find('list', ['keyField' => 'id', 'valueField' => 'id'])
					->where([$CustomForms->aliasField($this->config('moduleKey')) => $moduleId]);

				if (!empty($filterAlias)) {
					$filterKey = $this->getFilterKey($filterAlias, $this->config('model'));
					if (empty($filterKey)) {
						list($modelplugin, $modelAlias) = explode('.', $filterAlias, 2);
						$filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
					}

					$filterId = $entity->$filterKey;
					$customFormQuery
						->join([
							'table' => $CustomFormsFilters->table(),
							'alias' => $CustomFormsFilters->alias(),
							'conditions' => [
								'OR' => [
									[
										$CustomFormsFilters->aliasField($this->config('formKey') . ' = ') . $CustomForms->aliasField('id'),
										$CustomFormsFilters->aliasField($this->config('filterKey')) => 0
									],
									[
										$CustomFormsFilters->aliasField($this->config('formKey') . ' = ') . $CustomForms->aliasField('id'),
										$CustomFormsFilters->aliasField($this->config('filterKey')) => $filterId
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

			$query = $CustomFormsFields
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
					$CustomFormsFields->aliasField($this->config('formKey') . ' IN') => $customFormIds
				])
				->group([
					$CustomFormsFields->aliasField($this->config('fieldKey'))
				]);
		}

		return $query;
	}

	public function formatEntity(Entity $entity) {
		$values = [];
		if ($entity->has('custom_field_values')) {
			foreach ($entity->custom_field_values as $key => $obj) {
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

		$query = $this->getCustomFieldQuery($entity);

    	$fieldValues = [];	// values of custom field must be in sequence for validation errors to be placed correctly
    	$extraFieldValues = [];	// to hold result for more checkbox values
    	if (!is_null($query)) {
			$customFields = $query->toArray();

			$CustomFieldValues = $this->_table->CustomFieldValues;
			foreach ($customFields as $key => $obj) {
				$customField = $obj->custom_field;

				// only apply for field type store in custom_field_values
				if (in_array($customField->field_type, $this->fieldValueArray)) {
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
							'custom_field' => null // to-do
						];
						$valueEntity = $CustomFieldValues->newEntity($valueData, ['validate' => false]);
						$fieldValues[] = $valueEntity;
					}
				}
			}
		}

		$entity->custom_field_values = $fieldValues;
	}

	public function setupCustomFields(Entity $entity) {
		$model = $this->_table;
		$query = $this->getCustomFieldQuery($entity);

		if (!is_null($query)) {
			$customFields = $query->toArray();

			$order = 0;
			$fieldOrder = [];
			$ignoreFields = ['id', 'modified_user_id', 'modified', 'created_user_id', 'created'];
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
					        	// onPost
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
			foreach ($customFields as $key => $obj) {
				$customField = $obj->custom_field;

				$fieldType = $customField->field_type;
				$fieldName = "custom_".$key."_field";
				$valueClass = strtolower($fieldType) == 'table' || strtolower($fieldType) == 'student_list' ? 'table-full-width' : '';

				$attr = [
					'type' => 'custom_'. strtolower($fieldType),
					'attr' => [
						'label' => $customField->name,
						'fieldKey' => $this->config('fieldKey')
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
				$fieldOrder[++$order] = $field;
			}
			ksort($fieldOrder);
			$model->ControllerAction->setFieldOrder($fieldOrder);
		}
	}
}
