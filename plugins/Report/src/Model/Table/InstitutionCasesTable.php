<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

class InstitutionCasesTable extends AppTable
{
    private $features = [];

    public function initialize(array $config)
    {
        $this->table('institution_cases');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('InstitutionCaseRecords', ['className' => 'Institution.InstitutionCaseRecords', 'foreignKey' => 'institution_case_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsTo('CaseTypes', ['className' => 'Cases.CaseTypes', 'foreignKey' => 'case_type_id']); //POCOR-7786
        $this->belongsTo('CasePriority', ['className' => 'Cases.CasePriorities', 'foreignKey' => 'case_priority_id']); //POCOR-7786
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Report.AreaList');//POCOR-7851
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $this->features = $WorkflowRules->getFeatureOptionsWithClassName();
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];

        $requestData = json_decode($settings['process']['params']);

        //POCOR-7786 start
        // $module = $requestData->module;

        // $this->InstitutionCaseRecords->belongsTo($module, [
        //     'className' => $this->features[$module],
        //     'foreignKey' => 'record_id',
        //     'conditions' => ['feature' => $module]
        // ]);
        //POCOR-7786 end
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institution_id = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id;//POCOR-7851
        $where = [];
        if ($institution_id != 0) {
            $where['Institutions.id'] = $institution_id;
        }
        //POCOR-7851 start
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {

            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $where['Institutions.area_id IN'] = $areaList;
        }
        //POCOR-7851 end
        // if ($areaId != -1) {
        //     $where['Institutions.area_id'] = $areaId;
        // }
        //POCOR-7786 start
        // $module = $requestData->module;
        // $listener = TableRegistry::get(]);
        $query
            ->select([
                'case_id'=>'InstitutionCases.id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'case_number'=> 'InstitutionCases.case_number',
                'case_title'=> 'InstitutionCases.title',
                'description' => 'InstitutionCases.description',
                'type' => 'CaseTypes.name',
                'priority' => 'CasePriority.name',
                'status_to' => 'WorkflowTransitions.workflow_step_name',
                'assignee_first_name' => 'Assignees.first_name',
                'assignee_last_name' => 'Assignees.last_name',
                'assignee_openemis' => 'Assignees.openemis_no',
                'executed_by' => 'WorkflowTransitions.created_user_id',
                'executed_date' => 'WorkflowTransitions.created',
                'created' => 'InstitutionCases.created',
                'modified' => 'InstitutionCases.modified',
                'CreatedUser.first_name',
                'CreatedUser.middle_name',
                'CreatedUser.third_name',
                'CreatedUser.last_name',
                'CreatedUser.preferred_name',
                'CreatedUser.openemis_no',
            ])
            ->matching('Statuses.Workflows.WorkflowModels.WorkflowTransitions.CreatedUser', function ($q) {
                    return $q->where(['WorkflowTransitions.model_reference = ' . $this->aliasField('id')]);
            })
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'CaseTypes',
                'CasePriority',
                'Assignees',
                
            ])
            ->where([$where])
            ->order([$this->aliasField('case_number')])
            ->formatResults(function ($results) {
                $arrayRes = $results->toArray();
                foreach ($arrayRes as $arr) {
                    Log::write('debug', $arr);
                    $arr->executed_by = $arr['_matchingData']['CreatedUser']['openemis_no']." - ".$arr['_matchingData']['CreatedUser']['name'];
                    $arr->assignee=$arr['assignee_openemis']." - ".$arr['assignee_first_name']." ".$arr["assignee_last_name"];
                    
                    $linkedRecords = TableRegistry::get('institution_case_links'); //POCOR-7786
                    $institutionCases = TableRegistry::get('institution_cases');
                    $childCases=$linkedRecords->find()
                                               ->where([$linkedRecords->aliasField('parent_case_id')=>$arr->case_id])
                                               ->toArray();
                    $childCaseNumbers=[];
                    if(!empty($childCases)){
                        foreach($childCases as $case){
                            $childCaseNumbers[]= $institutionCases->get($case->id)->case_number;
                        }
                    }
                    $arr->linked_records = implode(', ', $childCaseNumbers);
                $arr->created= $arr->created->format('F j, Y - H:i:s');
                $arr->modified = !isset($arr->modified)? $arr->modified : $arr->modified->format('F j, Y - H:i:s');
                }
                return $arrayRes;
            });
        // $event = $listener->dispatchEvent('InstitutionCase.onBuildCustomQuery', [$query], $listener);
        //POCOR-7786 end
        if ($event->isStopped()) {
            return $event->result;
        }
        if (!empty($event->result)) {
            $query = $event->result;
        }

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $startDate = $requestData->report_start_date;
            $endDate = $requestData->report_end_date;

            $reportStartDate = (new DateTime($startDate))->format('Y-m-d');
            $reportEndDate = (new DateTime($endDate))->format('Y-m-d');

            $query->where([
                $this->aliasField('created') . ' <= ' => $reportEndDate. ' 23:59:59',
                $this->aliasField('created') . ' >= ' => $reportStartDate.' 00:00:00'
            ]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        //POCOR-7786 start
        // $module = $requestData->module;  //POCOR-7786
        $newFields = [];

      
        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'integer',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'InstitutionCases.case_number',
            'field' => 'case_number',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionCases.title',
            'field' => 'case_title',
            'type' => 'string',
            'label' => __('Case Title')
        ];
        $newFields[] = [
            'key' => 'InstitutionCases.description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Description')
        ];
        $newFields[] = [
            'key' => 'CaseTypes.name',
            'field' => 'type',
            'type' => 'string',
            'label' => __('Type')
        ];
        $newFields[] = [
            'key' => 'CasePriority.name',
            'field' => 'priority',
            'type' => 'string',
            'label' => __('Priority')
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.workflow_step_name',
            'field' => 'status_to',
            'type' => 'string',
            'label' => __('Status')
        ];

        $newFields[] = [
            'key' => 'Assignee',
            'field' => 'assignee',
            'type' => 'string',
            'label' => __('Assignee')
        ];
        $newFields[] = [
            'key' => 'WorkflowTransitions.executed_by',
            'field' => 'executed_by',
            'type' => 'string',
            'label' => 'Creator'
        ];
        $newFields[] = [
            'key' => 'LinkedRecords',
            'field' => 'linked_records',
            'type' => 'string',
            'label' => 'Linked Records'
        ];
        $newFields[] = [
            'key' => 'InstitutionCases.modified',
            'field' => 'modified',
            'type' => 'string',
            'label' => __('Updated')
        ];
        $newFields[] = [
            'key' => 'InstitutionCases.created',
            'field' => 'created',
            'type' => 'string',
            'label' => __('Created')
        ];

       
            
        // $listener = TableRegistry::get($this->features[$module]);  //POCOR-7786 start
        // $event = $listener->dispatchEvent('InstitutionCase.onIncludeCustomExcelFields', [$newFields], $listener);  //POCOR-7786 start
         //POCOR-7786 end
        if ($event->isStopped()) {
            return $event->result;
        }
        if (!empty($event->result)) {
            $newFields = $event->result;
        }

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetFullName(Event $event, Entity $entity)
    {
        $fullName = [];
        ($entity->first_name) ? $fullName[] = $entity->first_name : '';
        ($entity->middle_name) ? $fullName[] = $entity->middle_name : '';
        ($entity->third_name) ? $fullName[] = $entity->third_name : '';
        ($entity->last_name) ? $fullName[] = $entity->last_name : '';

        return implode(' ', $fullName);
    }
}
