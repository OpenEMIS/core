<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class InstitutionCasesTable extends AppTable
{
    // use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('institution_cases');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('InstitutionCaseRecords', ['className' => 'Institution.InstitutionCaseRecords', 'foreignKey' => 'institution_case_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('AcademicPeriod.Period');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $StaffBehaviours = TableRegistry::get('Institution.StaffBehaviours');
        $Users = TableRegistry::get('User.Users');
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        // module is hard coded to StaffBehaviours for now
        // $module = $requestData->module;

        $query
            ->select([
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'institution_code' => 'Institutions.code',
                // 'status_from' => 'WorkflowTransitions.prev_workflow_step_name',
                // 'status_to' => 'WorkflowTransitions.workflow_step_name',
                // 'action' => 'WorkflowTransitions.workflow_action_name',
                // 'comment' => 'WorkflowTransitions.comment',
                // 'executed_by' => 'WorkflowTransitions.created_user_id',
                // 'executed_date' => 'WorkflowTransitions.created',
                'openemis_no' => 'Users.openemis_no',
                'staff_name' => 'Users.first_name'
            ])
            // ->matching('Statuses.Workflows.WorkflowModels.WorkflowTransitions', function ($q) {
            //         return $q->where(['WorkflowTransitions.model_reference = ' . $this->aliasField('id')]);
            // })
            ->contain(['Institutions.Areas'])
            ->innerJoin(
                [$this->InstitutionCaseRecords->alias() => $this->InstitutionCaseRecords->table()],
                [$this->InstitutionCaseRecords->aliasField('institution_case_id = ') . $this->aliasField('id')]
            )
            ->innerJoin(
                [$StaffBehaviours->alias() => $StaffBehaviours->table()],
                [$StaffBehaviours->aliasField('id = ') . 'InstitutionCaseRecords.record_id']
            )
            ->innerJoin(
                [$Users->alias() => $Users->table()],
                [$Users->aliasField('id = ') . $StaffBehaviours->aliasField('staff_id')]
            )
            ->order([$this->aliasField('code')]);

        $this->log($query->toArray(), 'debug');

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $query->find('inPeriod', ['field' => 'created', 'academic_period_id' => $academicPeriodId, 'table' => 'Institution.InstitutionCaseRecords']);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Areas.area_code',
            'field' => 'area_code',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Areas.area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffBehaviours.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => __('Institution Name')
        ];

        $newFields[] = [
            'key' => 'StaffBehaviours.code',
            'field' => 'code',
            'type' => 'integer',
            'label' => __('Case Code')
        ];

        $newFields[] = [
            'key' => 'StaffBehaviours.title',
            'field' => 'title',
            'type' => 'integer',
            'label' => __('Case Title')
        ];

    //     $newFields[] = [
    //         'key' => 'WorkflowTransitions.prev_workflow_step_name',
    //         'field' => 'status_from',
    //         'type' => 'string',
    //         'label' => ''
    //     ];

    //     $newFields[] = [
    //         'key' => 'WorkflowTransitions.workflow_step_name',
    //         'field' => 'status_to',
    //         'type' => 'string',
    //         'label' => ''
    //     ];

    //     $newFields[] = [
    //         'key' => 'WorkflowTransitions.workflow_action_name',
    //         'field' => 'action',
    //         'type' => 'string',
    //         'label' => ''
    //     ];

    //     $newFields[] = [
    //         'key' => 'WorkflowTransitions.comment',
    //         'field' => 'comment',
    //         'type' => 'string',
    //         'label' => ''
    //     ];

    //     $newFields[] = [
    //         'key' => 'WorkflowTransitions.executed_by',
    //         'field' => 'executed_by',
    //         'type' => 'string',
    //         'label' => ''
    //     ];

    //     $newFields[] = [
    //         'key' => 'WorkflowTransitions.executed_date',
    //         'field' => 'executed_date',
    //         'type' => 'string',
    //         'label' => ''
    //     ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.staff_name',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => ''
        ];


        $fields->exchangeArray($newFields);
    }
}
