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

class InstitutionSurveysTable extends ControllerActionTable
{
    use OptionsTrait;
    use MessagesTrait;

    // Default Status
    const EXPIRED = -1;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

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
            'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
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
        $events['Model.custom.onUpdateActionButtons'] = 'onUpdateActionButtons';
        $events['Workflow.getFilterOptions'] = 'getWorkflowFilterOptions';
        // $events['Restful.Model.index.workbench'] = 'indexAfterFindWorkbench';
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';

        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $query
            ->select(['code' => 'Institutions.code', 'area_id' => 'Areas.name', 'area_administrative_id' => 'AreaAdministratives.name'])
            ->contain(['Institutions.Areas', 'Institutions.AreaAdministratives']);
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

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $extra)
    {
        return $this->controller->redirect($this->url('edit'));
    }

    public function getWorkflowFilterOptions(Event $event)
    {
        $CustomModules = $this->SurveyForms->CustomModules;
        $module = $this->module;
        $list = $this->SurveyForms
            ->find('list')
            ->matching('CustomModules', function ($q) use ($CustomModules, $module) {
                return $q->where([$CustomModules->aliasField('model') => $module]);
            })
            ->toArray();

        return $list;
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
        $surveyFormId = $entity->survey_form->id;
        return $this->SurveyForms->get($surveyFormId)->description;
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
                            if ($workflowStep->category == 1) {     // To Do
                                $this->openStatusId = $workflowStep->id;
                            } elseif ($workflowStep->category == 3) {  // Done
                                $this->closedStatusId = $workflowStep->id;
                            }
                        }
                    }
                }
            }
        }

        $this->field('description');
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
        $query->where([
            $this->aliasField('status_id <> ') => self::EXPIRED
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

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
        $searchableFields[] = 'assignee_id';
        $searchableFields[] = 'description';
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('academic_period_id');
        $this->field('survey_form_id');
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('status_id', [
            'attr' => ['value' => $entity->status_id]
        ]);
        $this->field('academic_period_id', [
            'attr' => ['value' => $entity->academic_period_id]
        ]);
        $this->field('survey_form_id', [
            'attr' => ['value' => $entity->survey_form_id]
        ]);
        // this extra field is use by repeater type to know user click add on which repeater question
        $this->field('repeater_question_id');
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);

        // For Institution Survey, delete button will be disabled regardless settings in Workflow
        if (array_key_exists('remove', $buttons)) {
            unset($buttons['remove']);
        }

        return $buttons;
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
            $periodOptions = $this->AcademicPeriods->getYearList(['withLevels' => false]);
            $periodId = $attr['attr']['value'];

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $periodOptions[$periodId];
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

    public function buildSurveyRecords($institutionId = null)
    {
        if (is_null($institutionId)) {
            $session = $this->controller->request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
        }

        $surveyForms = $this->getForms();
        $todayDate = date("Y-m-d");
        $SurveyStatuses = $this->SurveyForms->SurveyStatuses;
        $SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

        foreach ($surveyForms as $surveyFormId => $surveyForm) {
            $openStatusId = null;
            $workflow = $this->getWorkflow($this->registryAlias(), null, $surveyFormId);
            if (!empty($workflow)) {
                foreach ($workflow->workflow_steps as $workflowStep) {
                    if ($workflowStep->category == 1) {
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
                    ->matching($this->AcademicPeriods->alias())
                    ->matching($SurveyStatuses->alias(), function ($q) use ($SurveyStatuses, $surveyFormId, $todayDate) {
                        return $q
                            ->where([
                                $SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
                                $SurveyStatuses->aliasField('date_disabled >=') => $todayDate
                            ]);
                    })
                    ->all();

                foreach ($periodResults as $obj) {
                    $periodId = $obj->academic_period_id;
                    if (!is_null($institutionId)) {
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
                                $this->log($surveyEntity->errors(), 'debug');
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

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

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
            ->where([$this->aliasField('assignee_id') => $userId])
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
}
