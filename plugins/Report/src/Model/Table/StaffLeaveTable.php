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
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $query->find('academicPeriod', ['academic_period_id' => $academicPeriodId, 'start_date_field' => 'date_from', 'end_date_field' => 'date_to']);
        }

        $query
            ->select(['openemis_no' => 'Users.openemis_no', 
                    'code' => 'Institutions.code', 
                    'area_name' => 'Areas.name',
                    'area_code' => 'Areas.code',
                    'area_administrative_code' => 'AreaAdministratives.code',
                    'area_administrative_name' => 'AreaAdministratives.name'
            ])
            ->contain(['Users', 'Institutions', 'Institutions.Areas', 'Institutions.AreaAdministratives'])
            ->order([$this->aliasField('date_from')]);
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
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'StaffLeave.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.area',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
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
}
