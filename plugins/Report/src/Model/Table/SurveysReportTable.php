<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\I18n\Date;

 //POCOR-6695
class SurveysReportTable extends AppTable
{
    private $surveyStatuses = [];
    const OPEN = 1;
    const PENDINGAPPROVAL = 2;
    const COMPLETED = 3;
    const SURVEY_DISABLED = -1;

    public function initialize(array $config)
    {
        $this->table('institution_surveys');
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AreaLevels', ['className' => 'AreaLevel.AreaLevels']);

        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('survey_form', ['type' => 'hidden']);
        $this->ControllerAction->field('survey_section', ['type' => 'hidden']);
        
        $this->ControllerAction->field('table_question', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_id', ['attr' => ['label' => __('Area Education')]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_status');
        $this->ControllerAction->field('status', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }

    public function onExcelAfterHeader(Event $event, ArrayObject $settings)
    {
       if ($settings['renderNotComplete'] || $settings['renderNotOpen']) {
            $fields = $settings['sheet']['fields'];
            $requestData = json_decode($settings['process']['params']);
            $surveyFormId = $requestData->survey_form;
            $academicPeriodId = $requestData->academic_period_id;
            $institutionStatus = $requestData->institution_status;
            $institution_id = $requestData->institution_id;
            $areaId = $requestData->area_id;
            $condition = [];
            if ($institution_id != 0) {
                $condition['Institutions.id'] = $institution_id;
            }
            if ($areaId != -1) {
                $condition['Institutions.area_id'] = $areaId;
            }
            $institutionFormStatus = [];
            if ($institutionStatus == "Active") {
                $institutionFormStatus = 1;
            }
            if ($institutionStatus == "Inactive") {
                $institutionFormStatus = 2;
            }

            $surveyFormName = $this->SurveyForms->get($surveyFormId)->name;
            $academicPeriodName = $this->AcademicPeriods->get($academicPeriodId)->name;
            $userId = $requestData->user_id;
            $superAdmin = $requestData->super_admin;

            $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
            $institutionType = $SurveyFormsFilters->find()
                ->where([
                    $SurveyFormsFilters->aliasField('survey_form_id').' = '.$surveyFormId,
                ])
                ->select([ 'institution_type_id' => $SurveyFormsFilters->aliasField('survey_filter_id') ]);

            $InstitutionsTable = $this->Institutions;

            if($settings['renderNotComplete']){
                $notCompleteRecords = $InstitutionsTable->find()
                ->where(['NOT EXISTS ('.
                    $this->find()->where([
                        $this->aliasField('academic_period_id').' = '.$academicPeriodId,
                        $this->aliasField('survey_form_id').' = '.$surveyFormId,
                        $this->aliasField('institution_id').' = '.$InstitutionsTable->aliasField('id')
                    ])
                .')'])
                ->where([
                            $InstitutionsTable->aliasField('institution_status_id') => $institutionFormStatus,
                            $condition
                        ])
                ->innerJoinWith('Areas')
                ->leftJoinWith('AreaAdministratives')
                ->select([
                    'institution_id' => $InstitutionsTable->aliasField('name'),
                    'code' => $InstitutionsTable->aliasField('code'),
                    'area' => 'Areas.name',
                    'area_administrative' => 'AreaAdministratives.name'
                ]);


                if ($institutionType->cleanCopy()->first()->institution_type_id) {
                $notCompleteRecords->where([
                    $InstitutionsTable->aliasField('institution_type_id').' IN ('.$institutionType.')'
                ]);
            }

            if (!$superAdmin) {
                $notCompleteRecords->find('ByAccess', ['userId' => $userId]);
            }

            $writer = $settings['writer'];
            $sheetName = $settings['sheet']['name'];
            $mappingArray = ['academic_period_id', 'survey_form_id', 'institution_id', 'code'];

            foreach ($notCompleteRecords->all() as $record) {

                $surveyFormCount = $this->SurveyForms->find()
                    ->select([
                        'SurveyForms.id',
                        'SurveyForms.code',
                        'SurveyForms.name',
                        'SurveyStatuses.date_enabled',
                        'SurveyStatuses.date_disabled',
                        'SurveyStatusPeriods.academic_period_id',
                    ])
                    ->leftJoin(['SurveyStatuses' => 'survey_statuses'], [
                        'SurveyStatuses.survey_form_id = SurveyForms.id'
                    ])
                    ->leftJoin(['SurveyStatusPeriods' => 'survey_status_periods'], [
                        'SurveyStatusPeriods.survey_status_id = SurveyStatuses.id'
                    ])
                    ->where(['SurveyForms.id' => $surveyFormId,
                        'SurveyStatusPeriods.academic_period_id' => $academicPeriodId,
                        'DATE(SurveyStatuses.date_disabled) >= ' => date('Y-m-d')
                        ])
                    ->count();

                $record->status_id = __('Not Completed');

                if( $surveyFormCount > 0){
                    $record->status_id = __('Open');
                }



                $record->academic_period_id = $academicPeriodName;
                $record->survey_form_id = $surveyFormName;



                $row = [];
                foreach ($fields as $field) {
                    if (in_array($field['field'], $mappingArray)) {
                        $row[] = __($record->{$field['field']});
                    } else if ($field['field'] == 'area') {
                        $row[] = __($record->area);
                    } else if ($field['field'] == 'institution_statusActive') {
                        $row[] = __('Active');
                    }
                    else if ($field['field'] == 'institution_statusInactive') {
                        $row[] = __('Inactive');
                    }
                    else if ($field['field'] == 'area_administrative') {
                        $row[] = __($record->area_administrative);
                    }
                    else {
                        $row[] = '';
                    }
                }
                $writer->writeSheetRow($sheetName, $row);
                }
            }

           if($settings['renderNotOpen']){
            $notOpenRecords = $InstitutionsTable->find()
                ->where(['EXISTS ('.
                    $this->find()->where([
                        $this->aliasField('academic_period_id').' = '.$academicPeriodId,
                        $this->aliasField('survey_form_id').' = '.$surveyFormId,
                        $this->aliasField('institution_id').' = '.$InstitutionsTable->aliasField('id'),
                        $this->aliasField('status_id').' IN ('.self::SURVEY_DISABLED.','.self::OPEN.','.self::PENDINGAPPROVAL.')'
                    ])
                .')'])
                ->where([
                            $InstitutionsTable->aliasField('institution_status_id') => $institutionFormStatus,
                            $condition
                        ])
                ->innerJoinWith('Areas')
                ->leftJoinWith('AreaAdministratives')
                ->select([
                    'institution_id' => $InstitutionsTable->aliasField('name'),
                    'institutionId' => $InstitutionsTable->aliasField('id'),
                    'code' => $InstitutionsTable->aliasField('code'),
                    'area' => 'Areas.name',
                    'area_administrative' => 'AreaAdministratives.name'
                ]);


                if ($institutionType->cleanCopy()->first()->institution_type_id) {
                $notOpenRecords->where([
                    $InstitutionsTable->aliasField('institution_type_id').' IN ('.$institutionType.')'
                ]);
            }

            if (!$superAdmin) {
                $notOpenRecords->find('ByAccess', ['userId' => $userId]);
            }

            $writer = $settings['writer'];
            $sheetName = $settings['sheet']['name'];
            $mappingArray = ['academic_period_id', 'survey_form_id'];

            foreach ($notOpenRecords->all() as $record) {
                $record->academic_period_id = $academicPeriodName;
                $record->survey_form_id = $surveyFormName;

                $countDisabledSurveyInstitution = $this->find('list',[
                'keyField' => 'institution_id',
                'valueField' => 'status_id',
                ])->where([
                    $this->aliasField('academic_period_id') .' = '. $academicPeriodId,
                    $this->aliasField('survey_form_id') .' = '. $surveyFormId
                ])->count();

                $writer->writeSheetRow($sheetName);
             }
            }

            $settings['renderNotComplete'] = false;
            $settings['renderNotOpen'] = false;

        }

      }

    
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        //Setting request data and modifying fetch condition
        // $requestData = json_decode($settings['process']['params']);
        // $surveyFormId = $requestData->survey_form;
        // $academicPeriodId = $requestData->academic_period_id;
        // $status = $requestData->status;
        // $institutionStatus = $requestData->institution_status;
        // $areaId = $requestData->area_id;

        // $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        // if (!empty($academicPeriodId) && empty($areaId)) { ////POCOR-7046
        //     $surveyStatuses = $WorkflowStatusesTable->WorkflowModels->getWorkflowStatusesCode('Institution.InstitutionSurveys');

        //     if($status == '' || $status == 'all'){
        //           $settings['renderNotOpen'] = true;
        //           $settings['renderNotComplete'] = true;

        //     } elseif ($surveyStatuses[$status] == 'Open') {

        //           $settings['renderNotOpen'] = true;
        //           $settings['renderNotComplete'] = false;

        //     } elseif (  !$status || $surveyStatuses[$status] == 'NOT_COMPLETED') {
        //           $settings['renderNotOpen'] = false;
        //           $settings['renderNotComplete'] = true;

        //     } else {
        //           $settings['renderNotOpen'] = false;
        //           $settings['renderNotComplete'] = false;
        //     }
        // }//Start POCOR-7046
        // else if (!empty($areaId)) {
        //     if($status == '' || $status == 'all'){
        //           $settings['renderNotOpen'] = false;
        //           $settings['renderNotComplete'] = true;

        //     } 
        //     // elseif ($surveyStatuses[$status] == 'Open') {

        //     //       $settings['renderNotOpen'] = true;
        //     //       $settings['renderNotComplete'] = false;

        //     // } 
        //     // elseif (  !$status || $surveyStatuses[$status] == 'NOT_COMPLETED') {
        //     //       $settings['renderNotOpen'] = false;
        //     //       $settings['renderNotComplete'] = true;

        //     // } 
        //     else {
        //           $settings['renderNotOpen'] = false;
        //           $settings['renderNotComplete'] = false;
        //     }
        // }//End POCOR-7046
        // else {
        //     $academicPeriodId = 0;
        // }

        // $configCondition = [];
        // $condition = [
        //     $this->aliasField('academic_period_id') => $academicPeriodId
        // ];

        //if (!$status) $status = array_keys($surveyStatuses);

        //$surveyStatuses = $WorkflowStatusesTable->getWorkflowSteps($status);

        //$this->surveyStatuses = $WorkflowStatusesTable->getWorkflowStepStatusNameMappings('Institution.InstitutionSurveys');
        // if (!empty($surveyStatuses) || $status == '' || $status == 'all') {
            /*POCOR-6600 - removed all conditions, as report must have all status ids when all option selected*/
            // if($settings['renderNotComplete'] === true && $settings['renderNotOpen'] === true){
            //     $statusCondition = [
            //         $this->aliasField('status_id').' IN ('.self::OPEN.')'
            //     ];
            // }elseif($settings['renderNotComplete'] === true && $settings['renderNotOpen'] === false){
            //     $statusCondition = [
            //         $this->aliasField('status_id').' NOT IN ('.self::OPEN.', '.self::PENDINGAPPROVAL.', '.self::COMPLETED.' )'
            //     ];
            // }else{
            //     $statusCondition = [
            //         $this->aliasField('status_id').' IN ' => array_keys($surveyStatuses)
            //     ];
            // }
            // $statusCondition = [];
            // $condition = array_merge($condition, $statusCondition);
        //}
        //$condition = array_merge($condition, $configCondition);

        // $this->setCondition($condition);

        //For Surveys only
        // $forms = $this->getForms($surveyFormId);
        // foreach ($forms as $formId => $formName) {
        //     $this->excelContent($sheets, $formName, null, $formId);
        // }

        // Stop the customfieldlist behavior onExcelBeforeStart function
        $event->stopPropagation();

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $surveyForms = TableRegistry::get('survey_forms');
        $surveyFormsFilters = TableRegistry::get('survey_forms_filters');
        $institutionTypes = TableRegistry::get('institution_types');
        $institutions = TableRegistry::get('institutions');
        $surveyFormsQuestion = TableRegistry::get('survey_forms_questions');
        $surveyQuestion = TableRegistry::get('survey_questions');
        $SurveyCells = TableRegistry::get('institution_survey_table_cells');
        $SurveyRows = TableRegistry::get('survey_table_rows');
        $SurveyColumns = TableRegistry::get('survey_table_columns');
        $SurveyColumns = TableRegistry::get('survey_table_columns');
        // $Areas = TableRegistry::get('areas');
        // $AreaLevels = TableRegistry::get('area_levels');
        $Areas = TableRegistry::get('AreaLevel.AreaLevels');


        
        $condition = [];
        // POCOR-6440 start
        $requestData = json_decode($settings['process']['params']);
        $institutionID = $requestData->institution_id;
        if($institutionID > 0){
            $condition['Institutions.id'] = $institutionID;
        }
        // POCOR-6440 end

          $query->select([
                    'institution_name' => 'Institutions.name',
                    'code' => 'Institutions.code',
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'area_level_code' => 'Areas.code',
                    'area_level_name' => 'Areas.name',
                    'survey_code' => $surveyForms->aliasField('code'),
                    'survey_name' => $surveyForms->aliasField('name'),
                    'survey_section' => $surveyFormsQuestion->aliasField('section'),
                    'survey_question_code' => $surveyQuestion->aliasField('code'),
                    'survey_question_name' => $surveyQuestion->aliasField('name'),
                    'question_row' => $SurveyRows->aliasField('name'),
                    'question_grade_1' => $SurveyCells->aliasField('text_value'),
                    'question_grade_2' => $SurveyCells->aliasField('text_value'),
                    'question_grade_3' => $SurveyCells->aliasField('text_value')


                ])
                ->leftJoin([$surveyForms->alias() => $surveyForms->table()],
                [
                    $surveyForms->aliasField('id') . ' = '. $this->aliasField('survey_form_id')
                ])
                ->leftJoin([$surveyFormsQuestion->alias() => $surveyFormsQuestion->table()],
                [
                    $surveyFormsQuestion->aliasField('survey_form_id') . ' = '. $surveyForms->aliasField('id')
                ])
                ->leftJoin([$surveyQuestion->alias() => $surveyQuestion->table()],
                [
                    $surveyQuestion->aliasField('id') . ' = '. $surveyFormsQuestion->aliasField('survey_question_id')
                ])
                ->leftJoin([$SurveyRows->alias() => $SurveyRows->table()],
                [
                    $SurveyRows->aliasField('survey_question_id') . ' = '. $surveyQuestion->aliasField('id')
                ])
                ->leftJoin([$SurveyCells->alias() => $SurveyCells->table()],
                [
                    $SurveyCells->aliasField('survey_question_id') . ' = '. $surveyQuestion->aliasField('id')
                ])
                // ->leftJoin([$SurveyCells->alias() => $SurveyCells->table()],
                // [
                //     $SurveyCells->aliasField('survey_table_column_id') . ' = '. $SurveyColumns->aliasField('id')
                // ])

                ->leftJoin(['SurveyFormsFilters' => 'survey_forms_filters'], [
                    'SurveyFormsFilters.survey_form_id = '. $surveyForms->aliasField('id')
                ])
                ->leftJoin(['InstitutionTypes' => 'institution_types'], [
                    'SurveyFormsFilters.survey_filter_id = InstitutionTypes.id'
                ])
                ->leftJoin(['Institutions' => 'institutions'], [
                    'InstitutionTypes.id = Institutions.institution_type_id'
                ])
                ->leftJoin(['Institutions' => 'institutions'], [
                    'Areas.id = Institutions.area_id'
                ])
                ->leftJoin(['Areas' => 'Areas'], [
                    'AreaLevels.id = Areas.area_level_id'
                ])
                ->contain([
                    'Institutions.Areas',
                    'Institutions.AreaAdministratives',
                    'Institutions.Statuses'
                ])
                ->where([$condition]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        //$requestData = json_decode($settings['process']['params']);

        // $institutionStatus = $requestData->institution_status;

        // To update to this code when upgrade server to PHP 5.5 and above
        //unset($fields[array_search('status_id', array_column($fields, 'field'))]);

        foreach ($fields as $key => $field) {
            if ($field['field'] == 'institution_id') {
                unset($fields[$key]);
            }
            if ($field['field'] == 'survey_form_id') {
                unset($fields[$key]);
            }
            
            if ($field['field'] == 'status_id') {
                unset($fields[$key]);
             }
            if ($field['field'] == 'assignee_id') {
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
        $fields[] = [
            'key' => 'question_row',
            'field' =>'question_row',
            'type' => 'string',
            'label' => __('Question Row')
        ];

        $fields[] = [
            'key' => 'question_grade_1',
            'field' =>'question_grade_1',
            'type' => 'string',
            'label' => __('Question Column - Grade 1')
        ];

        $fields[] = [
            'key' => 'question_grade_2',
            'field' =>'question_grade_2',
            'type' => 'string',
            'label' => __('Question Column - Grade 2')
        ];

        $fields[] = [
            'key' => 'question_grade_3',
            'field' =>'question_grade_3',
            'type' => 'string',
            'label' => __('Question Column - Grade 3')
        ];


    }




}
//End of POCOR-6695
