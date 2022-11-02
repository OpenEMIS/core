<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Event\Event;

use App\Model\Table\AppTable;

class StaffTrainingApplicationsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_applications');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;

        $query
            ->contain(['Institutions.Areas', 'Sessions.Courses'])
            ->select([
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'institution_code' => 'Institutions.code',
                'course_code' => 'Courses.code',
                'course_name' => 'Courses.name',
                'session_code' => 'Sessions.code'
            ]);

        if ($selectedStatus != '-1') {
            $query->matching('Statuses.WorkflowStatuses', function ($q) use ($selectedStatus) {
                return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
            });
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'Areas.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Areas.area',
            'field' => 'area_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffTrainingApplications.institution_id',
            'field' => 'institution_id',
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
            'key' => 'Courses.course',
            'field' => 'course_name',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Sessions.session_code',
            'field' => 'session_code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffTrainingApplications.session_id',
            'field' => 'training_session_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffTrainingApplications.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'StaffTrainingApplications.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => '',
        ];

        $fields->exchangeArray($newFields);
    }
}
