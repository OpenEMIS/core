<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionStaffTransfersTable extends AppTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onTriggerIncomingStaffTransferWorkflow',
            'text' => 'Trigger Incoming Staff Transfer Workflow',
            'description' => 'Performing this action will trigger the staff transfer workflow in the incoming institution.',
            'method' => 'onTriggerIncomingStaffTransferWorkflow'
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

    public function onTriggerIncomingStaffTransferWorkflow(Event $event, $id, Entity $workflowTransitionEntity)
    {

    }
}
