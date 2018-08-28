<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\ControllerActionTable;

class StudentSurveysTable extends ControllerActionTable
{
    // Default Status
    const EXPIRED = -1;

    private $surveyInstitutionId = null;
    private $studentId = null;

    public function initialize(array $config)
    {
        $this->table('institution_student_surveys');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('InstitutionSurveys', ['className' => 'Institution.InstitutionSurveys', 'foreignKey' => 'parent_form_id']);
        $this->addBehavior('Survey.Survey', [
            'module' => 'Student.StudentSurveys'
        ]);
        $this->addBehavior('CustomField.Record', [
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
            'recordKey' => 'institution_student_survey_id',
            'fieldValueClass' => ['className' => 'Student.StudentSurveyAnswers', 'foreignKey' => 'institution_student_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'Student.StudentSurveyTableCells', 'foreignKey' => 'institution_student_survey_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionSurveys.afterSave'] = 'institutionSurveyAfterSave';

        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        //Add controls filter to index, view and edit page
        $indexElements = [
            ['name' => 'Student.StudentSurveys/controls', 'data' => [], 'options' => [], 'order' => 1]
        ];
        $extra['elements'] = array_merge($extra['elements'], $indexElements);

        $session = $this->controller->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }
        $studentId = $this->Session->read('Student.Students.id');

        // Build Survey Records
        $currentAction = $this->action;
        if ($currentAction == 'index') {
            // Disabled auto-insert New Survey for Student until there is a better solution
            // $this->_buildSurveyRecords($studentId);
        }
        // End

        // Academic Periods
        $periodOptions = $this->AcademicPeriods->getYearList();
        $selectedPeriod = $this->queryString('period', $periodOptions);
        $this->advancedSelectOptions($periodOptions, $selectedPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noSurveys')),
            'callable' => function ($id) use ($institutionId, $studentId) {
                return $this
                    ->find()
                    ->where([
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('student_id') => $studentId,
                        $this->aliasField('academic_period_id') => $id,
                        $this->aliasField('status_id <>') => self::EXPIRED  // Not expired
                    ])
                    ->count();
            }
        ]);
        $this->controller->set('periodOptions', $periodOptions);
        // End

        // Survey Forms
        $surveyForms = $this->getForms();

        $formOptions = [];
        foreach ($surveyForms as $surveyFormId => $surveyForm) {
            $count = $this
                ->find()
                ->where([
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('student_id') => $studentId,
                    $this->aliasField('academic_period_id') => $selectedPeriod,
                    $this->aliasField('survey_form_id') => $surveyFormId,
                    $this->aliasField('status_id <>') => self::EXPIRED  // Not expired
                ])
                ->count();
            if ($count) {
                $formOptions[$surveyFormId] = $surveyForm;
            }
        }
        $selectedForm = $this->queryString('form', $formOptions);
        $this->advancedSelectOptions($formOptions, $selectedForm);
        $this->controller->set('formOptions', $formOptions);
        // End

        $this->field('student_id', ['type' => 'hidden']);
        $this->field('academic_period_id', ['type' => 'hidden']);
        $this->field('survey_form_id', ['type' => 'hidden']);
        $this->field('parent_form_id', ['type' => 'hidden']);
        $this->field('status_id', ['type' => 'hidden']);

        $this->surveyInstitutionId = $institutionId;
        $this->studentId = $studentId;
        $this->request->query['period'] = $selectedPeriod;
        $this->request->query['form'] = $selectedForm;

        $this->_redirect($institutionId, $studentId, $selectedPeriod, $selectedForm);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $indexElements = [];
        $toolbarButtons = $extra['toolbarButtons'];
        if (isset($toolbarButtons['list'])) {
            unset($toolbarButtons['list']);
        }
        if (isset($toolbarButtons['back'])) {
            unset($toolbarButtons['back']);
        }
        $this->controller->set('indexElements', $indexElements);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupTabElements($entity);
    }

    private function setupTabElements($entity = null)
    {
        $id = !is_null($this->request->query('id')) ? $this->request->query('id') : 0;
        $userId = !is_null($this->request->query('user_id')) ? $this->request->query('user_id') : 0;

        $options = [
            'userRole' => 'Student',
            'action' => $this->action,
            'id' => $id,
            'userId' => $userId
        ];

        $tabElements = $this->controller->getUserTabElements($options);

        if (!is_null($entity)) {
            $tabElements['StudentSurveys']['url'][0] = 'view';
            $tabElements['StudentSurveys']['url'][1] = $this->paramsEncode(['id' => $entity->id]);
        }
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function institutionSurveyAfterSave(Event $event, Entity $institutionSurveyEntity)
    {
        $this->updateAll(
            ['status_id' => $institutionSurveyEntity->status_id],
            [
                'institution_id' => $institutionSurveyEntity->institution_id,
                'academic_period_id' => $institutionSurveyEntity->academic_period_id,
                'parent_form_id' => $institutionSurveyEntity->survey_form_id
            ]
        );
    }

    /* Disabled auto-insert New Survey for Student until there is a better solution
    public function _buildSurveyRecords($studentId=null, $institutionId=null) {
        $session = $this->controller->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
        }

        if (!is_null($studentId) && !is_null($institutionId)) {
            $surveyForms = $this->getForms();
            $todayDate = date("Y-m-d");
            $SurveyStatuses = $this->SurveyForms->SurveyStatuses;
            $SurveyStatusPeriods = $this->SurveyForms->SurveyStatuses->SurveyStatusPeriods;

            // Update all New Survey to Expired by Institution ID and Student ID
            $this->updateAll(['status_id' => -1],
                [
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'status_id' => 0
                ]
            );

            foreach ($surveyForms as $surveyFormId => $surveyForm) {
                $surveyStatusIds = $SurveyStatuses
                    ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
                    ->where([
                        $SurveyStatuses->aliasField('survey_form_id') => $surveyFormId,
                        $SurveyStatuses->aliasField('date_disabled >=') => $todayDate
                    ])
                    ->toArray();

                $academicPeriodIds = $SurveyStatusPeriods
                    ->find('list', ['keyField' => 'academic_period_id', 'valueField' => 'academic_period_id'])
                    ->where([$SurveyStatusPeriods->aliasField('survey_status_id IN') => $surveyStatusIds])
                    ->toArray();

                foreach ($academicPeriodIds as $key => $academicPeriodId) {
                    $results = $this
                        ->find('all')
                        ->where([
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('student_id') => $studentId,
                            $this->aliasField('academic_period_id') => $academicPeriodId,
                            $this->aliasField('survey_form_id') => $surveyFormId
                        ])
                        ->all();

                    if ($results->isEmpty()) {
                        // Insert New Survey if not found
                        $data = [
                            'institution_id' => $institutionId,
                            'student_id' => $studentId,
                            'academic_period_id' => $academicPeriodId,
                            'survey_form_id' => $surveyFormId
                        ];
                        $entity = $this->newEntity($data);
                        if ($this->save($entity)) {
                        } else {
                            $this->log($entity->errors(), 'debug');
                        }
                    } else {
                        // Update Expired Survey back to New
                        $this->updateAll(['status_id' => 0],
                            [
                                'institution_id' => $institutionId,
                                'student_id' => $studentId,
                                'academic_period_id' => $academicPeriodId,
                                'survey_form_id' => $surveyFormId,
                                'status_id' => self::EXPIRED
                            ]
                        );
                    }
                }
            }
        }
    }
    */

    public function _redirect($institutionId = null, $studentId = null, $periodId = 0, $formId = 0)
    {
        $currentAction = $this->action;

        $results = $this
            ->find()
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $periodId,
                $this->aliasField('survey_form_id') => $formId,
                $this->aliasField('status_id <>') => self::EXPIRED  // Not Expired
            ])
            ->first();

        if (!empty($results)) {
            $this->request->query['status'] = $results->status_id;

            $url = $this->url('view');
            $url[1] = $this->paramsEncode(['id' => $results->id]);

            if ($currentAction == 'index') {
                return $this->controller->redirect($url);
            } else {
                $paramsPass = $this->paramsDecode($this->paramsPass(0))['id'];
                if ($results->id != $paramsPass) {
                    return $this->controller->redirect($url);
                }
            }
        } else {
            $url = $this->url('index');

            if ($currentAction == 'view' || $currentAction == 'edit') {
                return $this->controller->redirect($url);
            }
        }
    }
}
