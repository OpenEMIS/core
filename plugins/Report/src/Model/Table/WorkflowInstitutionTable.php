<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowInstitutionTable extends AppTable  {

    public function initialize(array $config) {
        $this->table("institution_surveys");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }
}
