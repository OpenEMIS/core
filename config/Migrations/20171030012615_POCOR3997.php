<?php

use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;

class POCOR3997 extends AbstractMigration
{
    private $incomingWorkflowModelId = 13;
    private $outgoingWorkflowModelId = 14;

    // commit
    public function up()
    {
        // rename institution_staff_assignments
        $InstitutionStaffAssignments = $this->table('institution_staff_assignments');
        $InstitutionStaffAssignments->rename('z_3997_institution_staff_assignments');

        // institution_staff_transfers
        $InstitutionStaffTransfers = $this->table('institution_staff_transfers', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the staff transfer requests'
        ]);
        $InstitutionStaffTransfers
            ->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('new_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to workflow_steps.id'
            ])
            ->addColumn('assignee_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('new_institution_position_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_positions.id'
            ])
            ->addColumn('new_staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('new_FTE', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 2,
                'null' => true
            ])
            ->addColumn('new_start_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('new_end_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('previous_institution_staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_staff.id'
            ])
            ->addColumn('previous_staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('previous_FTE', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 2,
                'null' => true
            ])
            ->addColumn('previous_end_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('initiated_by', 'integer', [
                'default' => null,
                'limit' => 1,
                'null' => false,
                'comment' => '1 -> Incoming Institution, 2 -> Outgoing Institution'
            ])
            ->addColumn('transfer_type', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
                'comment' => '1 -> Full Transfer, 2 -> Partial Transfer, 3 -> No Change'
            ])
            ->addColumn('modified_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true
            ])
            ->addColumn('modified', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false
            ])
            ->addColumn('created', 'datetime', [
                'default' => null,
                'null' => false
            ])
            ->addIndex('staff_id')
            ->addIndex('new_institution_id')
            ->addIndex('previous_institution_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('new_institution_position_id')
            ->addIndex('new_staff_type_id')
            ->addIndex('previous_institution_staff_id')
            ->addIndex('previous_staff_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // NOTE data migration here

        // workflow_steps_params
        $WorkflowStepsParams = $this->table('workflow_steps_params', [
            'id' => false,
            'primary_key' => 'id',
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains the list of params belonging to which step'
        ]);
        $WorkflowStepsParams
            ->addColumn('id', 'uuid', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('workflow_step_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to workflow_steps.id'
            ])
            ->addColumn('name', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addColumn('value', 'string', [
                'default' => null,
                'limit' => 100,
                'null' => false
            ])
            ->addIndex('workflow_step_id')
            ->save();

        // workflow_models
        $workflowModelData = [
            [
                'id' => $this->incomingWorkflowModelId,
                'name' => 'Institutions > Staff > Incoming Transfer',
                'model' => 'Institution.StaffTransferIn',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $this->outgoingWorkflowModelId,
                'name' => 'Institutions > Staff > Outgoing Transfer',
                'model' => 'Institution.StaffTransferOut',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_models', $workflowModelData);

        // workflows
        $workflowData = [
            [
                'code' => 'STAFF-TRANSFER-1001',
                'name' => 'Staff Transfer - Initiated By Incoming Institution',
                'workflow_model_id' => $this->incomingWorkflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'STAFF-TRANSFER-2001',
                'name' => 'Staff Transfer - Initiated By Outgoing Institution',
                'workflow_model_id' => $this->outgoingWorkflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflows', $workflowData);

        // STAFF-TRANSFER-1001 (by incoming)
        $byIncomingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `workflow_model_id` = " . $this->incomingWorkflowModelId)['id'];

        // workflow_steps
        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => '1',
                'is_editable' => '1',
                'is_removable' => '1',
                'is_system_defined' => '1',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Outgoing Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Staff Assignment',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Assigned',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $template = "SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byIncomingWorkflowId;
        $openStatusId = $this->fetchRow($template . " AND `category` = 1")['id'];
        $pendingApprovalStatusId = $this->fetchRow($template . " AND `category` = 2 AND `name` = 'Pending Approval'")['id'];
        $pendingApprovalOutgoingStatusId = $this->fetchRow($template . " AND `category` = 2 AND `name` = 'Pending Approval From Outgoing Institution'")['id'];
        $pendingAsssignmentStatusId = $this->fetchRow($template . " AND `category` = 2 AND `name` = 'Pending Staff Assignment'")['id'];
        $assignedStatusId = $this->fetchRow($template . " AND `category` = 3 AND `name` = 'Assigned'")['id'];
        $rejectedStatusId = $this->fetchRow($template . " AND `category` = 3 AND `name` = 'Rejected'")['id'];

        // workflow_actions
        $workflowActionData = [
            [
                'name' => 'Submit For Approval',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => NULL,
                'workflow_step_id' => $openStatusId,
                'next_workflow_step_id' => $pendingApprovalStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalStatusId,
                'next_workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'next_workflow_step_id' => $pendingAsssignmentStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Assign',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onTransferStaff',
                'workflow_step_id' => $pendingAsssignmentStatusId,
                'next_workflow_step_id' => $assignedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingAsssignmentStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        $institutionOwner = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $openStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingAsssignmentStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $assignedStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $rejectedStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

        $institutionVisible = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $openStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingAsssignmentStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingAsssignmentStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $assignedStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $assignedStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $rejectedStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $rejectedStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionVisible);

        $validateApprove = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalOutgoingStatusId,
                'name' => 'validate_approve',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $validateApprove);

        // STAFF-TRANSFER-2001 (by outgoing)
        $byOutgoingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `workflow_model_id` = " . $this->outgoingWorkflowModelId)['id'];

        // workflow_steps
        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => '1',
                'is_editable' => '1',
                'is_removable' => '1',
                'is_system_defined' => '1',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Incoming Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Staff Transfer',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Transferred',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $template = "SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byOutgoingWorkflowId;
        $openStatusId = $this->fetchRow($template . " AND `category` = 1")['id'];
        $pendingApprovalStatusId = $this->fetchRow($template . " AND `category` = 2 AND `name` = 'Pending Approval'")['id'];
        $pendingApprovalIncomingStatusId = $this->fetchRow($template . " AND `category` = 2 AND `name` = 'Pending Approval From Incoming Institution'")['id'];
        $pendingTransferStatusId = $this->fetchRow($template . " AND `category` = 2 AND `name` = 'Pending Staff Transfer'")['id'];
        $transferredStatusId = $this->fetchRow($template . " AND `category` = 3 AND `name` = 'Transferred'")['id'];
        $rejectedStatusId = $this->fetchRow($template . " AND `category` = 3 AND `name` = 'Rejected'")['id'];

        // workflow_actions
        $workflowActionData = [
            [
                'name' => 'Submit For Approval',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => NULL,
                'workflow_step_id' => $openStatusId,
                'next_workflow_step_id' => $pendingApprovalStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalStatusId,
                'next_workflow_step_id' => $pendingApprovalIncomingStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalIncomingStatusId,
                'next_workflow_step_id' => $pendingTransferStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingApprovalIncomingStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Transfer',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onTransferStaff',
                'workflow_step_id' => $pendingTransferStatusId,
                'next_workflow_step_id' => $transferredStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingTransferStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        $institutionOwner = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $openStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalIncomingStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingTransferStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $transferredStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $rejectedStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

        $institutionVisible = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $openStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalIncomingStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalIncomingStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingTransferStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingTransferStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $transferredStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $transferredStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $rejectedStatusId,
                'name' => 'institution_visible',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $rejectedStatusId,
                'name' => 'institution_visible',
                'value' => '2'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionVisible);

        $validateApprove = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalIncomingStatusId,
                'name' => 'validate_approve',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $validateApprove);

        // labels
        $this->execute("CREATE TABLE `z_3997_labels` LIKE `labels`");
        $this->execute("INSERT INTO `z_3997_labels` SELECT * FROM `labels` WHERE `module` LIKE 'StaffTransfer%'");
        $this->execute("DELETE FROM `labels` WHERE `module` LIKE 'StaffTransfer%'");

        $labels = [
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'previous_institution_id',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'Current Institution',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'previous_end_date',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'Current End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'previous_FTE',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'New FTE',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'previous_staff_type_id',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'New Staff Type',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'new_start_date',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'Requested Start Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'previous_institution_id',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Current Institution',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_FTE',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Requested FTE',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_institution_position_id',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Requested Institution Position',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_staff_type_id',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Requested Staff Type',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_start_date',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Requested Start Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_end_date',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Requested End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'previous_end_date',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('labels', $labels);

        // security_functions
        $staffTransferInSql = "UPDATE security_functions
                                SET `name` = 'Staff Transfer In',
                                `_view` = 'StaffTransferIn.index|StaffTransferIn.view',
                                `_edit` = 'StaffTransferIn.edit',
                                `_add` = null,
                                `_delete` = 'StaffTransferIn.remove',
                                `_execute` = null
                                WHERE `id` = 1039";

        $staffTransferOutSql = "UPDATE security_functions
                                SET `name` = 'Staff Transfer Out',
                                `_view` = 'StaffTransferOut.index|StaffTransferOut.view',
                                `_edit` = 'StaffTransferOut.edit',
                                `_add` = 'StaffTransferOut.add',
                                `_delete` = 'StaffTransferOut.remove',
                                `_execute` = null
                                WHERE `id` = 1040";

        $this->execute($staffTransferInSql);
        $this->execute($staffTransferOutSql);
    }

    // rollback
    public function down()
    {
        // rename z_3997_institution_staff_assignments
        $InstitutionStaffAssignments = $this->table('z_3997_institution_staff_assignments');
        $InstitutionStaffAssignments->rename('institution_staff_assignments');

        // drop institution_staff_transfers
        $this->dropTable('institution_staff_transfers');

        // drop workflow_steps_params
        $this->dropTable('workflow_steps_params');

        // delete workflow_models
        $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $this->incomingWorkflowModelId);
        $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $this->outgoingWorkflowModelId);

        // delete workflows
        $byIncomingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `workflow_model_id` = " . $this->incomingWorkflowModelId)['id'];
        $byOutgoingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `workflow_model_id` = " . $this->outgoingWorkflowModelId)['id'];
        $this->execute("DELETE FROM `workflows` WHERE `id` = " . $byIncomingWorkflowId);
        $this->execute("DELETE FROM `workflows` WHERE `id` = " . $byOutgoingWorkflowId);

        // delete workflow_actions
        $this->execute("DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byIncomingWorkflowId . "
            )");
        $this->execute("DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byOutgoingWorkflowId . "
            )");

        // delete workflow_steps_roles
        $this->execute("DELETE FROM `workflow_steps_roles` WHERE `workflow_steps_roles`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byIncomingWorkflowId . "
            )");
        $this->execute("DELETE FROM `workflow_steps_roles` WHERE `workflow_steps_roles`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byOutgoingWorkflowId . "
            )");

        // delete workflow_statuses_steps
        $this->execute("DELETE FROM `workflow_statuses_steps` WHERE `workflow_statuses_steps`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byIncomingWorkflowId . "
            )");
        $this->execute("DELETE FROM `workflow_statuses_steps` WHERE `workflow_statuses_steps`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $byOutgoingWorkflowId . "
            )");

        // delete workflow_steps
        $this->execute("DELETE FROM `workflow_steps` WHERE `workflow_id` = " . $byIncomingWorkflowId);
        $this->execute("DELETE FROM `workflow_steps` WHERE `workflow_id` = " . $byOutgoingWorkflowId);

        // delete workflow_statuses
        $this->execute("DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = " . $this->incomingWorkflowModelId);
        $this->execute("DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = " . $this->outgoingWorkflowModelId);

        // delete workflow_transitions
        $this->execute("DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = " . $this->incomingWorkflowModelId);
        $this->execute("DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = " . $this->outgoingWorkflowModelId);

        // labels
        $this->execute("DELETE FROM `labels` WHERE `module` = 'StaffTransferOut'");
        $this->execute("DELETE FROM `labels` WHERE `module` = 'StaffTransferIn'");
        $this->execute("INSERT INTO `labels` SELECT * FROM `z_3997_labels`");
        $this->dropTable('z_3997_labels');

        // security_functions
        $staffTransferInSql = "UPDATE security_functions
                                SET `name` = 'Transfer Requests',
                                `_view` = 'StaffTransferRequests.index|StaffTransferRequests.view',
                                `_edit` = null,
                                `_add` = null,
                                `_delete` = 'StaffTransferRequests.remove',
                                `_execute` = 'StaffTransferRequests.edit|StaffTransferRequests.add'
                                WHERE `id` = 1039";

        $staffTransferOutSql = "UPDATE security_functions
                                SET `name` = 'Transfer Approvals',
                                `_view` = 'StaffTransferApprovals.index|StaffTransferApprovals.view',
                                `_edit` = null,
                                `_add` = null,
                                `_delete` = null,
                                `_execute` = 'StaffTransferApprovals.edit|StaffTransferApprovals.view'
                                WHERE `id` = 1040";

        $this->execute($staffTransferInSql);
        $this->execute($staffTransferOutSql);
    }
}
