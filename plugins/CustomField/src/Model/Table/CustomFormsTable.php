<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class CustomFormsTable extends ControllerActionTable
{
    use OptionsTrait;
    const APPLY_TO_ALL_YES = 1;
    const APPLY_TO_ALL_NO = 0;

    private $extra = [
        'fieldClass' => [
            'className' => 'CustomField.CustomFields',
            'joinTable' => 'custom_forms_fields',
            'foreignKey' => 'custom_form_id',
            'targetForeignKey' => 'custom_field_id',
            'through' => 'CustomField.CustomFormsFields',
            'dependent' => true
        ],
        'filterClass' => null,
        'label' => [
            'custom_fields' => 'Custom Fields',
            'add_field' => 'Add Field',
            'fields' => 'Fields'
        ]
    ];
    private $hasFilter = false;

    public function initialize(array $config)
    {
        if (array_key_exists('extra', $config)) {
            $this->extra = array_merge($this->extra, $config['extra']);
        }
        parent::initialize($config);
        $this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
        $this->belongsToMany('CustomFields', $this->extra['fieldClass']);

        if (array_key_exists('filterClass', $this->extra) && !is_null($this->extra['filterClass'])) {
            $this->hasFilter = true;
        }

        if ($this->hasFilter) {
            $this->belongsToMany('CustomFilters', $this->extra['filterClass']);

            $junctionTable = $this->extra['filterClass']['through'];
            $this->CustomFormsFilters = TableRegistry::get($junctionTable);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->has('apply_to_all') && $entity->apply_to_all == self::APPLY_TO_ALL_YES) {
            $customFormIds = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                ->where([
                    $this->aliasField('custom_module_id') => $entity->custom_module_id
                ])
                ->toArray();

            if (!empty($customFormIds)) {
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
            } else {
                $this->log('customFormIds is empty ...', 'debug');
            }
        }
    }

    public function onGetApplyToAll(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $entity->custom_filters = [];

            $entity->is_deletable = true;
            if (!is_null($entity->_matchingData['CustomModules']->filter)) {
                $filter = $entity->_matchingData['CustomModules']->filter;

                $formKey = $this->extra['filterClass']['foreignKey'];
                $filterKey = $this->extra['filterClass']['targetForeignKey'];
                $filterIds = $this->CustomFormsFilters
                    ->find('list', ['keyField' => $filterKey, 'valueField' => $filterKey])
                    ->where([
                        $this->CustomFormsFilters->aliasField($formKey) => $entity->id
                    ])
                    ->toArray();

                if (array_key_exists(0, $filterIds)) {
                    $value = __('Yes');
                    $entity->is_deletable = false;
                } else {
                    $value = __('No');

                    $filters = [];
                    $filterModel = TableRegistry::get($filter);
                    if (!empty($filterIds)) {
                        $filters = $filterModel
                            ->getList()
                            ->where([$filterModel->aliasField('id IN ') => $filterIds])
                            ->toArray();
                    }

                    $entity->custom_filters = $filters;
                }

                return $value;
            }

            return '<i class="fa fa-minus"></i>';
        }
    }

    public function onGetCustomFilters(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            if (!is_null($entity->_matchingData['CustomModules']->filter)) {
                if (sizeof($entity->custom_filters) > 0) {
                    $chosenSelectList = [];
                    foreach ($entity->custom_filters as $value) {
                        $chosenSelectList[] = $value;
                    }
                    return implode(', ', $chosenSelectList);
                }
            }

            return '<i class="fa fa-minus"></i>';
        }
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        if (array_key_exists('remove', $buttons) && !$entity->is_deletable) {
            unset($buttons['remove']);    // remove delete action from the action button
        }

        return $buttons;
    }

    /**
     * Gets the list form fields that are associated with the particular form
     * @param integer custom form ID
     * @return array List of custom fields that are associated with the form
     */
    public function getCustomFormsFields($formId)
    {
        $CustomFormsFields = TableRegistry::get($this->extra['fieldClass']['through']);
        $CustomFields = TableRegistry::get($this->extra['fieldClass']['className']);
        $formKey = $this->extra['fieldClass']['foreignKey'];
        $fieldKey = $this->extra['fieldClass']['targetForeignKey'];

        return $CustomFormsFields
            ->find('all')
            ->select([
                'name' => $CustomFields->aliasField('name'),
                'field_type' => $CustomFields->aliasField('field_type'),
                $fieldKey => $CustomFormsFields->aliasField($fieldKey),
                $formKey => $CustomFormsFields->aliasField($formKey),
                'section' => $CustomFormsFields->aliasField('section'),
                'id' => $CustomFormsFields->aliasField('id')
            ])
            ->innerJoin(
                [$CustomFields->alias() => $CustomFields->table()],
                [
                    $CustomFields->aliasField('id = ') . $CustomFormsFields->aliasField($fieldKey),
                ]
            )
            ->order([$CustomFormsFields->aliasField('order')])
            ->where([$CustomFormsFields->aliasField($formKey) => $formId])
            ->toArray();
    }

    public function onGetCustomOrderFieldElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            // No implementation yet
        } elseif ($action == 'view') {
            $tableHeaders = [__($this->extra['label']['fields']), __('Field Type')];
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
                    $rowData[] = ''; // Field Type
                    $tableCells[] = $rowData;
                    $printSection = false;
                }
                $rowData = [];
                $rowData[] = $obj['name'];
                $rowData[] = $obj['field_type'];
                $tableCells[] = $rowData;
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        } elseif ($action == 'add' || $action == 'edit') {
            $form = $event->subject()->Form;
            $form->unlockField($attr['model'] . '.custom_fields');
            $formKey = $this->extra['fieldClass']['foreignKey'];
            $fieldKey = $this->extra['fieldClass']['targetForeignKey'];

            // Build Questions options
            $moduleQuery = $this->getModuleQuery();
            $moduleOptions = $moduleQuery->toArray();
            $selectedModule = isset($this->request->query['module']) ? $this->request->query['module'] : key($moduleOptions);
            $customModule = $this->CustomModules->get($selectedModule);
            $supportedFieldTypes = $customModule->supported_field_types;

            $Fields = TableRegistry::get($this->extra['fieldClass']['className']);
            $customFieldOptions = $this->CustomFields
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => function ($row) {
                        if ($row->has('code') && !empty($row->code)) {
                            return $row->code . ' - ' . $row->name;
                        } else {
                            return $row->name;
                        }
                    }
                ])
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
                            'field_type' => $obj->field_type,
                            $fieldKey => $obj->{$fieldKey},
                            $formKey => $obj->{$formKey},
                            'section' => $obj->section,
                            'id' => $obj->id
                        ];
                    }
                }
            } elseif ($this->request->is(['post', 'put'])) {
                $requestData = $this->request->data;
                $arraySection = [];
                if (array_key_exists('custom_fields', $requestData[$this->alias()])) {
                    foreach ($requestData[$this->alias()]['custom_fields'] as $key => $obj) {
                        $arrayData = [
                            'name' => $obj['_joinData']['name'],
                            'field_type' => $obj['_joinData']['field_type'],
                            $fieldKey => $obj['id'],
                            $formKey => $obj['_joinData'][$formKey],
                            'section' => $obj['_joinData']['section']
                        ];
                        if (!empty($obj['_joinData']['id'])) {
                            $arrayData['id'] = $obj['_joinData']['id'];
                        }
                        $arrayFields[] = $arrayData;
                        $arraySection[] = $obj['_joinData']['section'];
                    }
                }

                if (array_key_exists('selected_custom_field', $requestData[$this->alias()])) {
                    $fieldId = $requestData[$this->alias()]['selected_custom_field'];
                    if (!empty($fieldId)) {
                        $fieldObj = $Fields->get($fieldId);
                        $sectionName = $entity->section;
                        $arrayFields[] = [
                            'name' => $fieldObj->name,
                            'field_type' => $fieldObj->field_type,
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
            $tableHeaders = [__($this->extra['label']['fields']) , __('Field Type'), ''];
            $tableCells = [];

            $order = 0;
            $sectionName = "";
            $printSection = false;
            foreach ($arrayFields as $key => $obj) {
                $fieldPrefix = $attr['model'] . '.custom_fields.' . $cellCount++;
                $joinDataPrefix = $fieldPrefix . '._joinData';

                $customFieldName = $obj['name'];
                $customFieldType = $obj['field_type'];
                $customFieldId = $obj[$fieldKey];
                $customFormId = $obj[$formKey];
                $customSection = "";
                if (!empty($obj['section'])) {
                    $customSection = $obj['section'];
                }
                if ($sectionName != $customSection) {
                    $sectionName = $customSection;
                    $printSection = true;
                }

                $cellData = "";
                $cellData .= $form->hidden($fieldPrefix.".id", ['value' => $customFieldId]);
                $cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $customFieldName]);
                $cellData .= $form->hidden($joinDataPrefix.".field_type", ['value' => $customFieldType]);
                $cellData .= $form->hidden($joinDataPrefix.".".$formKey, ['value' => $customFormId]);
                $cellData .= $form->hidden($joinDataPrefix.".".$fieldKey, ['value' => $customFieldId]);
                $cellData .= $form->hidden($joinDataPrefix.".order", ['value' => ++$order, 'class' => 'order']);
                $cellData .= $form->hidden($joinDataPrefix.".section", ['value' => $customSection, 'class' => 'section']);
                $form->unlockField($joinDataPrefix.".order");
                $form->unlockField($joinDataPrefix.".section");

                if (isset($obj['id'])) {
                    $cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
                }
                if (! empty($sectionName) && ($printSection)) {
                    $rowData = [];
                    $rowData[] = '<div class="section-header">'.$sectionName.'</div>';
                    $rowData[] = ''; // Field Type
                    $rowData[] = '<button onclick="jsTable.doRemove(this);CustomForm.updateSection();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
                    $rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
                    $printSection = false;
                    $tableCells[] = $rowData;
                }
                $rowData = [];
                $rowData[] = $customFieldName.$cellData;
                $rowData[] = $customFieldType;
                $rowData[] = '<button onclick="jsTable.doRemove(this); $(\'#reload\').click();" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';
                $rowData[] = [$event->subject()->renderElement('OpenEmis.reorder', ['attr' => '']), ['class' => 'sorter rowlink-skip']];
                $tableCells[] = $rowData;

                unset($customFieldOptions[$obj[$fieldKey]]);
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $attr['reorder'] = true;
            $attr['labels'] = $this->extra['label'];

            $customFieldOptions = ['' => '-- '. __($this->extra['label']['add_field']) .' --'] + $customFieldOptions;
            $selectedCustomField = '';    // Set selected custom field to empty
            $this->advancedSelectOptions($customFieldOptions, $selectedCustomField, [
                'message' => '{{label}} - ' . $this->getMessage('CustomForms.notSupport'),
                'callable' => function ($id) use ($Fields, $supportedFieldTypes) {
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

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder(['custom_module_id', 'name', 'description']);

        if ($this->hasFilter) {
            $this->field('apply_to_all', ['after' => 'custom_module_id']);
            $this->field('custom_filters', ['after' => 'apply_to_all']);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $moduleQuery = $this->getModuleQuery();
        $moduleOptions = $moduleQuery->toArray();

        $query->matching('CustomModules');

        if (!empty($moduleOptions)) {
            $selectedModule = $this->queryString('module', $moduleOptions);
            $extra['toolbarButtons']['add']['url']['module'] = $selectedModule;
            $this->advancedSelectOptions($moduleOptions, $selectedModule);

            $query->where([$this->aliasField('custom_module_id') => $selectedModule]);

            //Add controls filter to index page
            $toolbarElements = ['name' => 'CustomField.controls', 'data' => [], 'options' => [], 'order' => 1];

            $extra['elements']['controls'] = $toolbarElements;
            $this->controller->set(compact('moduleOptions'));
        }

        if ($this->hasFilter) {
            $query->contain(['CustomFilters', 'CustomFields']);
        } else {
            $query->contain(['CustomFields']);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        if ($this->hasFilter) {
            $query->contain(['CustomFilters', 'CustomFields']);
        } else {
            $query->contain(['CustomFields']);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['module'] = $entity->custom_module_id;
        $this->request->query['apply_all'] = $this->getApplyToAll($entity);

        $this->setupFields($entity);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['module'] = $entity->custom_module_id;
        $this->request->query['apply_all'] = $this->getApplyToAll($entity);
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        // To handle when delete all subjects
        if (!array_key_exists('custom_fields', $data[$this->alias()])) {
            $data[$this->alias()]['custom_fields'] = [];
        }

        // Required by patchEntity for associated data
        $newOptions = [];
        if ($this->hasFilter) {
            $newOptions['associated'] = ['CustomFilters', 'CustomFields._joinData'];
        } else {
            $newOptions['associated'] = ['CustomFields._joinData'];
        }

        $arrayOptions = $options->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $options->exchangeArray($arrayOptions);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request)
    {
        $moduleQuery = $this->getModuleQuery();
        $moduleOptions = $moduleQuery->toArray();
        $selectedModule = $this->queryString('module', $moduleOptions);
        $this->advancedSelectOptions($moduleOptions, $selectedModule);

        $attr['type'] = 'select';
        $attr['options'] = $moduleOptions;
        $attr['select'] = false;
        $attr['onChangeReload'] = 'changeModule';

        return $attr;
    }

    public function onUpdateFieldApplyToAll(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $applyToAllOptions = $attr['options'];
            $attr['value'] = $applyToAllOptions[$attr['value']];
        }

        return $attr;
    }

    public function onUpdateFieldCustomFilters(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $customModule = $attr['attr']['customModule'];
            $filter = $customModule->filter;
            list($plugin, $modelAlias) = explode('.', $filter, 2);
            $labelText = Inflector::underscore(Inflector::singularize($modelAlias));

            $attr['attr']['label'] = __(Inflector::humanize($labelText));
        } elseif ($action == 'add' || $action == 'edit') {
            $customModule = $attr['attr']['customModule'];
            $selectedModule = $customModule->id;
            $filter = $customModule->filter;
            $entity = $attr['attr']['entity'];

            list($plugin, $modelAlias) = explode('.', $filter, 2);
            $labelText = Inflector::underscore(Inflector::singularize($modelAlias));
            $filterOptions = TableRegistry::get($filter)->getList()->toArray();

            // Logic to remove filter from the list if already in used
            $formKey = $this->extra['filterClass']['foreignKey'];
            $filterKey = $this->extra['filterClass']['targetForeignKey'];

            $filterQuery = $this->CustomFormsFilters
                ->find('list', ['keyField' => $filterKey, 'valueField' => $filterKey])
                ->matching('CustomForms', function ($q) use ($selectedModule) {
                    return $q->where([
                        'CustomForms.custom_module_id' => $selectedModule
                    ]);
                })
                ->where([
                    $this->CustomFormsFilters->aliasField($filterKey.' <> ') => 0
                ]);

            if (isset($entity->id)) {    // edit
                $customFormId = $entity->id;
                $filterQuery->where([
                    $this->CustomFormsFilters->aliasField($formKey.' <> ') => $customFormId
                ]);
            }
            $filterIds = $filterQuery->toArray();

            foreach ($filterOptions as $key => $value) {
                if (array_key_exists($key, $filterIds)) {
                    unset($filterOptions[$key]);
                }
            }
            // End

            $attr['placeholder'] = __('Select ') . __(Inflector::humanize($labelText));
            $attr['options'] = $filterOptions;
            $attr['attr']['label'] = __(Inflector::humanize($labelText));
        }

        return $attr;
    }

    public function addEditOnChangeModule(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
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

    public function addEditOnChangeApplyAll(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
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

    public function getModuleQuery()
    {
        return $this->CustomModules
            ->find('list')
            ->find('visible');
    }

    private function setupFields(Entity $entity)
    {
        $selectedModule = $this->request->query('module');
        $customModule = $this->CustomModules->get($selectedModule);

        $this->setFieldOrder(['custom_module_id', 'name', 'description', 'custom_fields']);
        $this->field('custom_module_id');

        // for model that has filter:
        // If no pages is added before, show apply_to_all = Yes
        // else show apply_to_all = No and Filters

        if ($this->hasFilter) {
            $showFilters = false;

            $where = [$this->aliasField('custom_module_id') => $selectedModule];
            if (isset($entity->id)) {
                $where[$this->aliasField('id <>')] = $entity->id;
            }

            $customFormIds = $this
                ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                ->where($where)
                ->toArray();

            if (!empty($customFormIds)) {
                $formKey = $this->extra['filterClass']['foreignKey'];
                $filterKey = $this->extra['filterClass']['targetForeignKey'];
                $filterResults = $this->CustomFormsFilters
                    ->find()
                    ->where([
                        $this->CustomFormsFilters->aliasField($formKey.' IN ') => $customFormIds,
                        $this->CustomFormsFilters->aliasField($filterKey) => 0
                    ])
                    ->all();

                if (!$filterResults->isEmpty()) {
                    $showFilters = true;
                }
            }

            $applyToAllOptions = $this->getSelectOptions('general.yesno');
            $inputOptions = [
                'type' => 'readonly',
                'options' => $applyToAllOptions,
                'after' => 'custom_module_id'
            ];

            if ($showFilters) {
                $inputOptions['value'] = self::APPLY_TO_ALL_NO;
                $inputOptions['attr']['value'] = $applyToAllOptions[self::APPLY_TO_ALL_NO];

                $this->field('apply_to_all', $inputOptions);
                $this->field('custom_filters', [
                    'type' => 'chosenSelect',
                    'placeholder' => __('Select Filters'),
                    'attr' => ['customModule' => $customModule, 'entity' => $entity],
                    'after' => 'apply_to_all'
                ]);
            } else {
                $inputOptions['value'] = self::APPLY_TO_ALL_YES;
                $inputOptions['attr']['value'] = $applyToAllOptions[self::APPLY_TO_ALL_YES];

                $this->field('apply_to_all', $inputOptions);
            }
        }

        $this->field('custom_fields', [
            'type' => 'custom_order_field',
            'valueClass' => 'table-full-width',
            'after' => 'description'
        ]);
    }

    private function getFilterAlias($selectedModule = null)
    {
        if (!is_null($selectedModule)) {
            $customModule = $this->CustomModules->get($selectedModule);
            return $customModule->filter;
        }

        return null;
    }

    private function getApplyToAll(Entity $entity)
    {
        $filterAlias = $this->getFilterAlias($entity->custom_module_id);

        if ($this->hasFilter) {
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
