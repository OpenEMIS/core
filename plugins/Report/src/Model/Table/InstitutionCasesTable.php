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

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');

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

        $module = $requestData->module;

        $this->InstitutionCaseRecords->belongsTo($module, [
            'className' => $this->features[$module],
            'foreignKey' => 'record_id',
            'conditions' => ['feature' => $module]
        ]);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        $module = $requestData->module;
        $listener = TableRegistry::get($this->features[$module]);
        $query
            ->select([
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'institution_code' => 'Institutions.code',
                'status_from' => 'WorkflowTransitions.prev_workflow_step_name',
                'status_to' => 'WorkflowTransitions.workflow_step_name',
                'action' => 'WorkflowTransitions.workflow_action_name',
                'comment' => 'WorkflowTransitions.comment',
                'executed_by' => 'WorkflowTransitions.created_user_id',
                'executed_date' => 'WorkflowTransitions.created',
                'CreatedUser.first_name',
                'CreatedUser.middle_name',
                'CreatedUser.third_name',
                'CreatedUser.last_name',
                'CreatedUser.preferred_name'
            ])
            ->matching('Statuses.Workflows.WorkflowModels.WorkflowTransitions.CreatedUser', function ($q) {
                    return $q->where(['WorkflowTransitions.model_reference = ' . $this->aliasField('id')]);
            })
            ->contain([
                'Institutions.Areas',
                'Institutions.AreaAdministratives'
            ])
            ->order([$this->aliasField('case_number')])
            ->formatResults(function ($results) {
                $arrayRes = $results->toArray();
                foreach ($arrayRes as $arr) {
                    Log::write('debug', $arr);
                    $arr->executed_by = $arr['_matchingData']['CreatedUser']['name'];
                }
                return $arrayRes;
            });

        $event = $listener->dispatchEvent('InstitutionCase.onBuildCustomQuery', [$query], $listener);
        
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
        $module = $requestData->module;
        $newFields = [];

        $newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionCases.institution_id',
            'field' => 'institution_id',
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
            'field' => 'title',
            'type' => 'integer',
            'label' => __('Case Title')
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.prev_workflow_step_name',
            'field' => 'status_from',
            'type' => 'string',
            'label' => __('Previous Status')
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.workflow_step_name',
            'field' => 'status_to',
            'type' => 'string',
            'label' => __('Status')
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.workflow_action_name',
            'field' => 'action',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.executed_by',
            'field' => 'executed_by',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'WorkflowTransitions.executed_date',
            'field' => 'executed_date',
            'type' => 'date',
            'label' => ''
        ];

        $listener = TableRegistry::get($this->features[$module]);
        $event = $listener->dispatchEvent('InstitutionCase.onIncludeCustomExcelFields', [$newFields], $listener);
        
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
