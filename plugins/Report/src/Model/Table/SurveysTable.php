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

class SurveysTable extends AppTable
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
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('survey_form', ['type' => 'hidden']);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_status');
        $this->ControllerAction->field('status', ['type' => 'hidden']);
    }

    public function onUpdateFieldInstitutionStatus(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
           $attr['options'] = $this->controller->getInstitutionStatusOptions($this->alias());

            if (!(isset($this->request->data[$this->alias()]['institution_status']))) {
                $option = $attr['options'];
                $options = [
                    'Active' => __('Active'),
                    'Inactive' => __('Inactive'),
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
                
                //reset($option);
                //$this->request->data[$this->alias()]['institution_status'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onExcelAfterHeader(Event $event, ArrayObject $settings)
    {      
       if ($settings['renderNotComplete'] || $settings['renderNotOpen']) {
            $fields = $settings['sheet']['fields'];
            $requestData = json_decode($settings['process']['params']);
            $surveyFormId = $requestData->survey_form;
            $academicPeriodId = $requestData->academic_period_id;
            $institutionStatus = $requestData->institution_status;

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
                            $InstitutionsTable->aliasField('institution_status_id') => $institutionFormStatus
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
            $mappingArray = ['status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'code'];

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
                            $InstitutionsTable->aliasField('institution_status_id') => $institutionFormStatus
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
            $mappingArray = ['status_id', 'academic_period_id', 'survey_form_id', 'institution_id', 'code'];
           
            foreach ($notOpenRecords->all() as $record) {
                $record->academic_period_id = $academicPeriodName;
                $record->survey_form_id = $surveyFormName;
                
                $countDisabledSurveyInstitution = $this->find('list',[
                'keyField' => 'institution_id',
		'valueField' => 'status_id',
                ])->where([
                    $this->aliasField('academic_period_id') .' = '. $academicPeriodId,
                    $this->aliasField('survey_form_id') .' = '. $surveyFormId,
                    $this->aliasField('institution_id') .' = '. $record->institutionId,
                    $this->aliasField('status_id') .' = '. self::SURVEY_DISABLED
                ])->count();
                
                if($countDisabledSurveyInstitution > 0){
                    $record->status_id = __('Not Completed');
                }else{
                    $record->status_id = __('Open');
                }
                
                $row = [];
                foreach ($fields as $field) {
                    if (in_array($field['field'], $mappingArray)) {
                        $row[] = __($record->{$field['field']});
                    } else if ($field['field'] == 'area') {
                        $row[] = __($record->area);
                    }else if ($field['field'] == 'institution_statusActive') {
                        $row[] = __('Active');
                    } 
                    else if ($field['field'] == 'institution_statusInactive') {
                        $row[] = __('Inactive');
                    } 
                    else if ($field['field'] == 'area_administrative') {
                        $row[] = __($record->area_administrative);
                    } else {
                        $row[] = '';
                    }
                }
                 
                $writer->writeSheetRow($sheetName, $row);
             }
            }
            
            $settings['renderNotComplete'] = false;
            $settings['renderNotOpen'] = false;
           
        }
        
      }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        // Setting request data and modifying fetch condition
        $requestData = json_decode($settings['process']['params']);
        $surveyFormId = $requestData->survey_form;
        $academicPeriodId = $requestData->academic_period_id;
        $status = $requestData->status;
        $institutionStatus = $requestData->institution_status;
       
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');
        
        if (!empty($academicPeriodId)) {
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
        } else {
            $academicPeriodId = 0;
        }

        $configCondition = $this->getCondition();
        $condition = [
            $this->aliasField('academic_period_id') => $academicPeriodId
        ];

        if (!$status) $status = array_keys($surveyStatuses);

        $surveyStatuses = $WorkflowStatusesTable->getWorkflowSteps($status);
        
        $this->surveyStatuses = $WorkflowStatusesTable->getWorkflowStepStatusNameMappings('Institution.InstitutionSurveys');
        if (!empty($surveyStatuses) || $status == '' || $status == 'all') {
            
            if($settings['renderNotComplete'] === true && $settings['renderNotOpen'] === true){
                $statusCondition = [
                    $this->aliasField('status_id').' IN ('.self::COMPLETED.')'
                ];
            }elseif($settings['renderNotComplete'] === true && $settings['renderNotOpen'] === false){
                $statusCondition = [
                    $this->aliasField('status_id').' NOT IN ('.self::OPEN.', '.self::PENDINGAPPROVAL.', '.self::COMPLETED.' )'
                ];
            }else{
                $statusCondition = [
                    $this->aliasField('status_id').' IN ' => array_keys($surveyStatuses)
                ];
            }
            
            $condition = array_merge($condition, $statusCondition);
            
           
       }
        $condition = array_merge($condition, $configCondition);
       
        $this->setCondition($condition);

        // For Surveys only
        $forms = $this->getForms($surveyFormId);
        foreach ($forms as $formId => $formName) {
            $this->excelContent($sheets, $formName, null, $formId);
        }

        // Stop the customfieldlist behavior onExcelBeforeStart function
        $event->stopPropagation();
       
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $query
            ->select([
                'code' => 'Institutions.code',
                'area' => 'Areas.name',
                'area_administrative' => 'AreaAdministratives.name'
            ])
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'Institutions.Statuses'
            ])
            //Only Active schools are able to generate survey
            ->where([
                'Statuses.code' => 'ACTIVE'
            ]
            );
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        
        $institutionStatus = $requestData->institution_status;


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
            'field' => 'area',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Institutions.area_administrative_id',
            'field' => 'area_administrative',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'institution_status'. $institutionStatus,
            'field' =>'institution_status'. $institutionStatus,
            'type' => 'string',
            'label' => __('Institution Status')
        ];

    }

    public function onUpdateFieldSurveyForm(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];
                if ($feature == $this->registryAlias()) {
                    $surveyFormOptions = $this->SurveyForms
                        ->find('list')
                        ->toArray();
                    $attr['options'] = $surveyFormOptions;
                    $attr['onChangeReload'] = true;
                    $attr['type'] = 'select';
                    if (empty($this->request->data[$this->alias()]['survey_form'])) {
                        $option = $attr['options'];
                        reset($option);
                        $this->request->data[$this->alias()]['survey_form'] = key($option);
                    }
                    return $attr;
                }
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature']) && isset($this->request->data[$this->alias()]['survey_form'])) {
                $feature = $this->request->data[$this->alias()]['feature'];
                $surveyForm = $this->request->data[$this->alias()]['survey_form'];
                if ($feature == $this->registryAlias() && !empty($surveyForm)) {
                    $SurveyStatusTable = $this->SurveyForms->surveyStatuses;
                    $academicPeriodOptions = $SurveyStatusTable
                        ->find('list', [
                            'keyField' => 'academic_id',
                            'valueField' => 'academic_name'
                        ])
                        ->matching('AcademicPeriods')
                        ->select(['academic_id' => 'AcademicPeriods.id', 'academic_name' => 'AcademicPeriods.name'])
                        ->where([
                            $SurveyStatusTable->aliasField('survey_form_id') => $surveyForm,
                        ])
                        ->order(['AcademicPeriods.order'])
                        ->toArray();
                    $attr['options'] = $academicPeriodOptions;
                    $attr['onChangeReload'] = true;
                    $attr['type'] = 'select';
                    if (empty($this->request->data[$this->alias()]['academic_period_id'])) {
                        $option = $attr['options'];
                        reset($option);
                        $this->request->data[$this->alias()]['academic_period_id'] = key($option);
                    }
                    return $attr;
                }
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])
                && isset($this->request->data[$this->alias()]['academic_period_id'])) {
                $feature = $this->request->data[$this->alias()]['feature'];
                $surveyForm = $this->request->data[$this->alias()]['survey_form'];
                $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];

                if ($feature == $this->registryAlias() && !empty($academicPeriodId)) {
                    $surveyStatuses = $this->Workflow->getWorkflowStatuses('Institution.InstitutionSurveys');
                    $attr['type'] = 'select';
                    $surveyTable = $this;
                    $arrAll = array("all" => "All" );
                    $collectionData = new Collection($surveyStatuses);
                    $attr['options'] = $collectionData->append($arrAll)->toArray();
                    return $attr;
                }
            }
        }
    }


    public function onExcelGetStatusId(Event $event, Entity $entity)
    {
        $surveyStatuses = $this->surveyStatuses;
        $status = $entity->status_id;
        return __($surveyStatuses[$status]);
    }

}
