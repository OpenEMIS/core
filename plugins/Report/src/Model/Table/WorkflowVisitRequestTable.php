<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowVisitRequestTable extends AppTable  {

    public function initialize(array $config) {
        //This controller base table is "workflow_models" so '$this' will represent the "workflow_models" table
        $this->table("institution_visit_requests");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('QualityVisitTypes', ['className' => 'FieldOption.QualityVisitTypes', 'foreignKey' => 'quality_visit_type_id']);
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
        // $events['WorkflowReport.onExcelBeforeQuery'] = 'workflowBeforeQuery';
        // $events['WorkflowReport.onExcelUpdateFields'] = 'workflowUpdateFields';
        return $events;
    }
}
