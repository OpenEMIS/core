<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class InstitutionStaffTransfersTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Transfer Type
    const FULL_TRANSFER = 1;
    const PARTIAL_TRANSFER = 2;

    // Initiated By
    const INCOMING = 1;
    const OUTGOING = 2;

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('OutgoingInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'outgoing_institution_id']);
        $this->belongsTo('IncomingInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'incoming_institution_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Staff', ['className' => 'Institution.Staff', 'foreignKey' => 'institution_staff_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'staff_type_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');
    }
}
