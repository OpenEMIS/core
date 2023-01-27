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
//POCOR-6695 Starts
class SurveysReportTable extends AppTable
{
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
        
        $condition = [];
        $requestData = json_decode($settings['process']['params']);
        $institutionID = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $surveySection = $requestData->survey_section;
        $tableQuestion = $requestData->table_question;
        $institutionStatus = $requestData->institution_status;

        if($institutionID > 0){
            $condition['Institutions.id'] = $institutionID;
        }
        if (!empty($institutionStatus)) {
            $condition['Statuses.name'] = $institutionStatus;
        }
        if (!empty($academicPeriodId)) {
            $condition[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($tableQuestion)) {
            $condition[$surveyFormsQuestion->aliasField('survey_question_id')] = $tableQuestion;
        }

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
            ->leftJoin(['SurveyFormsFilters' => 'survey_forms_filters'], [
                'SurveyFormsFilters.survey_form_id = '. $surveyForms->aliasField('id')
            ])
            ->leftJoin(['InstitutionTypes' => 'institution_types'], [
                'SurveyFormsFilters.survey_filter_id = InstitutionTypes.id'
            ])
            ->leftJoin(['Institutions' => 'institutions'], [
                'InstitutionTypes.id = Institutions.institution_type_id'
            ])
            ->leftJoin(['Areas' => $areas->table()], [
                'Areas.id = Institutions.area_id'
            ])
            ->leftJoin([$areaLevels->alias() => $areaLevels->table()],
            [
                $areaLevels->aliasField('id') . ' = '. 'Areas.area_level_id'
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
                        $surveyTableColumns->aliasField('id') . ' = '. $insSurveyTblCell->aliasField('survey_table_column_id'),
                        $surveyTableColumns->aliasField('survey_question_id') . ' = '. $insSurveyTblCell->aliasField('survey_question_id')
                    ])
                    ->where([
                        $insSurveyTblCell->aliasField('survey_table_row_id') => $survey_table_row_id,
                        $insSurveyTblCell->aliasField('survey_question_id') => $tableQuestion
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
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $requestData = json_decode($settings['process']['params']);
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
//End of POCOR-6695
