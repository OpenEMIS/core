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
        $this->addBehavior('Report.CustomFieldList', [
            'moduleKey' => null,
            'model' => 'Institution.InstitutionSurveys',
            'formKey' => 'survey_form_id',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
        ]);

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

    
    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        //Setting request data and modifying fetch condition
        $requestData = json_decode($settings['process']['params']);
        $surveyFormId = $requestData->survey_form;
        $academicPeriodId = $requestData->academic_period_id;
        $status = $requestData->status;
        $institutionStatus = $requestData->institution_status;
        $areaId = $requestData->area_id;

        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        if (!empty($academicPeriodId) && empty($areaId)) { ////POCOR-7046
            $surveyStatuses = $WorkflowStatusesTable->WorkflowModels->getWorkflowStatusesCode('Institution.InstitutionSurveys');

            if($status == '' || $status == 'all'){
                  $settings['renderNotOpen'] = true;
                  $settings['renderNotComplete'] = true;

            } elseif ($surveyStatuses[$status] == 'Open') {

                  $settings['renderNotOpen'] = true;
                  $settings['renderNotComplete'] = false;

            } elseif (  !$status || $surveyStatuses[$status] == 'NOT_COMPLETED') {
                  $settings['renderNotOpen'] = false;
                  $settings['renderNotComplete'] = true;

            } else {
                  $settings['renderNotOpen'] = false;
                  $settings['renderNotComplete'] = false;
            }
        }//Start POCOR-7046
        else if (!empty($areaId)) {
            if($status == '' || $status == 'all'){
                  $settings['renderNotOpen'] = false;
                  $settings['renderNotComplete'] = true;

            } 
            // elseif ($surveyStatuses[$status] == 'Open') {

            //       $settings['renderNotOpen'] = true;
            //       $settings['renderNotComplete'] = false;

            // } 
            // elseif (  !$status || $surveyStatuses[$status] == 'NOT_COMPLETED') {
            //       $settings['renderNotOpen'] = false;
            //       $settings['renderNotComplete'] = true;

            // } 
            else {
                  $settings['renderNotOpen'] = false;
                  $settings['renderNotComplete'] = false;
            }
        }//End POCOR-7046
        else {
            $academicPeriodId = 0;
        }

        $configCondition = $this->getCondition();
        $condition = [
            $this->aliasField('academic_period_id') => $academicPeriodId
        ];

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
        $condition = array_merge($condition, $configCondition);

        $this->setCondition($condition);

        //For Surveys only
        $forms = $this->getForms($surveyFormId);
        foreach ($forms as $formId => $formName) {
            $this->excelContent($sheets, $formName, null, $formId);
        }

        // Stop the customfieldlist behavior onExcelBeforeStart function
        $event->stopPropagation();

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $surveyForms = TableRegistry::get('survey_forms');
        $surveyFormsFilters = TableRegistry::get('survey_forms_filters');
        $institutionTypes = TableRegistry::get('institution_types');
        $institutions = TableRegistry::get('institutions');
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
                    // 'area_level_code' => 'AreaLevels.levels',
                    // 'area_level_name' => 'AreaLevels.name',
                    'area_administrative' => 'AreaAdministratives.name',
                    'Statuses_name' => 'Statuses.name'
                ])
                ->leftJoin(['SurveyForms' => 'survey_forms'], [
                    'Surveys.id = SurveyForms.id'
                ])
                ->leftJoin(['SurveyFormsFilters' => 'survey_forms_filters'], [
                    'SurveyFormsFilters.survey_form_id = SurveyForms.id'
                ])
                ->leftJoin(['InstitutionTypes' => 'institution_types'], [
                    'SurveyFormsFilters.survey_filter_id = InstitutionTypes.id'
                ])
                ->leftJoin(['Institutions' => 'institutions'], [
                    'InstitutionTypes.id = Institutions.institution_type_id'
                ])
                ->contain([
                    'Institutions.Areas',
                    'Institutions.AreaAdministratives',
                    'Institutions.Statuses'
                ])
                ->where([$condition]);
               // print_r($query->toArray()); die;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
       // print_r($fields); die;
        $requestData = json_decode($settings['process']['params']);

        $institutionStatus = $requestData->institution_status;

        // To update to this code when upgrade server to PHP 5.5 and above
        // unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

        // foreach ($fields as $key => $field) {
        //     if ($field['field'] == 'institution_id') {

        //         unset($fields[$key]);
        //         break;
        //     }
        // }

        $fields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        // $fields[] = [
        //     'key' => 'area_level_code',
        //     'field' => 'area_level_code',
        //     'type' => 'string',
        //     'label' => __('Area Level Code')
        // ];

        // $fields[] = [
        //     'key' => 'area_level_name',
        //     'field' => 'area_level_name',
        //     'type' => 'integer',
        //     'label' => __('Area Level Name')
        // ];

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
            'label' => __('Institution Name')
        ];

        $fields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $fields[] = [
            'key' => 'Statuses_name',
            'field' =>'Statuses_name',
            'type' => 'string',
            'label' => __('Institution Status')
        ];

    }




}
