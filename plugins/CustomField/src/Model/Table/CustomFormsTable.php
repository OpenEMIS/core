<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class CustomFormsTable extends AppTable {
	use OptionsTrait;
	const APPLY_TO_ALL_YES = 1;
	const APPLY_TO_ALL_NO = 0;

	private $extra = [
		'filterClass' => [
			'className' => 'FieldOption.FieldOptionValues',
			'joinTable' => 'custom_forms_filters',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_filter_id',
			'through' => 'CustomField.CustomFormsFilters',
			'dependent' => true
		],
		'fieldClass' => [
			'className' => 'CustomField.CustomFields',
			'joinTable' => 'custom_forms_fields',
			'foreignKey' => 'custom_form_id',
			'targetForeignKey' => 'custom_field_id',
			'through' => 'CustomField.CustomFormsFields',
			'dependent' => true
		],
		'label' => [
			'custom_fields' => 'Custom Fields',
			'add_field' => 'Add Field',
			'fields' => 'Fields'
		]
	];

	public function initialize(array $config) {
		if (array_key_exists('extra', $config)) {
			$this->extra = array_merge($this->extra, $config['extra']);
		}		
		parent::initialize($config);
		$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
		$this->belongsToMany('CustomFilters', $this->extra['filterClass']);
		$this->belongsToMany('CustomFields', $this->extra['fieldClass']);
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if ($entity->has('apply_to_all') && $entity->apply_to_all == self::APPLY_TO_ALL_YES) {
			$customFormIds = $this
				->find('list', ['keyField' => 'id', 'valueField' => 'id'])
				->where([
					$this->aliasField('custom_module_id') => $entity->custom_module_id
				])
				->toArray();

			$CustomFormsFilters = TableRegistry::get($this->extra['filterClass']['through']);
			$CustomFormsFilters->deleteAll([
				'OR' => [
					[
						$CustomFormsFilters->aliasField($this->extra['filterClass']['foreignKey'] . ' IN') => $customFormIds,
						$CustomFormsFilters->aliasField($this->extra['filterClass']['targetForeignKey']) => 0
					],
					$CustomFormsFilters->aliasField($this->extra['filterClass']['foreignKey']) => $entity->id
				]
			]);

			$filterData = [
				$this->extra['filterClass']['foreignKey'] => $entity->id,
				$this->extra['filterClass']['targetForeignKey'] => 0
			];
			$filterEntity = $CustomFormsFilters->newEntity($filterData);

			if ($CustomFormsFilters->save($filterEntity)) {
			} else {
				$CustomFormsFilters->log($filterEntity->errors(), 'debug');
			}
		}
	}

	public function onGetApplyToAll(Event $event, Entity $entity) {
		$selectedApplyToAll = $this->getApplyToAll($entity);

		if (!is_null($selectedApplyToAll)) {
			$applyToAllOptions = $this->getSelectOptions('general.yesno');
			return $applyToAllOptions[$selectedApplyToAll];
		} else {
			return '<i class="fa fa-minus"></i>';
		}
	}

    /**
     * Gets the list form fields that are associated with the particular form
     * @param integer custom form ID
     * @return array List of custom fields that are associated with the form
     */
	public function getCustomFormsFields($formId){
		$CustomFormsFields = TableRegistry::get($this->extra['fieldClass']['through']);
		$CustomFields = TableRegistry::get($this->extra['fieldClass']['className']);
		$formKey = $this->extra['fieldClass']['foreignKey'];
		$fieldKey = $this->extra['fieldClass']['targetForeignKey'];

		return $CustomFormsFields
			->find('all')
			->select([
				'name' => $CustomFields->aliasField('name'),
				$fieldKey => $CustomFormsFields->aliasField($fieldKey),
				$formKey => $CustomFormsFields->aliasField($formKey),
				'section' => $CustomFormsFields->aliasField('section'),
				'id' => $CustomFormsFields->aliasField('id')
			])
			->innerJoin([$CustomFields->alias() => $CustomFields->table()],
				[
					$CustomFields->aliasField('id = ') . $CustomFormsFields->aliasField($fieldKey),
				]
			)
			->order([$CustomFormsFields->aliasField('order')])
			->where([$CustomFormsFields->aliasField($formKey) => $formId])
			->toArray();
	}

	public function onGetCustomOrderFieldElement(Event $event, $action, $entity, $attr, $options=[]) {
		if ($action == 'index') {
			// No implementation yet
		} else if ($action == 'view') {
			$tableHeaders = [__($this->extra['label']['fields'])];
			$tableCells = [];

			$customFormId = $entity->id;
			$customFields = $this->getCustomFormsFields($customFormId);

			$sectionName = "";
			$printSection = false;
			foreach ($customFields as $key => $obj) {
				if (!empty($obj['section']) && $obj['section'] != $sectionName) {
					$sectionName = $obj['section'];
					$printSection = true;
				}
				if (!empty($sectionName) && ($printSection)) {
					$rowData = [];
					$rowData[] = '<div class="section-header">'.$sectionName.'</div>';
					$tableCells[] = $rowData;
					$printSection = false;
				}
				$rowData = [];
				$rowData[] = $obj['name'];
				$tableCells[] = $rowData;
			}

			$attr['tableHeaders'] = $tableHeaders;
	    	$attr['tableCells'] = $tableCells;
		} else if ($action == 'add' || $action == 'edit') {
			$form = $event->subject()->Form;
			$formKey = $this->extra['fieldClass']['foreignKey'];
			$fieldKey = $this->extra['fieldClass']['targetForeignKey'];

			// Build Questions options
			$moduleQuery = $this->getModuleQuery();
			$moduleOptions = $moduleQuery->toArray();
			$selectedModule = isset($this->request->query['module']) ? $this->request->query['module'] : key($moduleOptions);
			$customModule = $this->CustomModules->get($selectedModule);
			$supportedFieldTypes = explode(",", $customModule->supported_field_types);

			$Fields = TableRegistry::get($this->extra['fieldClass']['className']);
			$customFieldOptions = $this->CustomFields
				->find('list')
				->toArray();

			$arrayFields = [];
			// Showing the list of the questions that are already added
			if ($this->request->is(['get'])) {
				// edit
				if (isset($entity->id)) {
					$customFormId = $entity->id;
					$customFields = $this->getCustomFormsFields($customFormId);

					foreach ($customFields as $key => $obj) {
						$arrayFields[] = [
							'name' => $obj->name,
							$fieldKey => $obj->{$fieldKey},
							$formKey => $obj->{$formKey},
							'section' => $obj->section,
							'id' => $obj->id
						];
					}
				}
			} else if ($this->request->is(['post', 'put'])) {
				$requestData = $this->request->data;
				$arraySection = [];
				if (array_key_exists('custom_fields', $requestData[$this->alias()])) {
					foreach ($requestData[$this->alias()]['custom_fields'] as $key => $obj) {
						$arrayData = [
							'name' => $obj['_joinData']['name'],
							$fieldKey => $obj['id'],
							$formKey => $obj['_joinData'][$formKey],
							'section' => $obj['_joinData']['section']
						];
						if(!empty($obj['_joinData']['id'])) {
							$arrayData['id'] = $obj['_joinData']['id'];
						}
						$arrayFields[] = $arrayData;
						$arraySection[] = $obj['_joinData']['section'];
					}
				}

				if (array_key_exists('selected_custom_field', $requestData[$this->alias()])) {
					$fieldId = $requestData[$this->alias()]['selected_custom_field'];
					if(!empty($fieldId)){
						$fieldObj = $Fields->get($fieldId);
						$sectionName = $entity->section;
						$arrayFields[] = [
							'name' => $fieldObj->name,
							$fieldKey => $fieldObj->id,
							$formKey => $entity->id,
							'section' => $sectionName
						];
					}
					// To be implemented in the future (To add questions to the specified section)
					// if(empty($sectionName)){
					// 	array_unshift($arrayQuestions, [
					// 		'name' => $questionObj->name,
					// 		'survey_question_id' => $questionObj->id,
					// 		'survey_form_id' => $entity->id,
					// 		'section' => $sectionName,
					// 	]);
					// } else {
					// 	$arrayKeys = array_keys($arraySection, $sectionName);
					// 	$sectionCounter = max($arrayKeys) + 1;
					// 	$res = [];
					// 	$res[] = array_slice($arrayQuestions, 0, $sectionCounter, true);
					// 	$res[] = [
					// 				'name' => $questionObj->name,
					// 				'survey_question_id' => $questionObj->id,
					// 				'survey_form_id' => $entity->id,
					// 				'section' => $sectionName,
					// 			];
					// 	$res[] = array_slice($arrayQuestions, $sectionCounter, count($arrayQuestions) - 1, true) ;
					// 	$arrayQuestions = $res;
					// }
				}
			}

			$cellCount = 0;
			$tableHeaders = [__($this->extra['label']['fields']) , ''];
			$tableCells = [];

			$order = 0;
			$sectionName = "";
			$printSection = false;
			foreach ($arrayFields as $key => $obj) {
				$fieldPrefix = $attr['model'] . '.custom_fields.' . $cellCount++;
				$joinDataPrefix = $fieldPrefix . '._joinData';

				$customFieldName = $obj['name'];
				$customFieldId = $obj[$fieldKey];
				$customFormId = $obj[$formKey];
				$customSection = "";
				if(!empty($obj['section'])){
					$customSection = $obj['section'];
				}
				if($sectionName != $customSection){
					$sectionName = $customSection;
					$printSection = true;
				}

				$cellData = "";
				$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $customFieldId]);
				$cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $customFieldName]);
				$cellData .= $form->hidden($joinDataPrefix.".".$formKey, ['value' => $customFormId]);
				$cellData .= $form->hidden($joinDataPrefix.".".$fieldKey, ['value' => $customFieldId]);
				$cellData .= $form->hidden($joinDataPrefix.".order", ['value' => ++$order, 'class' => 'order']);
				$cellData .= $form->hidden($joinDataPrefix.".section", ['value' => $customSection, 'class' => 'section']);
				
				if (isset($obj['id'])) {
					$cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
				}
				if (! empty($sectionName) && ($printSection)) {
					$rowData = [];
					$rowData[] = '<div class="section-header">'.$sectionName.'</div>';
					$rowData[] = '<button onclick="jsTable.doRemove(this);CustomForm.updateSection();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
					$rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
					$printSection = false;
					$tableCells[] = $rowData;
				} 
				$rowData = [];
				$rowData[] = $customFieldName.$cellData;
				$rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
				$rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
				$tableCells[] = $rowData;

				unset($customFieldOptions[$obj[$fieldKey]]);
			}

			$attr['tableHeaders'] = $tableHeaders;
    		$attr['tableCells'] = $tableCells;
    		$attr['reorder'] = true;
    		$attr['labels'] = $this->extra['label'];

    		$customFieldOptions = ['' => '-- '.$this->extra['label']['add_field'].' --'] + $customFieldOptions;
			$selectedCustomField = '';	// Set selected custom field to empty
			$this->advancedSelectOptions($customFieldOptions, $selectedCustomField, [
				'message' => '{{label}} - ' . $this->getMessage('CustomForms.notSupport'),
				'callable' => function($id) use ($Fields, $supportedFieldTypes) {
					if ($id == '') {
						// Skip checking for -- Add Question --
						return 1;
					} else {
						$fieldType = $Fields->get($id)->field_type;
						if (in_array($fieldType, $supportedFieldTypes)) {
							return 1;
						} else {
							// field type not support for this module
							return 0;
						}
					}
				}
			]);
    		ksort($customFieldOptions);
    		$attr['options'] = $customFieldOptions;
		}

		return $event->subject()->renderElement('CustomField.form_fields', ['attr' => $attr]);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupFields();
		$this->ControllerAction->field('custom_fields', ['visible' => false]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$moduleQuery = $this->getModuleQuery();
		$moduleOptions = $moduleQuery->toArray();

		if (!empty($moduleOptions)) {
			$selectedModule = $this->queryString('module', $moduleOptions);
			$this->advancedSelectOptions($moduleOptions, $selectedModule);

			$query->where([$this->aliasField('custom_module_id') => $selectedModule]);

			//Add controls filter to index page
			$toolbarElements = [
	            ['name' => 'CustomField.controls', 'data' => [], 'options' => []]
	        ];
	        $this->controller->set(compact('toolbarElements', 'moduleOptions'));
		}

        $query->contain(['CustomFilters', 'CustomFields']);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['CustomFilters', 'CustomFields']);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->request->query['module'] = $entity->custom_module_id;
		$this->request->query['apply_all'] = $this->getApplyToAll($entity);

		$this->setupFields($entity);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['module'] = $entity->custom_module_id;
		$this->request->query['apply_all'] = $this->getApplyToAll($entity);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// To handle when delete all subjects
		if (!array_key_exists('custom_fields', $data[$this->alias()])) {
			$data[$this->alias()]['custom_fields'] = [];
		}

		// Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = ['CustomFields._joinData'];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request) {
		$moduleQuery = $this->getModuleQuery();
		$moduleOptions = $moduleQuery->toArray();
		$selectedModule = $this->queryString('module', $moduleOptions);
		$this->advancedSelectOptions($moduleOptions, $selectedModule);

		$attr['type'] = 'select';
		$attr['options'] = $moduleOptions;
		$attr['onChangeReload'] = 'changeModule';

		return $attr;
	}

	public function onUpdateFieldApplyToAll(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			// default hide
			$attr['visible'] = false;
			$attr['type'] = 'hidden';
			$attr['value'] = 0;

			$selectedModule = $request->query('module');
			$filterAlias = $this->getFilterAlias($selectedModule);

			if (!empty($filterAlias)) {
				$applyToAllOptions = $this->getSelectOptions('general.yesno');
				$selectedApplyToAll = $this->queryString('apply_all', $applyToAllOptions);
				$this->advancedSelectOptions($applyToAllOptions, $selectedApplyToAll);

				// show selection if the module has filter
				$attr['visible'] = true;
				$attr['type'] = 'select';
				$attr['options'] = $applyToAllOptions;
				$attr['onChangeReload'] = 'changeApplyAll';
			}
		}

		return $attr;
	}

	public function onUpdateFieldCustomFilters(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view' || $action == 'add' || $action == 'edit') {
			// default hide
			$attr['visible'] = false;
			$attr['type'] = 'hidden';
			$attr['value'] = 0;

			$selectedModule = $request->query('module');
			$filterAlias = $this->getFilterAlias($selectedModule);

			if (!empty($filterAlias)) {
				$selectedApplyToAll = $request->query('apply_all');

				if ($selectedApplyToAll == self::APPLY_TO_ALL_NO) {
					list($plugin, $modelAlias) = explode('.', $filterAlias, 2);
					$labelText = Inflector::underscore(Inflector::singularize($modelAlias));
					$filterOptions = TableRegistry::get($filterAlias)->getList()->toArray();

					// show selection if the module has filter and not apply to all
					$attr['visible'] = true;
					$attr['type'] = 'chosenSelect';
					$attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
					$attr['options'] = $filterOptions;
					$attr['attr']['label'] = __(Inflector::humanize($labelText));
				}
			}
		}

		return $attr;
	}

	public function addEditOnChangeModule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['module']);
		unset($request->query['apply_all']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('custom_module_id', $request->data[$this->alias()])) {
					$this->request->query['module'] = $request->data[$this->alias()]['custom_module_id'];
				}
			}
		}
	}

	public function addEditOnChangeApplyAll(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['apply_all']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('apply_to_all', $request->data[$this->alias()])) {
					$this->request->query['apply_all'] = $request->data[$this->alias()]['apply_to_all'];
				}
			}
		}
	}

	public function getModuleQuery() {
		return $this->CustomModules
			->find('list')
			->find('visible');
	}

	private function setupFields(Entity $entity=null) {
		$selectedModule = $this->request->query('module');
		$filterAlias = $this->getFilterAlias($selectedModule);

		$fieldOrder = [];
		$this->ControllerAction->field('custom_module_id');
		$fieldOrder[] = 'custom_module_id';

		if (!empty($filterAlias)) {
			$this->ControllerAction->field('apply_to_all');
			$this->ControllerAction->field('custom_filters', [
				'type' => 'chosenSelect',
				'placeholder' => __('Select Filters')
			]);

			$fieldOrder[] = 'apply_to_all';
			$fieldOrder[] = 'custom_filters';
		}
		$this->ControllerAction->field('custom_fields', [
			'type' => 'custom_order_field',
			'valueClass' => 'table-full-width'
		]);

		$fieldOrder[] = 'name';
		$fieldOrder[] = 'description';
		$fieldOrder[] = 'custom_fields';

		$this->ControllerAction->setFieldOrder($fieldOrder);
	}

	private function getFilterAlias($selectedModule=null) {
		if (!is_null($selectedModule)) {
			$customModule = $this->CustomModules->get($selectedModule);
			return $customModule->filter;
		}

		return null;
	}

	private function getApplyToAll(Entity $entity) {
		$filterAlias = $this->getFilterAlias($entity->custom_module_id);

		if (!empty($filterAlias)) {
			$CustomFormsFilters = TableRegistry::get($this->extra['filterClass']['through']);
			$results = $CustomFormsFilters
				->find()
				->where([
					$CustomFormsFilters->aliasField($this->extra['filterClass']['foreignKey']) => $entity->id,
					$CustomFormsFilters->aliasField($this->extra['filterClass']['targetForeignKey']) => 0
				])
				->all();

			if ($results->isEmpty()) {
				return self::APPLY_TO_ALL_NO;
			} else {
				return self::APPLY_TO_ALL_YES;
			}
		}

		return null;
	}
}
