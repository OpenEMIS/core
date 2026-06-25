<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\I18n\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

//POCOR-6695 Starts
class SurveysReportTable extends AppTable
{
    // Reports > Survey > Survey Report
    private $_dynamicFieldName = 'custom_field_data';//POCOR-8525
    public function initialize(array $config): void
    {
        $this->setTable('institution_surveys');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    //Modify query -- POCOR-8043
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, $query)
    {
        $condition = [];
        $groupBy = [];
        $requestData = json_decode($settings['process']['params']);
        $institutionID = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $surveySection = $requestData->survey_section;
        $tableQuestion = $requestData->table_question;
        $institutionStatus = $requestData->institution_status;
        $areaId = $requestData->area_id;
        $selectedArea = $requestData->area_id;
        $surveyFormId = $requestData->survey_form_id; // POCOR-9116

        $surveyForms = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionStatuses = TableRegistry::getTableLocator()->get('Institution.InstitutionStatuses');
        $areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $areaLevels = TableRegistry::getTableLocator()->get('Area.AreaLevels');

        //POCOR-8525 starts find record is exist in `institution_repeater_surveys` table for Repeater case
        if($institutionID <= 0){
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $InstitutionsData = $Institutions->find()->toArray();
            if(!empty($InstitutionsData)){
                $instArr = [];
                foreach($InstitutionsData AS $inst_key => $inst_val){
                    $instArr[] = $inst_val['id'];
                }
            }
        }else{
            $instArr[] = $institutionID;
        }

        $repeaterListCountResult = $this->checkSurveyExistanceInRepeater($instArr, $academicPeriodId, $surveyFormId, $surveySection, $tableQuestion); // POCOR-9087
        $staffListCountResult = $this->checkSurveyExistanceInStaff($instArr, $academicPeriodId, $surveyFormId, $surveySection, $tableQuestion);
        $studentListCountResult = $this->checkSurveyExistanceInStudent($instArr, $academicPeriodId, $surveyFormId, $surveySection, $tableQuestion);

        // if exists
        if((count($repeaterListCountResult) > 0) && ((count($staffListCountResult) <= 0) && (count($studentListCountResult) <= 0))){
            $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
            $WorkflowSteps  = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $AreaAdministratives = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

            if($institutionID > 0){
                $condition[$institutions->aliasField('id')] = $institutionID;
            }
            if ($areaId != -1 && $areaId != '' && $areaId != 0) {
                $areaIds = [];
                $allgetArea = $this->getChildren($selectedArea, $areaIds);
                $selectedArea1[]= $selectedArea;
                if(!empty($allgetArea)){
                    $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                }else{
                    $allselectedAreas = $selectedArea1;
                }
                $condition[$institutions->aliasField('area_id IN')] = $allselectedAreas;
            }
            if (!empty($institutionStatus)) {
                $condition[$institutionStatuses->aliasField('name')] = $institutionStatus;
            }
            if (!empty($academicPeriodId)) {
                $condition[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            if (!empty($surveyFormId)) {
                $condition[$surveyForms->aliasField('id')] = $surveyFormId;
            }

            $query->select([
                    'survey_status' => 'WorkflowSteps.name',
                    'academic_period_name' => 'AcademicPeriods.name',
                    'survey_form_name' => 'SurveyForms.name',
                    'area_education_name' => 'Areas.name',
                    'code' => 'Institutions.code',
                    'institution_name' => 'Institutions.name',
                    'institution_status_name' => 'InstitutionStatuses.name',
                    'assignee_name' => "(CASE WHEN {$this->aliasField('assignee_id')} = 0 THEN '' ELSE REPLACE(REPLACE(CONCAT_WS(' ', SecurityUsers.first_name, SecurityUsers.middle_name, SecurityUsers.third_name, SecurityUsers.last_name), '   ', ' '), '  ', ' ') END)",
                    'area_administrative_name' => $query->newExpr("IFNULL(AreaAdministratives.name, '')"),
                    'InstitutionRepeaterSurveysId' => 'InstitutionRepeaterSurveys.id',
                    'InstitutionRepeaterSurveys_survey_form_id' => 'InstitutionRepeaterSurveys.survey_form_id'
                ])
                ->join([
                    'SecurityUsers' => [
                        'table' => 'security_users',
                        'type' => 'INNER',
                        'conditions' => 'SecurityUsers.id = '. $this->aliasField('assignee_id')
                    ],
                    'SurveyForms' => [
                        'table' => 'survey_forms',
                        'type' => 'INNER',
                        'conditions' => 'SurveyForms.id = '. $this->aliasField('survey_form_id')
                    ],
                    'WorkflowSteps' => [
                        'table' => 'workflow_steps',
                        'type' => 'INNER',
                        'conditions' => 'WorkflowSteps.id = '. $this->aliasField('status_id')
                    ],
                    'Institutions' => [
                        'table' => 'institutions',
                        'type' => 'INNER',
                        'conditions' => 'Institutions.id = '. $this->aliasField('institution_id')
                    ],
                    'InstitutionStatuses' => [
                        'table' => 'institution_statuses',
                        'type' => 'INNER',
                        'conditions' => 'InstitutionStatuses.id = Institutions.institution_status_id'
                    ],
                    'Areas' => [
                        'table' => 'areas',
                        'type' => 'INNER',
                        'conditions' => 'Areas.id = Institutions.area_id'
                    ],
                    'AreaAdministratives' => [
                        'table' => 'area_administratives',
                        'type' => 'LEFT',
                        'conditions' => 'AreaAdministratives.id = Institutions.area_administrative_id'
                    ],
                    'AcademicPeriods' => [
                        'table' => 'academic_periods',
                        'type' => 'INNER',
                        'conditions' => 'AcademicPeriods.id = '. $this->aliasField('academic_period_id')
                    ],
                    'InstitutionRepeaterSurveys' => [
                        'table' => 'institution_repeater_surveys',
                        'type' => 'INNER',
                        'conditions' => [
                            'InstitutionRepeaterSurveys.status_id = ' . $this->aliasField('status_id'),
                            'InstitutionRepeaterSurveys.academic_period_id = ' . $this->aliasField('academic_period_id'),
                            'InstitutionRepeaterSurveys.parent_form_id = ' . $this->aliasField('survey_form_id'),
                            'InstitutionRepeaterSurveys.institution_id = ' . $this->aliasField('institution_id')
                        ]
                    ]
                ])
                ->where([$condition])
                ->group([$this->aliasField('id'), 'InstitutionRepeaterSurveys.id']);
                $query->formatResults(function (ResultSetInterface $results) use ($surveySection, $surveyFormId, $tableQuestion) {
                    return $results->map(function ($row) use ($surveySection, $surveyFormId, $tableQuestion) {
                        //get data related to survey section
                        $surveySectionId = "$surveySection";
                        $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();
                        //get survey_form_id from the parent_form_id
                        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
                        $SurveyFormsQuestionsData = $SurveyFormsQuestions->find()
                            ->select([
                                'id' => $SurveyFormsQuestions->aliasField('id'),
                                'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                                'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                                'surveyQuestion_id' => $surveyQuestion->aliasField('id'),
                                'surveyQuestion_name' => $surveyQuestion->aliasField('name'),
                                'surveyQuestion_field_type' => $surveyQuestion->aliasField('field_type'),
                                'surveyQuestion_parmas' => $surveyQuestion->aliasField('params'),
                                'surveyQuestion_survey_form_id' => 'JSON_UNQUOTE(JSON_EXTRACT(' . $surveyQuestion->aliasField('params') . ", '$.survey_form_id'))",
                            ])
                            ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                            [
                                $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
                            ])
                            ->where([
                                $SurveyFormsQuestions->aliasField('survey_form_id') => $surveyFormId,
                                $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                                $surveyQuestion->aliasField('field_type') => 'REPEATER',
                                $surveyQuestion->aliasField('id') => $tableQuestion,
                            ])->first();
                        $InstitutionRepeaterSurveysId = $InstitutionRepeaterSurveys_survey_form_id = '';
                        if(!empty($SurveyFormsQuestionsData)){
                            $InstitutionRepeaterSurveysId = $row->InstitutionRepeaterSurveysId;
                            $InstitutionRepeaterSurveys_survey_form_id = $SurveyFormsQuestionsData->surveyQuestion_survey_form_id;
                        }
                        //get all questions list
                        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $SurveyFormsQuestionsRes = $SurveyFormsQuestions->find()
                            ->select([
                                'id' => $SurveyFormsQuestions->aliasField('id'),
                                'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                                'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                            ])->where([
                                $SurveyFormsQuestions->aliasField('survey_form_id IS') => $InstitutionRepeaterSurveys_survey_form_id,
                            ])
                            ->order([$SurveyFormsQuestions->aliasField('survey_question_id') => 'ASC'])
                            ->toArray();

                        if(!empty($SurveyFormsQuestionsRes)){
                            foreach($SurveyFormsQuestionsRes AS $sfq_key => $sfq_val){
                                $InstitutionRepeaterSurveys = TableRegistry::getTableLocator()->get('InstitutionRepeater.RepeaterSurveys');
                                $InstitutionRepeaterSurveyAnswers = TableRegistry::getTableLocator()->get('InstitutionRepeater.RepeaterSurveyAnswers');
                                $SurveyQuestionChoices = TableRegistry::getTableLocator()->get('Survey.SurveyQuestionChoices');
                                $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');

                                $InstitutionRepeaterSurveyAnswersRes = $InstitutionRepeaterSurveyAnswers
                                    ->find()
                                    ->select([
                                        'id' => $InstitutionRepeaterSurveyAnswers->aliasField('id'),
                                        'text_value' => $InstitutionRepeaterSurveyAnswers->aliasField('text_value'),
                                        'number_value' => $InstitutionRepeaterSurveyAnswers->aliasField('number_value'),
                                        'decimal_value' => $InstitutionRepeaterSurveyAnswers->aliasField('decimal_value'),
                                        'textarea_value' => $InstitutionRepeaterSurveyAnswers->aliasField('textarea_value'),
                                        'date_value' => $InstitutionRepeaterSurveyAnswers->aliasField('date_value'),
                                        'time_value' => $InstitutionRepeaterSurveyAnswers->aliasField('time_value'),
                                        'file' => $InstitutionRepeaterSurveyAnswers->aliasField('file'),
                                        'survey_question_id' => $InstitutionRepeaterSurveyAnswers->aliasField('survey_question_id'),
                                        'survey_question_choices_id' => $SurveyQuestionChoices->aliasField('id'),
                                        'survey_question_choices_name' => $SurveyQuestionChoices->aliasField('name'),
                                        'institution_repeater_survey_id' => $InstitutionRepeaterSurveyAnswers->aliasField('institution_repeater_survey_id'),
                                    ])
                                    ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                                    [
                                        $surveyQuestion->aliasField('id') . ' = '. $InstitutionRepeaterSurveyAnswers->aliasField('survey_question_id'),
                                    ])
                                    ->innerJoin([$InstitutionRepeaterSurveys->getAlias() => $InstitutionRepeaterSurveys->getTable()],
                                    [
                                        $InstitutionRepeaterSurveys->aliasField('id') . ' = '. $InstitutionRepeaterSurveyAnswers->aliasField('institution_repeater_survey_id')
                                    ])
                                    ->leftJoin([$SurveyQuestionChoices->getAlias() => $SurveyQuestionChoices->getTable()],
                                    [
                                        $SurveyQuestionChoices->aliasField('survey_question_id') . ' = '. $InstitutionRepeaterSurveyAnswers->aliasField('survey_question_id'),
                                        $SurveyQuestionChoices->aliasField('id') . ' = '. $InstitutionRepeaterSurveyAnswers->aliasField('number_value')
                                    ])
                                    ->where([
                                        $InstitutionRepeaterSurveyAnswers->aliasField('institution_repeater_survey_id') => $InstitutionRepeaterSurveysId,
                                        $InstitutionRepeaterSurveyAnswers->aliasField('survey_question_id') => $sfq_val->survey_question_id,
                                    ])->first();

                                if(!empty($InstitutionRepeaterSurveyAnswersRes)){
                                    $InstitutionRepeaterSurveyAnswersId = $InstitutionRepeaterSurveyAnswersRes->id;

                                    if($InstitutionRepeaterSurveyAnswersRes->text_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionRepeaterSurveyAnswersRes->text_value;
                                    }
                                    if($InstitutionRepeaterSurveyAnswersRes->survey_question_choices_id != "" && $InstitutionRepeaterSurveyAnswersRes->number_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionRepeaterSurveyAnswersRes->survey_question_choices_name;
                                    }else if($InstitutionRepeaterSurveyAnswersRes->survey_question_choices_id == "" && $InstitutionRepeaterSurveyAnswersRes->number_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionRepeaterSurveyAnswersRes->number_value;
                                    }
                                    if($InstitutionRepeaterSurveyAnswersRes->decimal_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionRepeaterSurveyAnswersRes->decimal_value;
                                    }
                                    if($InstitutionRepeaterSurveyAnswersRes->textarea_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionRepeaterSurveyAnswersRes->textarea_value;
                                    }
                                    if($InstitutionRepeaterSurveyAnswersRes->date_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = date('Y-m-d', strtotime($InstitutionRepeaterSurveyAnswersRes->date_value));
                                    }
                                    if($InstitutionRepeaterSurveyAnswersRes->time_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = date('h:i A', strtotime($InstitutionRepeaterSurveyAnswersRes->time_value));
                                    }
                                }else{
                                    $row[$this->_dynamicFieldName.'_'.$sfq_key] = '';
                                }
                            }
                        }
                        return $row;
                    });
                });
        }else if((count($staffListCountResult) > 0) && ((count($repeaterListCountResult) <= 0) && (count($studentListCountResult) <= 0))){
            $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
            $WorkflowSteps  = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $AreaAdministratives = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

            if($institutionID > 0){
                $condition[$institutions->aliasField('id')] = $institutionID;
            }
            if ($areaId != -1 && $areaId != '' && $areaId != 0) {
                $areaIds = [];
                $allgetArea = $this->getChildren($selectedArea, $areaIds);
                $selectedArea1[]= $selectedArea;
                if(!empty($allgetArea)){
                    $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                }else{
                    $allselectedAreas = $selectedArea1;
                }
                $condition[$institutions->aliasField('area_id IN')] = $allselectedAreas;
            }
            if (!empty($institutionStatus)) {
                $condition[$institutionStatuses->aliasField('name')] = $institutionStatus;
            }
            if (!empty($academicPeriodId)) {
                $condition[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            if (!empty($academicPeriodId)) {
                $condition[$surveyForms->aliasField('id')] = $surveyFormId;
            }

            $query->select([
                    'survey_status' => 'WorkflowSteps.name',
                    'academic_period_name' => 'AcademicPeriods.name',
                    'survey_form_name' => 'SurveyForms.name',
                    'area_education_name' => 'Areas.name',
                    'code' => 'Institutions.code',
                    'institution_name' => 'Institutions.name',
                    'institution_status_name' => 'InstitutionStatuses.name',
                    'assignee_name' => "(CASE WHEN {$this->aliasField('assignee_id')} = 0 THEN '' ELSE REPLACE(REPLACE(CONCAT_WS(' ', SecurityUsers.first_name, SecurityUsers.middle_name, SecurityUsers.third_name, SecurityUsers.last_name), '   ', ' '), '  ', ' ') END)",
                    'area_administrative_name' => $query->newExpr("IFNULL(AreaAdministratives.name, '')"),
                    'InstitutionStaffSurveysId' => 'InstitutionStaffSurveys.id',
                    'InstitutionStaffSurveys_survey_form_id' => 'InstitutionStaffSurveys.survey_form_id'
                ])
                ->join([
                    'SecurityUsers' => [
                        'table' => 'security_users',
                        'type' => 'INNER',
                        'conditions' => 'SecurityUsers.id = '. $this->aliasField('assignee_id')
                    ],
                    'SurveyForms' => [
                        'table' => 'survey_forms',
                        'type' => 'INNER',
                        'conditions' => 'SurveyForms.id = '. $this->aliasField('survey_form_id')
                    ],
                    'WorkflowSteps' => [
                        'table' => 'workflow_steps',
                        'type' => 'INNER',
                        'conditions' => 'WorkflowSteps.id = '. $this->aliasField('status_id')
                    ],
                    'Institutions' => [
                        'table' => 'institutions',
                        'type' => 'INNER',
                        'conditions' => 'Institutions.id = '. $this->aliasField('institution_id')
                    ],
                    'InstitutionStatuses' => [
                        'table' => 'institution_statuses',
                        'type' => 'INNER',
                        'conditions' => 'InstitutionStatuses.id = Institutions.institution_status_id'
                    ],
                    'Areas' => [
                        'table' => 'areas',
                        'type' => 'INNER',
                        'conditions' => 'Areas.id = Institutions.area_id'
                    ],
                    'AreaAdministratives' => [
                        'table' => 'area_administratives',
                        'type' => 'LEFT',
                        'conditions' => 'AreaAdministratives.id = Institutions.area_administrative_id'
                    ],
                    'AcademicPeriods' => [
                        'table' => 'academic_periods',
                        'type' => 'INNER',
                        'conditions' => 'AcademicPeriods.id = '. $this->aliasField('academic_period_id')
                    ],
                    'InstitutionStaffSurveys' => [
                        'table' => 'institution_staff_surveys',
                        'type' => 'INNER',
                        'conditions' => [
                            'InstitutionStaffSurveys.status_id = ' . $this->aliasField('status_id'),
                            'InstitutionStaffSurveys.academic_period_id = ' . $this->aliasField('academic_period_id'),
                            'InstitutionStaffSurveys.parent_form_id = ' . $this->aliasField('survey_form_id'),
                            'InstitutionStaffSurveys.institution_id = ' . $this->aliasField('institution_id')
                        ]
                    ]
                ])
                ->where([$condition])
                ->group([$this->aliasField('id'), 'InstitutionStaffSurveys.id']);

                $query->formatResults(function (ResultSetInterface $results) use ($surveySection, $surveyFormId, $tableQuestion) {
                    return $results->map(function ($row) use ($surveySection, $surveyFormId, $tableQuestion) {
                        //get data related to survey section
                        $surveySectionId = "$surveySection";
                        $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();
                        //get survey_form_id from the parent_form_id
                        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
                        $SurveyFormsQuestionsData = $SurveyFormsQuestions->find()
                            ->select([
                                'id' => $SurveyFormsQuestions->aliasField('id'),
                                'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                                'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                                'surveyQuestion_id' => $surveyQuestion->aliasField('id'),
                                'surveyQuestion_name' => $surveyQuestion->aliasField('name'),
                                'surveyQuestion_field_type' => $surveyQuestion->aliasField('field_type'),
                                'surveyQuestion_parmas' => $surveyQuestion->aliasField('params'),
                                'surveyQuestion_survey_form_id' => 'JSON_UNQUOTE(JSON_EXTRACT(' . $surveyQuestion->aliasField('params') . ", '$.survey_form_id'))",
                            ])
                            ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                            [
                                $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
                            ])
                            ->where([
                                $SurveyFormsQuestions->aliasField('survey_form_id') => $surveyFormId,
                                $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                                $surveyQuestion->aliasField('field_type') => 'STAFF_LIST',
                                $surveyQuestion->aliasField('id') => $tableQuestion,
                            ])
                            ->first();

                        $InstitutionStaffSurveysId = $InstitutionStaffSurveys_survey_form_id = '';
                        if(!empty($SurveyFormsQuestionsData)){
                            $InstitutionStaffSurveysId = $row->InstitutionStaffSurveysId;
                            $InstitutionStaffSurveys_survey_form_id = $SurveyFormsQuestionsData->surveyQuestion_survey_form_id;
                        }

                        //get all questions list
                        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $SurveyFormsQuestionsRes = $SurveyFormsQuestions->find()
                            ->select([
                                'id' => $SurveyFormsQuestions->aliasField('id'),
                                'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                                'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                            ])->where([
                                $SurveyFormsQuestions->aliasField('survey_form_id IS') => $InstitutionStaffSurveys_survey_form_id,
                            ])
                            ->order([$SurveyFormsQuestions->aliasField('survey_question_id') => 'ASC'])
                            ->toArray();
                        if(!empty($SurveyFormsQuestionsRes)){
                            foreach($SurveyFormsQuestionsRes AS $sfq_key => $sfq_val){
                                $InstitutionStaffSurveys = TableRegistry::getTableLocator()->get('Staff.StaffSurveys');
                                $InstitutionStaffSurveyAnswers = TableRegistry::getTableLocator()->get('Staff.StaffSurveyAnswers');
                                $SurveyQuestionChoices = TableRegistry::getTableLocator()->get('Survey.SurveyQuestionChoices');
                                $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');

                                $InstitutionStaffSurveyAnswersRes = $InstitutionStaffSurveyAnswers
                                    ->find()
                                    ->select([
                                        'id' => $InstitutionStaffSurveyAnswers->aliasField('id'),
                                        'text_value' => $InstitutionStaffSurveyAnswers->aliasField('text_value'),
                                        'number_value' => $InstitutionStaffSurveyAnswers->aliasField('number_value'),
                                        'decimal_value' => $InstitutionStaffSurveyAnswers->aliasField('decimal_value'),
                                        'textarea_value' => $InstitutionStaffSurveyAnswers->aliasField('textarea_value'),
                                        'date_value' => $InstitutionStaffSurveyAnswers->aliasField('date_value'),
                                        'time_value' => $InstitutionStaffSurveyAnswers->aliasField('time_value'),
                                        'file' => $InstitutionStaffSurveyAnswers->aliasField('file'),
                                        'survey_question_id' => $InstitutionStaffSurveyAnswers->aliasField('survey_question_id'),
                                        'survey_question_choices_id' => $SurveyQuestionChoices->aliasField('id'),
                                        'survey_question_choices_name' => $SurveyQuestionChoices->aliasField('name'),
                                        'institution_staff_survey_id' => $InstitutionStaffSurveyAnswers->aliasField('institution_staff_survey_id'),
                                    ])
                                    ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                                    [
                                        $surveyQuestion->aliasField('id') . ' = '. $InstitutionStaffSurveyAnswers->aliasField('survey_question_id'),
                                    ])
                                    ->innerJoin([$InstitutionStaffSurveys->getAlias() => $InstitutionStaffSurveys->getTable()],
                                    [
                                        $InstitutionStaffSurveys->aliasField('id') . ' = '. $InstitutionStaffSurveyAnswers->aliasField('institution_staff_survey_id')
                                    ])
                                    ->leftJoin([$SurveyQuestionChoices->getAlias() => $SurveyQuestionChoices->getTable()],
                                    [
                                        $SurveyQuestionChoices->aliasField('survey_question_id') . ' = '. $InstitutionStaffSurveyAnswers->aliasField('survey_question_id'),
                                        $SurveyQuestionChoices->aliasField('id') . ' = '. $InstitutionStaffSurveyAnswers->aliasField('number_value')
                                    ])
                                    ->where([
                                        $InstitutionStaffSurveyAnswers->aliasField('institution_staff_survey_id') => $InstitutionStaffSurveysId,
                                        //$InstitutionStaffSurveyAnswers->aliasField('parent_survey_question_id') => $tableQuestion,
                                        $InstitutionStaffSurveyAnswers->aliasField('survey_question_id') => $sfq_val->survey_question_id,
                                    ])
                                    ->first();

                                if(!empty($InstitutionStaffSurveyAnswersRes)){
                                    $InstitutionStaffSurveyAnswersId = $InstitutionStaffSurveyAnswersRes->id;

                                    if($InstitutionStaffSurveyAnswersRes->text_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStaffSurveyAnswersRes->text_value;
                                    }
                                    if($InstitutionStaffSurveyAnswersRes->survey_question_choices_id != "" && $InstitutionStaffSurveyAnswersRes->number_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStaffSurveyAnswersRes->survey_question_choices_name;
                                    }else if($InstitutionStaffSurveyAnswersRes->survey_question_choices_id == "" && $InstitutionStaffSurveyAnswersRes->number_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStaffSurveyAnswersRes->number_value;
                                    }
                                    if($InstitutionStaffSurveyAnswersRes->decimal_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStaffSurveyAnswersRes->decimal_value;
                                    }
                                    if($InstitutionStaffSurveyAnswersRes->textarea_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStaffSurveyAnswersRes->textarea_value;
                                    }
                                    if($InstitutionStaffSurveyAnswersRes->date_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = date('Y-m-d', strtotime($InstitutionStaffSurveyAnswersRes->date_value));
                                    }
                                    if($InstitutionStaffSurveyAnswersRes->time_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = date('h:i A', strtotime($InstitutionStaffSurveyAnswersRes->time_value));
                                    }
                                }else{
                                    $row[$this->_dynamicFieldName.'_'.$sfq_key] = '';
                                }
                            }
                        }
                        return $row;
                    });
                });
        }else if((count($studentListCountResult) > 0) && ((count($repeaterListCountResult) <= 0) && (count($staffListCountResult) <= 0))){
            $SecurityUsers = TableRegistry::getTableLocator()->get('Security.Users');
            $WorkflowSteps  = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $AreaAdministratives = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

            if($institutionID > 0){
                $condition[$institutions->aliasField('id')] = $institutionID;
            }
            if ($areaId != -1 && $areaId != '' && $areaId != 0) {
                $areaIds = [];
                $allgetArea = $this->getChildren($selectedArea, $areaIds);
                $selectedArea1[]= $selectedArea;
                if(!empty($allgetArea)){
                    $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                }else{
                    $allselectedAreas = $selectedArea1;
                }
                $condition[$institutions->aliasField('area_id IN')] = $allselectedAreas;
            }
            if (!empty($institutionStatus)) {
                $condition[$institutionStatuses->aliasField('name')] = $institutionStatus;
            }
            if (!empty($academicPeriodId)) {
                $condition[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            if (!empty($academicPeriodId)) {
                $condition[$surveyForms->aliasField('id')] = $surveyFormId;
            }

            $query->select([
                    'survey_status' => 'WorkflowSteps.name',
                    'academic_period_name' => 'AcademicPeriods.name',
                    'survey_form_name' => 'SurveyForms.name',
                    'area_education_name' => 'Areas.name',
                    'code' => 'Institutions.code',
                    'institution_name' => 'Institutions.name',
                    'institution_status_name' => 'InstitutionStatuses.name',
                    'assignee_name' => "(CASE WHEN {$this->aliasField('assignee_id')} = 0 THEN '' ELSE REPLACE(REPLACE(CONCAT_WS(' ', SecurityUsers.first_name, SecurityUsers.middle_name, SecurityUsers.third_name, SecurityUsers.last_name), '   ', ' '), '  ', ' ') END)",
                    'area_administrative_name' => $query->newExpr("IFNULL(AreaAdministratives.name, '')"),
                    'InstitutionStudentSurveysId' => 'InstitutionStudentSurveys.id',
                    'InstitutionStudentSurveys_survey_form_id' => 'InstitutionStudentSurveys.survey_form_id'
                ])
                ->join([
                    'SecurityUsers' => [
                        'table' => 'security_users',
                        'type' => 'INNER',
                        'conditions' => 'SecurityUsers.id = '. $this->aliasField('assignee_id')
                    ],
                    'SurveyForms' => [
                        'table' => 'survey_forms',
                        'type' => 'INNER',
                        'conditions' => 'SurveyForms.id = '. $this->aliasField('survey_form_id')
                    ],
                    'WorkflowSteps' => [
                        'table' => 'workflow_steps',
                        'type' => 'INNER',
                        'conditions' => 'WorkflowSteps.id = '. $this->aliasField('status_id')
                    ],
                    'Institutions' => [
                        'table' => 'institutions',
                        'type' => 'INNER',
                        'conditions' => 'Institutions.id = '. $this->aliasField('institution_id')
                    ],
                    'InstitutionStatuses' => [
                        'table' => 'institution_statuses',
                        'type' => 'INNER',
                        'conditions' => 'InstitutionStatuses.id = Institutions.institution_status_id'
                    ],
                    'Areas' => [
                        'table' => 'areas',
                        'type' => 'INNER',
                        'conditions' => 'Areas.id = Institutions.area_id'
                    ],
                    'AreaAdministratives' => [
                        'table' => 'area_administratives',
                        'type' => 'LEFT',
                        'conditions' => 'AreaAdministratives.id = Institutions.area_administrative_id'
                    ],
                    'AcademicPeriods' => [
                        'table' => 'academic_periods',
                        'type' => 'INNER',
                        'conditions' => 'AcademicPeriods.id = '. $this->aliasField('academic_period_id')
                    ],
                    'InstitutionStudentSurveys' => [
                        'table' => 'institution_student_surveys',
                        'type' => 'INNER',
                        'conditions' => [
                            'InstitutionStudentSurveys.status_id = ' . $this->aliasField('status_id'),
                            'InstitutionStudentSurveys.academic_period_id = ' . $this->aliasField('academic_period_id'),
                            'InstitutionStudentSurveys.parent_form_id = ' . $this->aliasField('survey_form_id'),
                            'InstitutionStudentSurveys.institution_id = ' . $this->aliasField('institution_id')
                        ]
                    ]
                ])
                ->where([$condition])
                ->group([$this->aliasField('id'), 'InstitutionStudentSurveys.id']);
                $query->formatResults(function (ResultSetInterface $results) use ($surveySection, $surveyFormId, $tableQuestion) {
                    return $results->map(function ($row) use ($surveySection, $surveyFormId, $tableQuestion) {
                        $surveySectionId = "$surveySection";
                        $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();
                        //get survey_form_id from the parent_form_id
                        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
                        $SurveyFormsQuestionsData = $SurveyFormsQuestions->find()
                            ->select([
                                'id' => $SurveyFormsQuestions->aliasField('id'),
                                'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                                'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                                'surveyQuestion_id' => $surveyQuestion->aliasField('id'),
                                'surveyQuestion_name' => $surveyQuestion->aliasField('name'),
                                'surveyQuestion_field_type' => $surveyQuestion->aliasField('field_type'),
                                'surveyQuestion_parmas' => $surveyQuestion->aliasField('params'),
                                'surveyQuestion_survey_form_id' => 'JSON_UNQUOTE(JSON_EXTRACT(' . $surveyQuestion->aliasField('params') . ", '$.survey_form_id'))",
                            ])
                            ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                            [
                                $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
                            ])
                            ->where([
                                $SurveyFormsQuestions->aliasField('survey_form_id') => $surveyFormId,
                                $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                                $surveyQuestion->aliasField('field_type') => 'STUDENT_LIST',
                                $surveyQuestion->aliasField('id') => $tableQuestion,
                            ])
                            ->first();

                        $InstitutionStudentSurveysId = $InstitutionStudentSurveys_survey_form_id = '';
                        if(!empty($SurveyFormsQuestionsData)){
                            $InstitutionStudentSurveysId = $row->InstitutionStudentSurveysId;
                            $InstitutionStudentSurveys_survey_form_id = $SurveyFormsQuestionsData->surveyQuestion_survey_form_id;
                        }
                        //get all questions list
                        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                        $SurveyFormsQuestionsRes = $SurveyFormsQuestions->find()
                            ->select([
                                'id' => $SurveyFormsQuestions->aliasField('id'),
                                'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                                'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                            ])->where([
                                $SurveyFormsQuestions->aliasField('survey_form_id IS') => $InstitutionStudentSurveys_survey_form_id,
                            ])
                            ->order([$SurveyFormsQuestions->aliasField('survey_question_id') => 'ASC'])
                            ->toArray();

                        if(!empty($SurveyFormsQuestionsRes)){
                            foreach($SurveyFormsQuestionsRes AS $sfq_key => $sfq_val){
                                $InstitutionStudentSurveys = TableRegistry::getTableLocator()->get('Student.StudentSurveys');
                                $InstitutionStudentSurveyAnswers = TableRegistry::getTableLocator()->get('Student.StudentSurveyAnswers');
                                $SurveyQuestionChoices = TableRegistry::getTableLocator()->get('Survey.SurveyQuestionChoices');
                                $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');

                                $InstitutionStudentSurveyAnswersRes = $InstitutionStudentSurveyAnswers
                                    ->find()
                                    ->select([
                                        'id' => $InstitutionStudentSurveyAnswers->aliasField('id'),
                                        'text_value' => $InstitutionStudentSurveyAnswers->aliasField('text_value'),
                                        'number_value' => $InstitutionStudentSurveyAnswers->aliasField('number_value'),
                                        'decimal_value' => $InstitutionStudentSurveyAnswers->aliasField('decimal_value'),
                                        'textarea_value' => $InstitutionStudentSurveyAnswers->aliasField('textarea_value'),
                                        'date_value' => $InstitutionStudentSurveyAnswers->aliasField('date_value'),
                                        'time_value' => $InstitutionStudentSurveyAnswers->aliasField('time_value'),
                                        'file' => $InstitutionStudentSurveyAnswers->aliasField('file'),
                                        'survey_question_id' => $InstitutionStudentSurveyAnswers->aliasField('survey_question_id'),
                                        'survey_question_choices_id' => $SurveyQuestionChoices->aliasField('id'),
                                        'survey_question_choices_name' => $SurveyQuestionChoices->aliasField('name'),
                                        'institution_student_survey_id' => $InstitutionStudentSurveyAnswers->aliasField('institution_student_survey_id'),
                                    ])
                                    ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                                    [
                                        $surveyQuestion->aliasField('id') . ' = '. $InstitutionStudentSurveyAnswers->aliasField('survey_question_id'),
                                    ])
                                    ->innerJoin([$InstitutionStudentSurveys->getAlias() => $InstitutionStudentSurveys->getTable()],
                                    [
                                        $InstitutionStudentSurveys->aliasField('id') . ' = '. $InstitutionStudentSurveyAnswers->aliasField('institution_student_survey_id')
                                    ])
                                    ->leftJoin([$SurveyQuestionChoices->getAlias() => $SurveyQuestionChoices->getTable()],
                                    [
                                        $SurveyQuestionChoices->aliasField('survey_question_id') . ' = '. $InstitutionStudentSurveyAnswers->aliasField('survey_question_id'),
                                        $SurveyQuestionChoices->aliasField('id') . ' = '. $InstitutionStudentSurveyAnswers->aliasField('number_value')
                                    ])
                                    ->where([
                                        $InstitutionStudentSurveyAnswers->aliasField('institution_student_survey_id') => $InstitutionStudentSurveysId,
                                       // $InstitutionStudentSurveyAnswers->aliasField('parent_survey_question_id') => $tableQuestion,
                                        $InstitutionStudentSurveyAnswers->aliasField('survey_question_id') => $sfq_val->survey_question_id,
                                    ])
                                    ->first();

                                if(!empty($InstitutionStudentSurveyAnswersRes)){
                                    $InstitutionStudentSurveyAnswersId = $InstitutionStudentSurveyAnswersRes->id;

                                    if($InstitutionStudentSurveyAnswersRes->text_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStudentSurveyAnswersRes->text_value;
                                    }
                                    if($InstitutionStudentSurveyAnswersRes->survey_question_choices_id != "" && $InstitutionStudentSurveyAnswersRes->number_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStudentSurveyAnswersRes->survey_question_choices_name;
                                    }else if($InstitutionStudentSurveyAnswersRes->survey_question_choices_id == "" && $InstitutionStudentSurveyAnswersRes->number_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStudentSurveyAnswersRes->number_value;
                                    }
                                    if($InstitutionStudentSurveyAnswersRes->decimal_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStudentSurveyAnswersRes->decimal_value;
                                    }
                                    if($InstitutionStudentSurveyAnswersRes->textarea_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = $InstitutionStudentSurveyAnswersRes->textarea_value;
                                    }
                                    if($InstitutionStudentSurveyAnswersRes->date_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = date('Y-m-d', strtotime($InstitutionStudentSurveyAnswersRes->date_value));
                                    }
                                    if($InstitutionStudentSurveyAnswersRes->time_value != ""){
                                        $row[$this->_dynamicFieldName.'_'.$sfq_key] = date('h:i A', strtotime($InstitutionStudentSurveyAnswersRes->time_value));
                                    }
                                }else{
                                    $row[$this->_dynamicFieldName.'_'.$sfq_key] = '';
                                }
                            }
                        }
                        return $row;
                    });
                });
        }else{//POCOR-8525 ends
            //not exists
            //POCOR-8043 - Starts
            $surveyFormsFilters = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
            $institutionTypes = TableRegistry::getTableLocator()->get('Institution.InstitutionTypes');
            $surveyFormsQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
            $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
            $SurveyRows = TableRegistry::getTableLocator()->get('Survey.SurveyTableRows');
            $SurveyColumns = TableRegistry::getTableLocator()->get('Survey.SurveyTableColumns');

            $groupBy[] = $surveyForms->aliasField('id');
            $groupBy[] = $surveyQuestion->aliasField('id');
            $groupBy[] = $SurveyRows->aliasField('id');
            $groupBy[] = $institutions->aliasField('id');
            $groupBy[] = $areas->aliasField('id');
            if($institutionID > 0){
                $condition[$institutions->aliasField('id')] = $institutionID;
            }
            if ($areaId != -1 && $areaId != '' && $areaId != 0) {
                $areaIds = [];
                $allgetArea = $this->getChildren($selectedArea, $areaIds);
                $selectedArea1[]= $selectedArea;
                if(!empty($allgetArea)){
                    $allselectedAreas = array_merge($selectedArea1, $allgetArea);
                }else{
                    $allselectedAreas = $selectedArea1;
                }
                $condition[$institutions->aliasField('area_id IN')] = $allselectedAreas;
            }
            if (!empty($institutionStatus)) {
                $condition[$institutionStatuses->aliasField('name')] = $institutionStatus;
            }
            if (!empty($academicPeriodId)) {
                $condition[$this->aliasField('academic_period_id')] = $academicPeriodId;
            }
            if (!empty($tableQuestion)) {
                $condition[$surveyFormsQuestion->aliasField('survey_question_id')] = $tableQuestion;
            }

            $query->select([
                    'institution_name' => $institutions->aliasField('name'),
                    'code' => $institutions->aliasField('code'),
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'area_level_code' => $areaLevels->aliasField('level'),
                    'area_level_name' => $areaLevels->aliasField('name'),
                    'survey_code' => $surveyForms->aliasField('code'),
                    'survey_name' => $surveyForms->aliasField('name'),
                    'survey_section' => $surveyFormsQuestion->aliasField('section'),
                    'survey_question_code' => $surveyQuestion->aliasField('code'),
                    'survey_question_name' => $surveyQuestion->aliasField('name'),
                    'survey_table_row_id' => $SurveyRows->aliasField('id'),
                    'question_row' => $SurveyRows->aliasField('name')
                ])
                ->innerJoin([$surveyForms->getAlias() => $surveyForms->getTable()],
                [
                    $surveyForms->aliasField('id') . ' = '. $this->aliasField('survey_form_id')
                ])
                ->innerJoin([$surveyFormsQuestion->getAlias() => $surveyFormsQuestion->getTable()],
                [
                    $surveyFormsQuestion->aliasField('survey_form_id') . ' = '. $surveyForms->aliasField('id')
                ])
                ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                [
                    $surveyQuestion->aliasField('id') . ' = '. $surveyFormsQuestion->aliasField('survey_question_id')
                ])
                ->innerJoin([$SurveyRows->getAlias() => $SurveyRows->getTable()],
                [
                    $SurveyRows->aliasField('survey_question_id') . ' = '. $surveyQuestion->aliasField('id')
                ])
                ->innerJoin([$institutions->getAlias() => $institutions->getTable()],
                [
                    $institutions->aliasField('id') . ' = '. $this->aliasField('institution_id')
                ])
                ->innerJoin([$areas->getAlias() => $areas->getTable()],
                [
                    $areas->aliasField('id') . ' = '. $institutions->aliasField('area_id')
                ])
                ->innerJoin([$areaLevels->getAlias() => $areaLevels->getTable()],
                [
                    $areaLevels->aliasField('id') . ' = '. $areas->aliasField('area_level_id')
                ])
                ->innerJoin([$institutionStatuses->getAlias() => $institutionStatuses->getTable()],
                [
                    $institutionStatuses->aliasField('id') . ' = '. $institutions->aliasField('institution_status_id')
                ])
                ->where([
                    $condition
                ])
                ->group($groupBy)
                ->order([$SurveyRows->aliasField('order ASC'), $institutions->aliasField('name ASC')]);
            $query->formatResults(function (ResultSetInterface $results) use ($tableQuestion) {
                return $results->map(function ($row) use ($tableQuestion) {
                    $survey_table_row_id = $row->survey_table_row_id;
                    $insSurveyTblCell = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveyTableCells');
                    $surveyTableColumns = TableRegistry::getTableLocator()->get('Survey.SurveyTableColumns');
                    $institutionSurveys = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveys');
                    $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                    $insSurveyTblCellRes = $insSurveyTblCell
                        ->find()
                        ->select([
                            'text_value' => $insSurveyTblCell->aliasField('text_value'),
                            'number_value' => $insSurveyTblCell->aliasField('number_value'),
                            'decimal_value' => $insSurveyTblCell->aliasField('decimal_value'),
                            'survey_question_id' => $insSurveyTblCell->aliasField('survey_question_id'),
                            'survey_table_column_id' => $insSurveyTblCell->aliasField('survey_table_column_id'),
                            'survey_table_row_id' => $insSurveyTblCell->aliasField('survey_table_row_id'),
                            'institution_survey_id' => $insSurveyTblCell->aliasField('institution_survey_id'),
                            'survey_table_columns_id' => $surveyTableColumns->aliasField('id'),
                            'name' => $surveyTableColumns->aliasField('name'),
                            'institution_id' => $institutions->aliasField('id')
                        ])
                        ->leftJoin([$surveyTableColumns->getAlias() => $surveyTableColumns->getTable()],
                        [
                            $surveyTableColumns->aliasField('id') . ' = '. $insSurveyTblCell->aliasField('survey_table_column_id'),
                            $surveyTableColumns->aliasField('survey_question_id') . ' = '. $insSurveyTblCell->aliasField('survey_question_id')
                        ])
                        ->innerJoin([$institutionSurveys->getAlias() => $institutionSurveys->getTable()],
                        [
                            $institutionSurveys->aliasField('id') . ' = '. $insSurveyTblCell->aliasField('institution_survey_id')
                        ])
                        ->innerJoin([$institutions->getAlias() => $institutions->getTable()],
                        [
                            $institutions->aliasField('id') . ' = '. $institutionSurveys->aliasField('institution_id')
                        ])
                        ->where([
                            $insSurveyTblCell->aliasField('survey_table_row_id') => $survey_table_row_id,
                            $insSurveyTblCell->aliasField('survey_question_id') => $tableQuestion,
                            'institution_id' => $row->institution_id
                        ])
                        ->toArray();
                    if(!empty($insSurveyTblCellRes)){
                        foreach ($insSurveyTblCellRes as $ins_key => $ins_val) {
                            $row[$ins_val->name] = "";
                            if($ins_val->text_value != ""){
                                $row["'".$ins_val->name."'"] = $ins_val->text_value;
                            }
                            if($ins_val->number_value != ""){
                                $row["'".$ins_val->name."'"] = $ins_val->number_value;
                            }
                            if($ins_val->decimal_value != ""){
                                $row["'".$ins_val->name."'"] = $ins_val->decimal_value;
                            }
                        }
                    }
                    return $row;
                });
            });
            //POCOR-8043 - Ends
        }
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ])
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

    // POCOR-9087
    public function checkSurveyExistanceInRepeater($institutions=[], $academicPeriodId, $surveyFormId, $surveySection, $tableQuestion){

        $childSurveys = TableRegistry::getTableLocator()->get('InstitutionRepeater.RepeaterSurveys');
        $surveySectionId = "$surveySection";
        $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
        $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();

        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
        $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
        $StaffSurveysRes = $SurveyFormsQuestions->find()
            ->select([
                'id' => $childSurveys->aliasField('id'),
                'parent_form_id' => $childSurveys->aliasField('parent_form_id'),
            ])
            ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                [
                    $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
                ])
            ->innerJoin([$childSurveys->getAlias() => $childSurveys->getTable()],
                [
                    $SurveyFormsQuestions->aliasField('survey_form_id') . ' = '. $childSurveys->aliasField('parent_form_id')
                ])
            ->where([
                $SurveyFormsQuestions->aliasField('survey_form_id') => $surveyFormId,
                $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                $surveyQuestion->aliasField('field_type') => 'REPEATER',
                $surveyQuestion->aliasField('id') => $tableQuestion,
                $childSurveys->aliasField('institution_id IN') => $institutions,
                $childSurveys->aliasField('academic_period_id') => $academicPeriodId,
                $childSurveys->aliasField('parent_form_id') => $surveyFormId
            ])->toArray();

        return $StaffSurveysRes;    }

    public function checkSurveyExistanceInStaff($institutions=[], $academicPeriodId, $surveyFormId, $surveySection, $tableQuestion){
        $childSurveys = TableRegistry::getTableLocator()->get('Staff.StaffSurveys'); // POCOR-9087
        $surveySectionId = "$surveySection";
        $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
        $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();

        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
        $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
        $StaffSurveysRes = $SurveyFormsQuestions->find()
            ->select([
                'id' => $childSurveys->aliasField('id'),
                'parent_form_id' => $childSurveys->aliasField('parent_form_id'),
            ])
            ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
            [
                $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
            ])
            ->innerJoin([$childSurveys->getAlias() => $childSurveys->getTable()],
            [
                $SurveyFormsQuestions->aliasField('survey_form_id') . ' = '. $childSurveys->aliasField('parent_form_id')
            ])
            ->where([
                $SurveyFormsQuestions->aliasField('survey_form_id') => $surveyFormId,
                $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                $surveyQuestion->aliasField('field_type') => 'STAFF_LIST',
                $surveyQuestion->aliasField('id') => $tableQuestion,
                $childSurveys->aliasField('institution_id IN') => $institutions,
                $childSurveys->aliasField('academic_period_id') => $academicPeriodId,
                $childSurveys->aliasField('parent_form_id') => $surveyFormId
            ])->toArray();

        return $StaffSurveysRes;
    }

    public function checkSurveyExistanceInStudent($institutions=[], $academicPeriodId, $surveyFormId, $surveySection, $tableQuestion){
        $childSurveys = TableRegistry::getTableLocator()->get('Student.StudentSurveys'); // POCOR-9087
        $surveySectionId = "$surveySection";
        $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
        $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();

        $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
        $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
        $StudentSurveysRes = $SurveyFormsQuestions->find()
            ->select([
                'id' => $childSurveys->aliasField('id'),
                'parent_form_id' => $childSurveys->aliasField('parent_form_id'),
            ])
            ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
            [
                $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
            ])
            ->innerJoin([$childSurveys->getAlias() => $childSurveys->getTable()],
            [
                $SurveyFormsQuestions->aliasField('survey_form_id') . ' = '. $childSurveys->aliasField('parent_form_id')
            ])
            ->where([
                $SurveyFormsQuestions->aliasField('survey_form_id') => $surveyFormId,
                $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                $surveyQuestion->aliasField('field_type') => 'STUDENT_LIST',
                $surveyQuestion->aliasField('id') => $tableQuestion,
                $childSurveys->aliasField('institution_id IN') => $institutions,
                $childSurveys->aliasField('academic_period_id') => $academicPeriodId,
                $childSurveys->aliasField('parent_form_id') => $surveyFormId
            ])->toArray();
        return $StudentSurveysRes;
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $tableQuestion = $requestData->table_question;
        $surveySection = $requestData->survey_section;
        //POCOR-8525 starts find record is exist in `institution_repeater_surveys` table for Repeater case
        $institutionID = $requestData->institution_id;
        if($institutionID <= 0){
            $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $InstitutionsData = $Institutions->find()->toArray();
            if(!empty($InstitutionsData)){
                $instArr = [];
                foreach($InstitutionsData AS $inst_key => $inst_val){
                    $instArr[] = $inst_val['id'];
                }
            }
        }else{
            $instArr[] = $institutionID;
        }

        $repeaterListCountResult = $this->checkSurveyExistanceInRepeater($instArr, $requestData->academic_period_id, $requestData->survey_form_id, $surveySection, $tableQuestion);
        $staffListCountResult = $this->checkSurveyExistanceInStaff($instArr, $requestData->academic_period_id, $requestData->survey_form_id, $surveySection, $tableQuestion);
        $studentListCountResult = $this->checkSurveyExistanceInStudent($instArr, $requestData->academic_period_id, $requestData->survey_form_id, $surveySection, $tableQuestion);
        //if record exists
        if((count($repeaterListCountResult) > 0) || (count($staffListCountResult) > 0) || (count($studentListCountResult) > 0)){
            foreach ($fields as $key => $field) {
                if ($field['field'] == 'survey_form_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'status_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'assignee_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'institution_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'academic_period_id') {
                    unset($fields[$key]);
                }
            }
            unset($fields[3]);//used for remove insittuion column from array
            unset($fields[1]);//used for remove Academic Period column from array
            $fields[] = [
                'key' => 'survey_status',
                'field' => 'survey_status',
                'type' => 'string',
                'label' => __('Status')
            ];

            $fields[] = [
                'key' => 'academic_period_name',
                'field' => 'academic_period_name',
                'type' => 'integer',
                'label' => __('Academic Periods')
            ];

            $fields[] = [
                'key' => 'survey_form_name',
                'field' => 'survey_form_name',
                'type' => 'string',
                'label' => __('Survey Form')
            ];

            $fields[] = [
                'key' => 'assignee_name',
                'field' => 'assignee_name',
                'type' => 'string',
                'label' => __('Assignee Name')
            ];

            $fields[] = [
                'key' => 'code',
                'field' => 'code',
                'type' => 'string',
                'label' => __('Code')
            ];

            $fields[] = [
                'key' => 'institution_name',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];

            $fields[] = [
                'key' => 'area_education_name',
                'field' => 'area_education_name',
                'type' => 'string',
                'label' => __('Area Education')
            ];

            $fields[] = [
                'key' => 'area_administrative_name',
                'field' => 'area_administrative_name',
                'type' => 'string',
                'label' => __('Area Administrative')
            ];

            $fields[] = [
                'key' => 'institution_status_name',
                'field' => 'institution_status_name',
                'type' => 'string',
                'label' => __('Institution Status')
            ];

            // $fields[] = [
            //     'key' => 'InstitutionRepeaterSurveysId',
            //     'field' => 'InstitutionRepeaterSurveysId',
            //     'type' => 'string',
            //     'label' => __('InstitutionRepeaterSurveysId')
            // ];

            $SurveyFormId = 0;
            if((count($repeaterListCountResult) > 0)){
                $SurveyFormId = $repeaterListCountResult[0]->parent_form_id;
                $fieldType = 'REPEATER';
            }else if((count($staffListCountResult) > 0)){
                $SurveyFormId = $staffListCountResult[0]->parent_form_id;
                $fieldType = 'STAFF_LIST';
            }else if((count($studentListCountResult) > 0)){
                $SurveyFormId = $studentListCountResult[0]->parent_form_id;
                $fieldType = 'STUDENT_LIST';
            }

            $surveySectionId = "$requestData->survey_section";
            $surveySection = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
            $surveySectionData = $surveySection->find()->where([ $surveySection->aliasField('id') => $surveySectionId ])->first();

            $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
            $surveyQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyQuestions');
            $SurveyFormsQuestionsData = $SurveyFormsQuestions->find()
                ->select([
                    'id' => $SurveyFormsQuestions->aliasField('id'),
                    'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                    'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                    'surveyQuestion_id' => $surveyQuestion->aliasField('id'),
                    'surveyQuestion_name' => $surveyQuestion->aliasField('name'),
                    'surveyQuestion_field_type' => $surveyQuestion->aliasField('field_type'),
                    'surveyQuestion_parmas' => $surveyQuestion->aliasField('params'),
                    'surveyQuestion_survey_form_id' => 'JSON_UNQUOTE(JSON_EXTRACT(' . $surveyQuestion->aliasField('params') . ", '$.survey_form_id'))",
                ])
                ->innerJoin([$surveyQuestion->getAlias() => $surveyQuestion->getTable()],
                [
                    $surveyQuestion->aliasField('id') . ' = '. $SurveyFormsQuestions->aliasField('survey_question_id')
                ])
                ->where([
                    $SurveyFormsQuestions->aliasField('survey_form_id') => $SurveyFormId,
                    $SurveyFormsQuestions->aliasField('section IS') => $surveySectionData->section,
                    $surveyQuestion->aliasField('field_type') => $fieldType,
                    $surveyQuestion->aliasField('id') => $tableQuestion,
                ])->first();
            if($SurveyFormsQuestionsData){
                $SurveyFormsQuestions = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
                $SurveyFormsQuestionsRes = $SurveyFormsQuestions->find()
                    ->select([
                        'id' => $SurveyFormsQuestions->aliasField('id'),
                        'survey_question_id' => $SurveyFormsQuestions->aliasField('survey_question_id'),
                        'survey_form_id' => $SurveyFormsQuestions->aliasField('survey_form_id'),
                        'name' => $SurveyFormsQuestions->aliasField('name'),
                        'section' => $SurveyFormsQuestions->aliasField('section'),
                    ])->where([
                        $SurveyFormsQuestions->aliasField('survey_form_id') => $SurveyFormsQuestionsData->surveyQuestion_survey_form_id,
                    ])
                    ->order([$SurveyFormsQuestions->aliasField('survey_question_id') => 'ASC'])
                    ->toArray();

                if(!empty($SurveyFormsQuestionsRes)){
                    foreach ($SurveyFormsQuestionsRes as $ins_key => $ins_val) {
                        $fields[] = [
                            'key' => '',
                            'field' => $this->_dynamicFieldName.'_'.$ins_key,
                            'type' => 'string',
                            'label' => __($ins_val->name)
                        ];
                    }
                }
            }
        }else{ //POCOR-8525 Ends
            $tableQuestionId = $requestData->table_question;
            foreach ($fields as $key => $field) {
                if ($field['field'] == 'survey_form_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'status_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'assignee_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'institution_id') {
                    unset($fields[$key]);
                }
                if ($field['field'] == 'academic_period_id') {
                    $fields[$key] = [
                        'key' => 'Surveys.academic_period_id',
                        'field' => 'academic_period_id',
                        'type' => 'integer',
                        'label' => 'Academic Period'
                    ];
                    break;
                }
            }
            unset($fields[3]);//used for remove insittuion column from array

            $fields[] = [
                'key' => 'area_level_code',
                'field' => 'area_level_code',
                'type' => 'string',
                'label' => __('Area Level Code')
            ];
            $fields[] = [
                'key' => 'area_level_name',
                'field' => 'area_level_name',
                'type' => 'integer',
                'label' => __('Area Level Name')
            ];
            $fields[] = [
                'key' => 'area_code',
                'field' => 'area_code',
                'type' => 'string',
                'label' => __('Area Education Code')
            ];
            $fields[] = [
                'key' => 'area_name',
                'field' => 'area_name',
                'type' => 'string',
                'label' => __('Area Education Name')
            ];
            $fields[] = [
                'key' => 'code',
                'field' => 'code',
                'type' => 'string',
                'label' => __('Institution Code')
            ];
            $fields[] = [
                'key' => 'institution_name',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution Name')
            ];
            $fields[] = [
                'key' => 'survey_code',
                'field' => 'survey_code',
                'type' => 'string',
                'label' => __('Survey Code')
            ];
            $fields[] = [
                'key' => 'survey_name',
                'field' =>'survey_name',
                'type' => 'string',
                'label' => __('Survey Name')
            ];
            $fields[] = [
                'key' => 'survey_section',
                'field' =>'survey_section',
                'type' => 'string',
                'label' => __('Survey Section')
            ];
            $fields[] = [
                'key' => 'survey_question_code',
                'field' =>'survey_question_code',
                'type' => 'string',
                'label' => __('Survey Question Code')
            ];
            $fields[] = [
                'key' => 'survey_question_name',
                'field' =>'survey_question_name',
                'type' => 'string',
                'label' => __('Survey Question Name')
            ];

            $SurveyTblColumns = TableRegistry::getTableLocator()->get('Survey.SurveyTableColumns');
            $surveyFormsQuestion = TableRegistry::getTableLocator()->get('Survey.SurveyFormsQuestions');
            $SurveyTblColumnRes = $SurveyTblColumns
                ->find()
                ->select([
                    'survey_column_id' => $SurveyTblColumns->aliasField('id'),
                    'survey_column_name' => $SurveyTblColumns->aliasField('name'),
                    'survey_column_order' => $SurveyTblColumns->aliasField('order')
                ])
                ->LeftJoin([$surveyFormsQuestion->getAlias() => $surveyFormsQuestion->getTable()],
                    [
                        $surveyFormsQuestion->aliasField('survey_question_id') . ' = '. $SurveyTblColumns->aliasField('survey_question_id')
                    ])
                ->where([$surveyFormsQuestion->aliasField('survey_question_id') => $tableQuestionId])
                ->toArray();
            if(!empty($SurveyTblColumnRes)){
                foreach ($SurveyTblColumnRes as $S_key => $S_val) {
                    if($S_val->survey_column_order == 1){
                        $fields[] = [
                            'key' => 'question_row',
                            'field' =>'question_row',
                            'type' => 'string',
                            'label' => $S_val->survey_column_name
                        ];
                    }else{
                        $fields[] = [
                            'key' => '',
                            'field' => "'".$S_val->survey_column_name."'",
                            'type' => 'string',
                            'label' => $S_val->survey_column_name
                        ];
                    }
                }
            }
        }
    }
    /**
     * POCOR-8929 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $alias, array $options = []): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($alias, $options);
        } catch (\Exception $exception) {

        }
        $parts = explode('.', $alias);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($alias);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias, $options);
    }
}
//End of POCOR-6695
