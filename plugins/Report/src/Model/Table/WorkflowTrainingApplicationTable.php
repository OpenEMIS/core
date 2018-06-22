<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowTrainingApplicationTable extends AppTable  
{
    public function initialize(array $config) 
    {
        $this->table("staff_training_applications");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery';
        $events['Model.excel.onExcelUpdateFields'] = 'onExcelUpdateFields';
        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query) {
        $query
            ->select([
                'name' => 'Statuses.name',
                'assignee_id' => $this->aliasField('assignee_id'),
                'staff_id' => $this->aliasField('staff_id'),
                'session_name' => 'Sessions.name',
                'course_name' => 'Courses.name',
                'institution_name' => 'Institutions.name'
            ])
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'Statuses.name'
                    ]
                ]   
            ])
            ->contain([
                'Assignees' => [
                    'fields' => [
                        'Assignees.first_name',
                        'Assignees.middle_name',
                        'Assignees.third_name',
                        'Assignees.last_name',
                        'Assignees.preferred_name'
                    ]
                ]   
            ])
            ->contain([
                'Staff' => [
                    'fields' => [
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name',
                        'Staff.preferred_name'
                    ]
                ]   
            ])
            ->contain([
                'Sessions.Courses' => [
                    'fields' => [
                        'Sessions.name',
                        'Courses.name'
                    ]
                ]
            ])
            ->contain([
                'Institutions' => [
                    'fields' => [
                        'Institutions.name'
                    ]
                ]   
            ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $newFields = [];

        $newFields[] = [
            'key' => 'Statuses.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Status')
        ];
        $newFields[] = [
            'key' => 'assignee_id',
            'field' => 'assignee_id',
            'type' => 'string',
            'label' => __('Assignee')
        ];
        $newFields[] = [
            'key' => 'staff_id',
            'field' => 'staff_id',
            'type' => 'string',
            'label' => __('Staff')
        ];
        $newFields[] = [
            'key' => 'session_name',
            'field' => 'session_name',
            'type' => 'string',
            'label' => __('Training Session')
        ];
        $newFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];
        $newFields[] = [
            'key' => 'course_name',
            'field' => 'course_name',
            'type' => 'string',
            'label' => __('Course')
        ];

        $fields->exchangeArray($newFields);
    }
}
