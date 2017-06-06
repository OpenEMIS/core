<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingSessionsTable extends AppTable  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
        $this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Excel', [
            'excludes' => ['code', 'name', 'assignee_id', 'status_id', 'training_course_id']
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
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;

        $query
            ->select(['course_code' => 'Courses.code'])
            ->order([$this->Courses->aliasField('code'), $this->aliasField('code')]);

        if ($selectedStatus != '-1') {
            $query->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus) {
                return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
            });
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'TrainingSessions.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.course_code',
            'field' => 'course_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Courses.training_course_id',
            'field' => 'training_course_id',
            'type' => 'integer',
            'label' => __('Course Name'),
        ];

        $newFields[] = [
            'key' => 'TrainingSessions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Session Code'),
        ];

        $newFields[] = [
            'key' => 'TrainingSessions.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Session Name'),
        ];

        $newFields = array_merge($newFields, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }
}
