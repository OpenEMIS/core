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
		$customField = [];
		$header = [];
		$InstitutionCustomFormFiltersTable = TableRegistry::get('InstitutionCustomField.InstitutionCustomFormsFilters');

		// Get the custom fields columns
		foreach ($institutionSiteTypes as $key => $name) {
			// Getting the headers
			$institutionCustomFormFilters = $InstitutionCustomFormFiltersTable->find()
				->where([$InstitutionCustomFormFiltersTable->aliasField('institution_custom_filter_id') => $key])
				->contain(['CustomForms', 'CustomForms.CustomFields'])
				->first();
			$customField[$key]['custom_fields'] = [];
			$header[$key] = null;
			if (isset($institutionCustomFormFilters['custom_form']['custom_fields'])) {
				$customField[$key]['custom_fields'] = $institutionCustomFormFilters['custom_form']['custom_fields'];
				foreach ($customField[$key]['custom_fields'] as $field) {
					if ($field->field_type != 'TABLE' && $field->field_type != 'STUDENT_LIST') {
						$header[$key][$field->id] = $field->name;
					}	
				}
				if (!empty($header[$key])) {
					ksort($header[$key]);
				}
			}
			$modelAlias = $this->ControllerAction->getModel($filter)['model'];
			$filterKey = Inflector::underscore(Inflector::singularize($modelAlias)) . '_id';
			$customFieldValueTable = TableRegistry::get('InstitutionCustomField.InstitutionCustomFieldValues');
			$query = $this->find()->where([$this->aliasField($filterKey) => $key]);

			// Getting the values

			

			$sheets[] = [
	    		'name' => __($name),
				'table' => $this,
				'query' => $query,
				'additionalHeader' => $header[$key],
				// 'additionalData' => $values,
	    	];
		}
	}

	/**
	 *	Function to get the custom values for each type of the filter
	 *
	 *	@param Query $query The primary query to run for the spreadsheet
	 *	@param string $filterKey $filter column name
	 *	@param array $customFields Array containing the custom fields for each of the id
	 *	@param Table $customFieldValueTable The table of the customFieldValue for the specified report. 
	 *			E.g. Institution will use InstitutionCustomFieldValue table
	 *	@param Table $customFieldValueOptionsTable The table of the customFieldOptions for the specified report. 
	 *			E.g. Institution will use InstitutionCustomFieldOptions table
	 *	@return array The values of each of the custom fields sorted by the table
	 */
	public function getCustomFieldValues(Query $query, $filterKey, $customField, Table $customFieldValueTable, $customFieldOptionsTable) {
		$customFieldsForeignKey = $customFieldValueTable->association('CustomFields')->foreignKey();
		$customRecordsForeignKey = $customFieldValueTable->association('CustomRecords')->foreignKey();
		$ids = $query->find('list', [
				'keyField' => 'id',
				'valueField' => $filterKey
			])->toArray();
		foreach ($ids as $id => $key) {
			pr($id);
			$fields = $customField[$key]['custom_fields'];
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
						$fieldValue->innerJoin(
								[$customFieldOptionsTable->alias() => $customFieldOptionsTable->table()],
								[$customFieldOptionsTable->aliasField('id').'='.$customFieldValueTable->aliasField('number_value')]
							)
							->select([$customFieldOptionsTable->aliasField('name')]);
						$tmpAnswer = '';
						foreach ($fieldValue->toArray() as $value) {
							$alias = $customFieldOptionsTable->alias();
							if (empty($tmpAnswer)) {
								$tmpAnswer = $value[$alias]['name'];
							} else {
								$tmpAnswer = $tmpAnswer.', '.$value[$alias]['name'];
							}
						}
						if (!empty($tmpAnswer)){
							$answer[] = $tmpAnswer;
						}
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
						}
						break;
				}
			}
			pr($answer);
		}
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
