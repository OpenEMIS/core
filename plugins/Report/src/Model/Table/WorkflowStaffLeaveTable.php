<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowStaffLeaveTable extends AppTable  {

    public function initialize(array $config) {
        //This controller base table is "workflow_models" so '$this' will represent the "workflow_models" table
        $this->table("institution_staff_leave");
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffLeaveTypes', ['className' => 'Staff.StaffLeaveTypes']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'excludes' => ['staff_id', 'date_from'],
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery2';
        $events['Model.excel.onExcelUpdateFields'] = 'onExcelUpdateFields2';
        return $events;
    }

    public function onExcelUpdateFields2(Event $event, ArrayObject $settings, ArrayObject $fields) {

    }

    public function onExcelBeforeQuery2(Event $event, ArrayObject $settings, $query) {
    }
}
