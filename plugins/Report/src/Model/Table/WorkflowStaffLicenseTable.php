<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowStaffLicenseTable extends AppTable  {

    public function initialize(array $config) {
        //This controller base table is "workflow_models" so '$this' will represent the "workflow_models" table
        $this->table("staff_licenses");
        parent::initialize($config);

       $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsToMany('Classifications', [
            'className' => 'FieldOption.LicenseClassifications',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'staff_license_id',
            'targetForeignKey' => 'license_classification_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true
        ]);

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
        // $events['WorkflowReport.onExcelBeforeQuery'] = 'workflowBeforeQuery';
        // $events['WorkflowReport.onExcelUpdateFields'] = 'workflowUpdateFields';
        return $events;
    }
}
