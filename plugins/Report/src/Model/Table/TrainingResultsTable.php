<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingResultsTable extends AppTable  {
    public function initialize(array $config)
    {
        $this->table('training_session_trainee_results');
        parent::initialize($config);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
        $this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);

        $this->addBehavior('Excel', [
            'pages' => false,
            'orientation' => 'landscape'
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $TrainingSessionResults = TableRegistry::get('Training.TrainingSessionResults');
        $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesSteps = TableRegistry::get('Workflow.WorkflowStatusesSteps');
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;

        $query
            ->select(['workflow_step_name' => 'WorkflowSteps.name', 'openemis_no' => 'Trainees.openemis_no', 'course_code' => 'Courses.code', 'course_name' => 'Courses.name', 'credit_hours' => 'Courses.credit_hours', 'session_code' => 'Sessions.code'])
            ->contain(['Trainees', 'Sessions.Courses'])
            ->innerJoin(
                [$TrainingSessionResults->alias() => $TrainingSessionResults->table()],
                [$TrainingSessionResults->aliasField('training_session_id = ') . $this->aliasField('training_session_id')]
            )
            ->innerJoin(
                [$WorkflowSteps->alias() => $WorkflowSteps->table()],
                [$WorkflowSteps->aliasField('id = ') . $TrainingSessionResults->aliasField('status_id')]
            )
            ->order([$this->aliasField('training_session_id'), $this->aliasField('trainee_id')])
            ->autoFields(true);

        if (!empty($selectedStatus)) {
            $query
                ->innerJoin(
                    [$WorkflowStatusesSteps->alias() => $WorkflowStatusesSteps->table()],
                    [$WorkflowStatusesSteps->aliasField('workflow_step_id = ') . $WorkflowSteps->aliasField('id')]
                )
                ->where([$WorkflowStatusesSteps->aliasField('workflow_status_id') => $selectedStatus]);
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'WorkflowSteps.status',
            'field' => 'workflow_step_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingResults.trainee_id',
            'field' => 'trainee_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.course_code',
            'field' => 'course_code',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.course_name',
            'field' => 'course_name',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Sessions.session_code',
            'field' => 'session_code',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingResults.training_session_id',
            'field' => 'training_session_id',
            'type' => 'integer',
            'label' => __('Session Name'),
        ];

        $newFields[] = [
            'key' => 'TrainingResults.training_result_type_id',
            'field' => 'training_result_type_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'TrainingResults.result',
            'field' => 'result',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.credit_hours',
            'field' => 'credit_hours',
            'type' => 'integer',
            'label' => '',
        ];

        $fields->exchangeArray($newFields);
    }
}
