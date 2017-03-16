<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffLeaveTable extends AppTable {
    public function initialize(array $config)
    {
        $this->table('institution_staff_leave');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Excel');
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select(['openemis_no' => 'Users.openemis_no'])
            ->contain(['Users']);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'StaffLeave.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.institution_id',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.staff_id',
            'field' => 'staff_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.staff_leave_type_id',
            'field' => 'staff_leave_type_id',
            'type' => 'integer',
            'label' => __('Leave Type')
        ];

        $newFields[] = [
            'key' => 'StaffLeave.date_from',
            'field' => 'date_from',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.date_to',
            'field' => 'date_to',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.number_of_days',
            'field' => 'number_of_days',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.comments',
            'field' => 'comments',
            'type' => 'string',
            'label' => ''
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetInstitutionId(Event $event, Entity $entity) {
        return $entity->institution->code_name;
    }
}
