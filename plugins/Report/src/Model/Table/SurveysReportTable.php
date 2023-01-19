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
use Cake\Datasource\ResultSetInterface;

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
       /*if (isset($settings['renderNotComplete']) || isset($settings['renderNotOpen'])) {
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

        }*/

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
       
        $SurveyRows = TableRegistry::get('survey_table_rows');
        $SurveyColumns = TableRegistry::get('survey_table_columns');
        $areas = TableRegistry::get('areas');
        $areaLevels = TableRegistry::get('area_levels');
        $Areas = TableRegistry::get('AreaLevel.AreaLevels');
        $condition = [];
        // POCOR-6440 start
        $requestData = json_decode($settings['process']['params']);
        //echo "<pre>"; print_r($requestData); die;
        $institutionID = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $surveySection = $requestData->survey_section;
        $tableQuestion = $requestData->table_question;
        if($institutionID > 0){
            $condition['Institutions.id'] = $institutionID;
        }
        if (!empty($academicPeriodId)) {
            $condition[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        /*if (!empty($surveySection)) {
            $condition[$surveyFormsQuestion->aliasField('id')] = $surveySection;
        }*/
        if (!empty($tableQuestion)) {
            $condition[$surveyFormsQuestion->aliasField('survey_question_id')] = $tableQuestion;
        }
        // POCOR-6440 end

        $query->select([
                'institution_name' => 'Institutions.name',
                'code' => 'Institutions.code',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
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
            ->innerJoin([$surveyForms->alias() => $surveyForms->table()],
            [
                $surveyForms->aliasField('id') . ' = '. $this->aliasField('survey_form_id')
            ])
            ->innerJoin([$surveyFormsQuestion->alias() => $surveyFormsQuestion->table()],
            [
                $surveyFormsQuestion->aliasField('survey_form_id') . ' = '. $surveyForms->aliasField('id')
            ])
            ->innerJoin([$surveyQuestion->alias() => $surveyQuestion->table()],
            [
                $surveyQuestion->aliasField('id') . ' = '. $surveyFormsQuestion->aliasField('survey_question_id')
            ])
            ->innerJoin([$SurveyRows->alias() => $SurveyRows->table()],
            [
                $SurveyRows->aliasField('survey_question_id') . ' = '. $surveyQuestion->aliasField('id')
            ])
            /*->innerJoin([$SurveyCells->alias() => $SurveyCells->table()],
            [
                $SurveyCells->aliasField('survey_question_id') . ' = '. $surveyQuestion->aliasField('id')
            ])*/
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
            ->innerJoin([$areaLevels->alias() => $areaLevels->table()],
            [
                $areaLevels->aliasField('id') . ' = '. $areas->aliasField('area_level_id')
            ])
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'Institutions.Statuses'
            ])
            ->where([
                $condition
            ])
            ->group([$SurveyRows->aliasField('name')])
            ->order([$SurveyRows->aliasField('order ASC')]);

        $query->formatResults(function (ResultSetInterface $results) use ($tableQuestion) {
            return $results->map(function ($row) use ($tableQuestion) {
                $survey_table_row_id = $row->survey_table_row_id;

                /*$surveyTblRows = TableRegistry::get('survey_table_rows');
                $surveyTblColumns = TableRegistry::get('survey_table_columns');
                $surveyTblRowsRes = $surveyTblRows
                    ->find()
                    ->select([
                        'id' => $surveyTblRows->aliasField('id'),
                        'name' => $surveyTblRows->aliasField('name'),
                        'order' => $surveyTblRows->aliasField('order'),
                        'survey_question_id' => $surveyTblRows->aliasField('survey_question_id'),
                        'survey_table_columns_id' => $surveyTblColumns->aliasField('id'),
                        'survey_table_columns_name' => $surveyTblColumns->aliasField('name')
                    ])
                    ->leftJoin([$surveyTblColumns->alias() => $surveyTblColumns->table()],
                    [
                        $surveyTblColumns->aliasField('survey_question_id') . ' = '. $surveyTblRows->aliasField('survey_question_id'),
                        $surveyTblColumns->aliasField('order') . ' = '. 1
                    ])
                    ->where([
                        $surveyTblRows->aliasField('id') => $survey_table_row_id,
                        $surveyTblRows->aliasField('survey_question_id') => $tableQuestion
                    ])
                    ->first();
                if(!empty($surveyTblRowsRes)){
                    $row["'".$surveyTblRowsRes->survey_table_columns_name."'"] = "";    
                    if($surveyTblRowsRes->name != ""){
                        $row["'".$surveyTblRowsRes->survey_table_columns_name."'"] = $surveyTblRowsRes->name;
                    }
                }*/

                $insSurveyTblCell = TableRegistry::get('institution_survey_table_cells');
                $surveyTableColumns = TableRegistry::get('survey_table_columns');
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
                        'name' => $surveyTableColumns->aliasField('name')
                    ])
                    ->leftJoin([$surveyTableColumns->alias() => $surveyTableColumns->table()],
                    [
                        $surveyTableColumns->aliasField('id') . ' = '. $insSurveyTblCell->aliasField('survey_table_column_id')
                    ])
                    ->where([
                        $insSurveyTblCell->aliasField('survey_table_row_id') => $survey_table_row_id,
                        $insSurveyTblCell->aliasField('survey_question_id') => $tableQuestion
                    ])
                    ->first();
                //echo "<pre>"; print_r($insSurveyTblCellRes); die;
                //$row = [];
                if(!empty($insSurveyTblCellRes)){
                    $row[$insSurveyTblCellRes->name] = "";    
                    if($insSurveyTblCellRes->text_value != ""){
                        $row["'".$insSurveyTblCellRes->name."'"] = $insSurveyTblCellRes->text_value;
                    }
                    if($insSurveyTblCellRes->number_value != ""){
                        $row["'".$insSurveyTblCellRes->name."'"] = $insSurveyTblCellRes->number_value;
                    }
                    if($insSurveyTblCellRes->decimal_value != ""){
                        $row["'".$insSurveyTblCellRes->name."'"] = $insSurveyTblCellRes->decimal_value;
                    }
                } 
                //echo "<pre>"; print_r($insSurveyTblCellRes); die;       
                return $row;
                //echo "<pre>"; print_r($insSurveyTblCellRes); die;
                
            });
        });
        //echo "<pre>"; print_r($query); die;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        
        $requestData = json_decode($settings['process']['params']);
        $tableQuestionId = $requestData->table_question;
        // $institutionStatus = $requestData->institution_status;

        //echo "<pre>"; print_r($fields); die;    
        /*foreach ($fields as $key => $field) {
            if ($field['field'] == 'institution_id' || $field['key'] == 'SurveysReport.institution_id') {
                /*echo "<pre>"; print_r($field['field']); 
                echo "<pre>"; print_r($fields[$key]); 
                die;
                
                unset($fields[$key]);
                //break;
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
                //break;
            }
        }*/
        //echo "--->>>>";

        //echo "<pre>"; print_r($fields); die;
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
        


        $SurveyTblColumns = TableRegistry::get('survey_table_columns');
        $surveyFormsQuestion = TableRegistry::get('survey_forms_questions');
        
        $SurveyTblColumnRes = $SurveyTblColumns
            ->find()
            ->select([
                'survey_column_id' => $SurveyTblColumns->aliasField('id'),
                'survey_column_name' => $SurveyTblColumns->aliasField('name'),
                'survey_column_order' => $SurveyTblColumns->aliasField('order')
            ])
            ->LeftJoin([$surveyFormsQuestion->alias() => $surveyFormsQuestion->table()],
                [
                    $surveyFormsQuestion->aliasField('survey_question_id') . ' = '. $SurveyTblColumns->aliasField('survey_question_id')
                ])
            ->where([$surveyFormsQuestion->aliasField('survey_question_id') => $tableQuestionId])
            ->toArray();
        //echo "<pre>"; print_r($SurveyTblColumnRes); die;
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
                        'key' => $S_val->survey_column_id,
                        'field' => "'".$S_val->survey_column_name."'",
                        'type' => 'string',
                        'label' => $S_val->survey_column_name
                    ];
                }
            }
        }
        //echo"<pre>"; print_r($SurveyTblColumnRes); die;

    }




}
//End of POCOR-6695
