<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use Survey\Model\Table\SurveyFormsTable as SurveyForms;

class InstitutionSurveysTable extends ControllerActionTable
{
    use OptionsTrait;
    use MessagesTrait;

    // Default Status
    const EXPIRED = -1;
    const IS_MANDATORY = 1;

    public $module = 'Institution.Institutions';
    public $attachWorkflow = true;  // indicate whether the model require workflow
    public $hasWorkflow = false;    // indicate whether workflow is setup

    public $openStatusId = null;
    public $closedStatusId = null;
    
    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->addBehavior('Survey.Survey', [
            'module' => $this->module
        ]);
        $this->addBehavior('CustomField.Record', [
            'tabSection' => true,
            'moduleKey' => null,
            'fieldKey' => 'survey_question_id',
            'tableColumnKey' => 'survey_table_column_id',
            'tableRowKey' => 'survey_table_row_id',
            'fieldClass' => ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id'],
            'formKey' => 'survey_form_id',
            // 'filterKey' => 'custom_filter_id',
            'formClass' => ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id'],
            'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
            // 'formFilterClass' => ['className' => 'CustomField.CustomFormsFilters'],
            'recordKey' => 'institution_survey_id',
            'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true,]
        ]);
        $this->addBehavior('Excel', ['pages' => ['view']]);
        $this->addBehavior('AcademicPeriod.AcademicPeriod');
        $this->addBehavior('Import.ImportLink');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getFilterOptions'] = 'getWorkflowFilterOptions';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        $events['Workflow.beforeTransition'] = 'workflowBeforeTransition';
        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $query
            ->select(['code' => 'Institutions.code', 'description' => 'SurveyForms.description', 'area_id' => 'Areas.name', 'area_administrative_id' => 'AreaAdministratives.name'])
            ->contain(['Institutions.Areas', 'Institutions.AreaAdministratives']);
    }

    public function deleteAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $broadcaster = $this;
        $listeners[] = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');

        if (!empty($listeners)) {
            $this->dispatchEventToModels('Model.InstitutionSurveys.afterDelete', [$entity], $broadcaster, $listeners);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        // To update to this code when upgrade server to PHP 5.5 and above
        // unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

        foreach ($fields as $key => $field) {
            if ($field['field'] == 'institution_id') {
                unset($fields[$key]);
                break;
            }
        }

        $fields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'InstitutionSurveys.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'SurveyForms.description',
            'field' => 'description',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Institutions.area_id',
            'field' => 'area_id',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Institutions.area_administrative_id',
            'field' => 'area_administrative_id',
            'type' => 'string',
            'label' => '',
        ];
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $tabSection = null;
        $newData = [];
        $conditions = [];
        // check if survey exists any tab section
        if (isset($this->request->query['tab_section'])) {
            $tabSection = $this->request->query['tab_section'];
        }
        $SurveyRules = TableRegistry::get('Survey.SurveyRules');
        $SurveyFormQuestions = TableRegistry::get('Survey.SurveyFormsQuestions');
        $surveyFormId = $data[$this->alias()]['survey_form_id'];
        $rules = $SurveyRules
            ->find('SurveyRulesList', ['survey_form_id' => $surveyFormId])
            ->innerJoin(
                [$SurveyFormQuestions->alias() => $SurveyFormQuestions->table()], 
                [$SurveyFormQuestions->aliasField('survey_question_id = ') . $SurveyRules->aliasField('survey_question_id')]
            );
        // get all the survey rules by survey section, if any
        if ($tabSection) {
            $conditions[] = $rules->newExpr('REPLACE(' . $SurveyFormQuestions->aliasField('section') . ', " ", "-" ) = "'.$tabSection.'"');
        }
        $rules = $rules
            ->where($conditions)
            ->toArray();
        if (!empty($rules)) {
            foreach ($data[$this->alias()]['custom_field_values'] as $customFieldValueKey => $customFieldValue) {
                $newData[$customFieldValue['survey_question_id']] = $customFieldValue;
                $newData[$customFieldValue['survey_question_id']]['dataKey'] = $customFieldValueKey;
            }
            foreach ($rules as $key => $rule) {
                foreach ($rule as $supportFieldKey => $options) {
                    $supportQuestionOptions = json_decode($options);
                    if (isset($newData[$supportFieldKey])) {
                        $userSelectedOption = $newData[$supportFieldKey]['number_value'];
                        if (!(in_array($userSelectedOption, $supportQuestionOptions)) && $newData[$key]['mandatory'] == 1) {
                            $dataAliasKey = $newData[$key]['dataKey'];
                            $data[$this->alias()]['custom_field_values'][$dataAliasKey]['mandatory'] = 0;
                        }
                    }
                }
            }
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $broadcaster = $this;
        $listeners = [];
        $listeners[] = TableRegistry::get('Student.StudentSurveys');
        $listeners[] = TableRegistry::get('InstitutionRepeater.RepeaterSurveys');
        $listeners[] = TableRegistry::get('Institution.InstitutionSurveyTableCells');
        if (!empty($listeners)) {
            $this->dispatchEventToModels('Model.InstitutionSurveys.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        $errors = $entity->errors();

        $fileErrors = [];
        $session = $this->request->session();
        $sessionErrors = $this->registryAlias().'.parseFileError';

        if ($session->check($sessionErrors)) {
            $fileErrors = $session->read($sessionErrors);
        }

        if (empty($errors) && empty($fileErrors)) {
            // redirect only when no errors
            $event->stopPropagation();
            return $this->controller->redirect($this->url('edit'));
        }
    }

    public function getWorkflowFilterOptions(Event $event, array $extra = null)
    {
        $CustomModules = $this->SurveyForms->CustomModules;
        $module = $this->module;
        $surveyList = $this->SurveyForms
            ->find('list')
            ->matching('CustomModules', function ($q) use ($CustomModules, $module) {
                return $q->where([$CustomModules->aliasField('model') => $module]);
            })
            ->toArray();

        if (is_null($extra) || empty($extra['institution_id'])) {
            // used by WorkflowTable, and it will not pass back $extra, to return the whole list
            return $surveyList;
        } else {
            // used by WorkflowBehavior, and it will pass back $extra with the institution_id to read from
            $institutionId = $extra['institution_id'];

            if (!is_null($institutionId)) {
                $AcademicPeriods = $this->AcademicPeriods;
                $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
                $SurveyStatuses = $this->SurveyForms->SurveyStatuses;
                $SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;
                $institutionTypeId = $this->Institutions->get($institutionId)->institution_type_id;
                $todayDate = date("Y-m-d");
                $list = [];

                foreach ($surveyList as $key => $value) {
                    $surveyFormId = $key;

                    // check if survey form filter type matches
                    $institutionFilterCount = $SurveyFormsFilters
                        ->find()
                        ->where([
                            'AND' => [
                                [$SurveyFormsFilters->aliasField('survey_form_id') => $surveyFormId],
                                [
                                    'OR' => [
                                        [$SurveyFormsFilters->aliasField('survey_filter_id') => $institutionTypeId],
                                        [$SurveyFormsFilters->aliasField('survey_filter_id') => SurveyForms::ALL_CUSTOM_FILER]
                                    ]
                                ]
                            ]
                        ])
                        ->count();

                    // if filter type matches, check if the status is active
                    if ($institutionFilterCount > 0) {
                        $activeSurveyCount = $SurveyStatusPeriods
                            ->find()
                            ->matching($AcademicPeriods->alias())
                            ->matching($SurveyStatuses->alias(), function ($q) use ($SurveyStatuses, $surveyFormId, $todayDate) {
                                return $q
                                    ->where([
                                        $SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
                                        $SurveyStatuses->aliasField('date_disabled >=') => $todayDate
                                    ]);
                            })
                            ->count();
                    } else {
                        $activeSurveyCount = 0;
                    }

                    // update the filter list if their is existing active surveys for the institution by institution type
                    if ($activeSurveyCount > 0) {
                        $list[$key] = $value;
                    }
                }
            }

            return $list;
        }
    }

    public function triggerBuildSurveyRecordsShell($params)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake Survey ' . implode(',', $params);
        $logs = ROOT . DS . 'logs' . DS . 'survey.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('survey_form') && $entity->survey_form->has('description')) {
            $value = $entity->survey_form->description;
        }

        return $value;
    }

    public function onGetLastModified(Event $event, Entity $entity)
    {
        if (is_null($entity->modified)) {
            return $this->formatDateTime($entity->created);
        } else {
            return $this->formatDateTime($entity->modified);
        }
    }

    public function onGetToBeCompletedBy(Event $event, Entity $entity)
    {
        $academicPeriodId = $entity->academic_period_id;
        $surveyFormId = $entity->survey_form->id;

        $SurveyStatuses = $this->SurveyForms->SurveyStatuses;
        $SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

        $results = $SurveyStatuses
            ->find()
            ->select([
                $SurveyStatuses->aliasField('date_disabled')
            ])
            ->innerJoin(
                [$SurveyStatusPeriods->alias() => $SurveyStatusPeriods->table()],
                [
                    $SurveyStatusPeriods->aliasField('survey_status_id = ') . $SurveyStatuses->aliasField('id'),
                    $SurveyStatusPeriods->aliasField('academic_period_id') => $academicPeriodId
                ]
            )
            ->where([
                $SurveyStatuses->aliasField('survey_form_id') => $surveyFormId
            ])
            ->all();

        $value = '<i class="fa fa-minus"></i>';
        if (!$results->isEmpty()) {
            $dateDisabled = $results->first()->date_disabled;
            $value = $this->formatDate($dateDisabled);
        }

        return $value;
    }

    public function onGetCompletedOn(Event $event, Entity $entity)
    {
        return $this->formatDateTime($entity->modified);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // Retrieve from here because will be reset in beforeAction of WorkflowBehavior
        $this->attachWorkflow = $this->controller->Workflow->attachWorkflow;
        $this->hasWorkflow = $this->controller->Workflow->hasWorkflow;
        // End

        if ($this->attachWorkflow) {
            if ($this->hasWorkflow) {
                $selectedFilter = $this->request->query('filter');
                if ($selectedFilter != -1) {
                    $workflow = $this->getWorkflow($this->registryAlias(), null, $selectedFilter);
                    if (!empty($workflow)) {
                        foreach ($workflow->workflow_steps as $workflowStep) {
                            if ($workflowStep->category == WorkflowSteps::TO_DO) {
                                $this->openStatusId = $workflowStep->id;
                            } elseif ($workflowStep->category == WorkflowSteps::DONE) {
                                $this->closedStatusId = $workflowStep->id;
                            }
                        }
                    }
                }
            }
        }

        $this->field('description', ['type' => 'text']);
        $fieldOrder = ['survey_form_id', 'description', 'academic_period_id'];
        $selectedStatus = $this->request->query('status');

        if (is_null($selectedStatus) || $selectedStatus == -1) {
            $this->buildSurveyRecords();
            $this->field('last_modified');
            $fieldOrder[] = 'last_modified';
        } else {
            if ($selectedStatus == $this->openStatusId) {   // Open
                $this->buildSurveyRecords();
                $this->field('to_be_completed_by');
                $fieldOrder[] = 'to_be_completed_by';
            } elseif ($selectedStatus == $this->closedStatusId) {  // Closed
                $this->field('completed_on');
                $fieldOrder[] = 'completed_on';
            } else {
                $this->field('last_modified');
                $this->field('to_be_completed_by');
                $fieldOrder[] = 'last_modified';
                $fieldOrder[] = 'to_be_completed_by';
            }
        }
        $this->setFieldOrder($fieldOrder);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // Do not show expired records
        $extra['auto_contain'] = false;

        $query
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'id',
                        'name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'institution_status_id'
                    ]
                ],
                'SurveyForms' => [
                    'fields' => [
                        'name', 'description'
                    ]
                ],
                'Assignees' => [
                    'fields' => [
                        'first_name', 'middle_name', 'third_name', 'last_name'
                    ]
                ]
            ])
            ->where([
            $this->aliasField('status_id <> ') => self::EXPIRED,
            //POCOR-5666 Condition[START]
            //Survey should only show for the active institution
            $this->aliasField('Institutions.institution_status_id = ') => 1
            //POCOR-5666 Condition[END]
        ]);

        // POCOR-4027 fixed search function (search assignee and survey form)
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Assignees', 'searchTerm' => $search]);
            $surveyConditions = [$this->SurveyForms->aliasField('name').' LIKE' => '%' . $search . '%'];
            $descriptionConditions = [$this->SurveyForms->aliasField('description').' LIKE' => '%' . $search . '%'];

            $extra['OR'] = array_merge($nameConditions, $surveyConditions, $descriptionConditions);
        }
        // end POCOR-4027
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'SurveyForms'
        ]);
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
        $searchableFields[] = 'assignee_id';
        $searchableFields[] = 'description';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('description');
        $this->setFieldOrder(['academic_period_id', 'survey_form_id', 'description']);
    }

    public function viewAfterAction(Event $event, Entity $entity) {
        // to get all the workflow steps for this model
        $workflow = $this->getWorkflow($this->registryAlias(), $entity);
        if (!empty($workflow)) {
            foreach ($workflow->workflow_steps as $workflowStep) {
                if ($entity->status->id == $workflowStep->id && !($workflowStep->is_removable)) {
                    $this->toggle('remove', false);
                }
            }
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $surveyFormId = $entity->survey_form_id;
        
        $this->field('status_id', [
            'attr' => ['value' => $entity->status_id]
        ]);
        $this->field('academic_period_id', [
            'attr' => ['value' => $entity->academic_period_id]
        ]);
        $this->field('survey_form_id', [
            'attr' => ['value' => $surveyFormId]
        ]);
        $this->field('description', [
            'entity' => $entity
        ]);
        // this extra field is use by repeater type to know user click add on which repeater question
        $this->field('repeater_question_id');
    }

    public function onUpdateFieldStatusId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $statusOptions = $this->getWorkflowStepList();
            if (isset($attr['attr']['value'])) {
                $statusId = $attr['attr']['value'];

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $statusOptions[$statusId];
            }
        }

        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'edit') {
            $periodId = $attr['attr']['value'];
            $periodObject = $this->AcademicPeriods->get($periodId);
            
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $periodObject->name;
        }

        return $attr;
    }

    public function onUpdateFieldSurveyFormId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } elseif ($action == 'edit') {
            $formOptions = $this->getForms();
            $formId = $attr['attr']['value'];

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $formOptions[$formId];
        }

        return $attr;
    }

    public function onUpdateFieldDescription(Event $event, array $attr, $action, $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'text';
        } elseif ($action == 'edit') {
            $attr['type'] = 'text';
            $attr['attr']['disabled'] = 'disabled';
            if (array_key_exists('entity', $attr)) {
                $entity = $attr['entity'];

                $surveyFormDescription = $entity->survey_form->description;
                $attr['attr']['value'] = $surveyFormDescription;
            }
        }

        return $attr;
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        $includes['ruleCtrl'] = ['include' => true, 'js' => 'CustomField.angular/rules/relevancy.rules.ctrl'];
    }

    public function onUpdateFieldRepeaterQuestionId(Event $event, array $attr, $action, $request)
    {
        $attr['type'] = 'hidden';
        $attr['value'] = 0;
        $attr['attr']['class'] = 'repeater-question-id';

        return $attr;
    }

    public function buildSurveyRecords($institutionId = null, $surveyFormId = null, $academicPeriodId = null)
    {
        if (is_null($institutionId)) {
            $session = $this->controller->request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
        }

        $surveyForms = !is_null($surveyFormId) ? $this->getForms($surveyFormId) : $this->getForms();
        $todayDate = date("Y-m-d");
        $SurveyStatuses = $this->SurveyForms->SurveyStatuses;
        $SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;
        $institutionTypeId = $this->Institutions->get($institutionId)->institution_type_id;
        $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');

        foreach ($surveyForms as $surveyFormId => $surveyForm) {
            // check if the institution type matches. only the match type or all type will try go in to check insertion of records
            $filterTypeQuery = $SurveyFormsFilters
                ->find()
                ->where([
                    [$SurveyFormsFilters->aliasField('survey_form_id') => $surveyFormId],
                    [
                        'OR' => [
                            [$SurveyFormsFilters->aliasField('survey_filter_id') => $institutionTypeId],
                            [$SurveyFormsFilters->aliasField('survey_filter_id') => SurveyForms::ALL_CUSTOM_FILER]
                        ]
                    ]
                ]);

            $isInstitutionTypeMatch = $filterTypeQuery->count() > 0;

            $openStatusId = null;
            $workflow = $this->getWorkflow($this->registryAlias(), null, $surveyFormId);
            if ($isInstitutionTypeMatch) {
                if (!empty($workflow)) {
                    foreach ($workflow->workflow_steps as $workflowStep) {
                        if ($workflowStep->category == WorkflowSteps::TO_DO) {
                            $openStatusId = $workflowStep->id;
                            break;
                        }
                    }

                    // Update all New Survey to Expired by Institution Id
                    $this->updateAll(
                        ['status_id' => self::EXPIRED],
                        [
                            'institution_id' => $institutionId,
                            'survey_form_id' => $surveyFormId,
                            'status_id' => $openStatusId
                        ]
                    );

                    $periodResults = $SurveyStatusPeriods
                    ->find()
                    ->matching($this->AcademicPeriods->alias(), function ($q) use ($academicPeriodId) {
                        if (!is_null($academicPeriodId)) {
                            return $q->where([
                                $this->AcademicPeriods->aliasField('id') => $academicPeriodId
                            ]);
                        }
                        return $q;
                    })
                    ->matching($SurveyStatuses->alias(), function ($q) use ($SurveyStatuses, $surveyFormId, $todayDate) {
                        return $q
                            ->where([
                                $SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
                                $SurveyStatuses->aliasField('date_disabled >=') => $todayDate
                            ]);
                    })
                    ->all();

                    foreach ($periodResults as $obj) {
                        if (!is_null($institutionId)) {
                            $periodId = $obj->academic_period_id;

                            $where = [
                                $this->aliasField('academic_period_id') => $periodId,
                                $this->aliasField('survey_form_id') => $surveyFormId,
                                $this->aliasField('institution_id') => $institutionId
                            ];

                            $results = $this
                            ->find('all')
                            ->where($where)
                            ->all();

                            if ($results->isEmpty()) {
                                // Insert New Survey if not found
                                $surveyData = [
                                    'status_id' => $openStatusId,
                                    'academic_period_id' => $periodId,
                                    'survey_form_id' => $surveyFormId,
                                    'institution_id' => $institutionId,
                                    'created_user_id' => 1,
                                    'created' => new Time('NOW')
                                ];

                                $surveyEntity = $this->newEntity($surveyData, ['validate' => false]);
                                if ($this->save($surveyEntity)) {
                                } else {
                                    Log::write('debug', $surveyEntity->errors());
                                }
                            } else {
                                // Update Expired Survey back to Open
                                $this->updateAll(
                                    ['status_id' => $openStatusId],
                                    [
                                        'academic_period_id' => $periodId,
                                        'survey_form_id' => $surveyFormId,
                                        'institution_id' => $institutionId,
                                        'status_id' => self::EXPIRED
                                    ]
                                );
                            }
                        }
                    }
                }
            }
        }
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = WorkflowSteps::DONE;
        $roles = TableRegistry::get('Security.SecurityGroupUsers');
        $userRole = $roles->find()
                    ->select([$roles->aliasField('security_role_id')])
                    ->where([ $roles->aliasField('security_user_id')  => $userId ])->first();
        $roleId = $userRole['security_role_id'];
        $workflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->AcademicPeriods->aliasField('name'),
                $this->SurveyForms->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->AcademicPeriods->alias(), $this->SurveyForms->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->innerJoin(
                [$workflowStepsRoles->alias() => $workflowStepsRoles->table()],
                [
                    $workflowStepsRoles->aliasField('workflow_step_id = ') . $this->aliasField('status_id')
                ]
            )
            ->where([
                $this->aliasField('assignee_id') => $userId,
                $workflowStepsRoles->aliasField('security_role_id') => $roleId
            ])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'Surveys',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s in %s'), $row->survey_form->name, $row->academic_period->name);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });
        return $query;
    }
    
    public function workflowBeforeTransition(Event $event, $requestData)
    {
        $errors = false;
        $modelId = $this->request->pass[1]; // id of the sub model
        $ids = $this->ControllerAction->paramsDecode($modelId);
       
        $institutionServery = $this->get($ids['id']);

        //print_r($data);
        $SurveyFormQuestions = TableRegistry::get('Survey.SurveyFormsQuestions');
        $SurveyFormsQuestionDatas = $SurveyFormQuestions->find()
                ->innerJoin(
                    ['SurveyQuestions' => 'survey_questions'],
                    ['SurveyQuestions.id = '.$SurveyFormQuestions->aliasField('survey_question_id')]
                )
                ->where(['survey_form_id' => $institutionServery->survey_form_id, 'SurveyQuestions.is_mandatory' => self::IS_MANDATORY])
                ->count();
        //echo "<pre>";print_r($SurveyFormsQuestionDatas);die();
        if($SurveyFormsQuestionDatas < 0 ){
          $errors = true; 
          $this->Alert->error('InstitutionSurveys.mandatoryFieldFill', ['reset'=>true]);
        } 
        
        if ($errors) {
            $event->stopPropagation();
            $url = $this->url('view');
            return $this->controller->redirect($url);
        }      
    }
}
