<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowStaffAppraisalTable extends AppTable  
{

    public function initialize(array $config) 
    {
        $this->table("institution_staff_appraisals");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('AppraisalForms', ['className' => 'StaffAppraisal.AppraisalForms']);
        $this->belongsTo('AppraisalTypes', ['className' => 'StaffAppraisal.AppraisalTypes']);
        $this->belongsTo('AppraisalPeriods', ['className' => 'StaffAppraisal.AppraisalPeriods']);
        $this->hasMany('AppraisalTextAnswers', [
            'className' => 'StaffAppraisal.AppraisalTextAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('AppraisalSliderAnswers', [
            'className' => 'StaffAppraisal.AppraisalSliderAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true]);

        $this->hasMany('AppraisalDropdownAnswers', [
            'className' => 'StaffAppraisal.AppraisalDropdownAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->hasMany('AppraisalNumberAnswers', [
            'className' => 'StaffAppraisal.AppraisalNumberAnswers',
            'foreignKey' => 'institution_staff_appraisal_id',
            'saveStrategy' => 'replace',
            'dependent' => true, 
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }
}
