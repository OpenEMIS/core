<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class InstitutionStaffTransfersTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    // Initiated By
    const INCOMING = 1;
    const OUTGOING = 2;

    // institution_owner params
    public $incomingOwnerParams = '';
    public $outgoingOwnerParams = '';

    // fte options
    public $fteOptions = [];

    public function initialize(array $config)
    {
        $this->table('institution_staff_transfers');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Staff', ['className' => 'Institution.Staff', 'foreignKey' => 'institution_staff_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'staff_type_id']);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Institution.InstitutionStaffTransfers']);
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('OpenEmis.Section');

        $this->incomingOwnerParams = json_encode(['institution_owner' => self::INCOMING], JSON_NUMERIC_CHECK);
        $this->outgoingOwnerParams = json_encode(['institution_owner' => self::OUTGOING], JSON_NUMERIC_CHECK);
        $this->fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onTransferStaff',
            'text' => 'Transfer Staff',
            'description' => 'Performing this action will transfer the staff to the selected institution.',
            'method' => 'onTransferStaff'
        ]
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';

        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onTransferStaff(Event $event, $id, Entity $workflowTransitionEntity)
    {
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('institution_staff_id', ['type' => 'hidden']);
    }

    public function onGetFTE(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('FTE')) {
            $fte = $entity->FTE;
            $value = $this->fteOptions["$fte"];
        }
        return $value;
    }

    public function onGetInitiatedBy(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->initiated_by == self::INCOMING && $entity->has('institution')) {
            $value = $entity->institution->code_name;

        } else if ($entity->initiated_by == self::OUTGOING && $entity->has('previous_institution')) {
            $value = $entity->previous_institution->code_name;
        }
        return $value;
    }

    public function onGetPreviousInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('previous_institution')) {
            $value = $entity->previous_institution->code_name;
        }
        return $value;
    }

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('institution')) {
            $value = $entity->institution->code_name;
        }
        return $value;
    }
}
