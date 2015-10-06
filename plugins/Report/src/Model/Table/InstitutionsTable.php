<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class InstitutionsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_sites');
		parent::initialize($config);
		
		$this->belongsTo('InstitutionSiteLocalities', 		['className' => 'Institution.Localities']);
		$this->belongsTo('InstitutionSiteTypes', 			['className' => 'Institution.Types']);
		$this->belongsTo('InstitutionSiteOwnerships', 		['className' => 'Institution.Ownerships']);
		$this->belongsTo('InstitutionSiteStatuses', 		['className' => 'Institution.Statuses']);
		$this->belongsTo('InstitutionSiteSectors', 			['className' => 'Institution.Sectors']);
		$this->belongsTo('InstitutionSiteProviders', 		['className' => 'Institution.Providers']);
		$this->belongsTo('InstitutionSiteGenders', 			['className' => 'Institution.Genders']);
		$this->belongsTo('Areas', 							['className' => 'Area.Areas']);
		$this->belongsTo('AreaAdministratives', 			['className' => 'Area.AreaAdministratives']);
		
		$this->addBehavior('Excel', ['excludes' => ['security_group_id', 'institution_site_type_id'], 'pages' => false]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onGetReportName(Event $event, ArrayObject $data) {
		return __('Overview');
	}

	public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$filter = $this->getFilter('Institution.Institutions');
		$institutionSiteTypes = $this->getInstitutionType($filter);
		$header = [];
		$InstitutionCustomFormFiltersTable = TableRegistry::get('InstitutionCustomField.InstitutionCustomFormsFilters');
		$modelAlias = $this->getModel($filter)['model'];
		$filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
		// Get the custom fields columns
		foreach ($institutionSiteTypes as $key => $name) {
			// Getting the headers
			$institutionCustomFormFilters = $InstitutionCustomFormFiltersTable->find()
				->where([$InstitutionCustomFormFiltersTable->aliasField('institution_custom_filter_id') => $key])
				->contain(['CustomForms', 'CustomForms.CustomFields'])
				->first();
			$customField = [];
			$header[$key] = null;
			if (isset($institutionCustomFormFilters['custom_form']['custom_fields'])) {
				$customField = $institutionCustomFormFilters['custom_form']['custom_fields'];
				foreach ($customField as $field) {
					if ($field->field_type != 'TABLE' && $field->field_type != 'STUDENT_LIST') {
						$header[$key][$field->id] = $field->name;
					}	
				}
				if (!empty($header[$key])) {
					ksort($header[$key]);
				}
			}

			$customFieldValueTable = TableRegistry::get('InstitutionCustomField.InstitutionCustomFieldValues');
			$query = $this->find()->where([$this->aliasField($filterKey) => $key]);
			$data = $this->getCustomFieldValues($query, $filterKey, $customField, $customFieldValueTable);
			$sheets[] = [
	    		'name' => __($name),
				'table' => $this,
				'query' => $this->find()->where([$this->aliasField($filterKey) => $key]),
				'additionalHeader' => $header[$key],
				'additionalData' => $data,
	    	];
		}
	}

	/**
	 *	Controller action component getModel function
	 *
	 */
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

	/**
	 *	Function to get the custom values for each type of the filter
	 *
	 *	@param Query $query The primary query to run for the spreadsheet
	 *	@param string $filterKey $filter column name
	 *	@param array $customFields Array containing the custom fields for each of the $filterKeys specified
	 *	@param Table $customFieldValueTable The table of the customFieldValue for the specified report. 
	 *			E.g. Institution will use InstitutionCustomFieldValue table
	 *	@return array The values of each of the custom fields sorted by the table
	 */
	public function getCustomFieldValues(Query $query, $filterKey, $customField, Table $customFieldValueTable) {
		$customFieldsForeignKey = $customFieldValueTable->association('CustomFields')->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->association('CustomRecords')->foreignKey();
		$ids = $query->find('list', [
				'keyField' => 'id',
				'valueField' => $filterKey
			])->toArray();

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
	 *	Function to get the institution site type base on the custom field filter specified
	 *
	 *	@param string $filter custom field filter
	 *	@return array The list of institution site types
	 */
	public function getInstitutionType($filter) {
		// Getting the institution type as the sheet name
		$types = TableRegistry::get($filter)->getList()->toArray();
		return $types;
	}
}
