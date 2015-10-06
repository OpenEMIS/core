<?php
namespace Report\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\ORM\Table;

class CustomFieldListBehavior extends Behavior {

	/**
	 *	Function to get the filter of the given model
	 *
	 *	@param string $model The code of of the custom module
	 *	@return string Filter of the custom module
	 */
	public function getFilter($model) {
		$CustomModuleTable = TableRegistry::get('CustomField.CustomModules');
		$filter = $CustomModuleTable
			->find()
			->where([$CustomModuleTable->aliasField('model')=>$model])
			->first()
			->filter
			;
		return $filter;
	}

	/**
	 *	Function to get the filter key from the filter specified
	 *
	 *	@param String $model The filter provided by the custom module
	 *	@return String The filter column name
	 */
	public function getFilterKey($model) {
		$split = explode('.', $model);
		$plugin = null;
		$modelClass = $model;
		if (count($split) > 1) {
			$plugin = $split[0];
			$modelClass = $split[1];
		}
		$filterKey = Inflector::underscore(Inflector::singularize($modelClass)) . '_id';
		return $filterKey;
	}

	/**
	 *	Function to get the custom values for each type of the filter
	 *
	 *	@param Table $table The model for which the custom field values is tagged to
	 *	@param string $filterKey The filter column name
	 *	@param int $filterValue The id value of the filterKey
	 *	@param array $customFields Array containing the custom fields for each of the $filterKeys specified
	 *	@param Table $customFieldValueTable The table of the customFieldValue for the specified report. 
	 *			E.g. Institution will use InstitutionCustomFieldValue table
	 *	@return array The values of each of the custom fields value base on the filter value specified
	 */
	public function getCustomFieldValues(Table $table, $filterKey, $filterValue, $customField, Table $customFieldValueTable) {
		$customFieldsForeignKey = $customFieldValueTable->CustomFields->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->CustomRecords->foreignKey();
		$ids = $table
			->find('list', [
				'keyField' => 'id',
				'valueField' => $filterKey
			])
			->where([$table->aliasField($filterKey) => $filterValue])
			->toArray();

		$consolidatedValues = [];
		foreach ($ids as $id => $key) {
			$fields = $customField;
			$answer = [];
			foreach ($fields as $field) {
				$fieldValue = $customFieldValueTable->find()
							->where([
								$customFieldValueTable->aliasField($customRecordsForeignKey) => $id,
								$customFieldValueTable->aliasField($customFieldsForeignKey) => $field->id,
							]);
				$fieldType = $field->field_type;
				switch ($fieldType) {
					case 'CHECKBOX':
					case 'DROPDOWN':
						$CustomFieldOptionsTable = $customFieldValueTable->CustomFields->CustomFieldOptions;
						$fieldValue->innerJoin(
								[$CustomFieldOptionsTable->alias() => $CustomFieldOptionsTable->table()],
								[$CustomFieldOptionsTable->aliasField('id').'='.$customFieldValueTable->aliasField('number_value')]
							)
							->select([$CustomFieldOptionsTable->aliasField('name')]);
						$tmpAnswer = '';
						$alias = $CustomFieldOptionsTable->alias();
						foreach ($fieldValue->toArray() as $value) {
							if (empty($tmpAnswer)) {
								$tmpAnswer = $value[$alias]['name'];
							} else {
								$tmpAnswer = $tmpAnswer.', '.$value[$alias]['name'];
							}
						}
						$answer[] = $tmpAnswer;
						break;

					default:
						$value = $fieldValue->first();
						if (!empty($value)) {
							switch ($fieldType) {
								case 'TABLE':
								case 'STUDENT_LIST':
									break;

								case 'DATE':
									$answer[] = $value->date_value;
									break;

								case 'TIME':
									$answer[] = $value->time_value;
									break;

								case 'TEXTAREA':
									$answer[] = $value->textarea_value;
									break;

								case 'NUMBER':
									$answer[] = $value->number_value;
									break;

								case 'TEXT':
									$answer[] = $value->text_value;
									break;
							}
						} else {
							switch ($fieldType) {
								case 'TABLE':
								case 'STUDENT_LIST':
									break;
								default:
									$answer[] = '';
									break;
							}
						}
						break;
				}
			}
			$consolidatedValues[] = $answer;
		}
		return $consolidatedValues;
	}
}
