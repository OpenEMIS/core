<?php

use Phinx\Migration\AbstractMigration;

class POCOR3997 extends AbstractMigration
{
    private $workflowModelId = 13;

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
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('institution_id', 'integer', [
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
            ->addColumn('institution_staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_staff.id'
            ])
            ->addColumn('institution_position_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_positions.id'
            ])
            ->addColumn('staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('FTE', 'decimal', [
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
            ->addIndex('previous_institution_id')
            ->addIndex('institution_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('institution_staff_id')
            ->addIndex('institution_position_id')
            ->addIndex('staff_type_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // NOTE data migration here

        // workflow_models
        $workflowModelData = [
            'id' => $this->workflowModelId,
            'name' => 'Institutions > Staff > Transfer',
            'model' => 'Institution.InstitutionStaffTransfers',
            'filter' => NULL,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
        ];
        $this->insert('workflow_models', $workflowModelData);

        // workflows
        $workflowData = [
            [
                'code' => 'STAFF-TRANSFER-1001',
                'name' => 'Staff Transfer - Initiated By Incoming Institution',
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'STAFF-TRANSFER-1002',
                'name' => 'Staff Transfer - Initiated By Outgoing Institution',
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflows', $workflowData);

        // workflow_steps
        $WorkflowSteps = $this->table('workflow_steps');
        $WorkflowSteps->addColumn('params', 'text', [
                'after' => 'workflow_id',
                'default' => null,
                'null' => true
            ])
            ->save();

        // STAFF-TRANSFER-1001 (by incoming)
        $byIncomingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = 'STAFF-TRANSFER-1001'")['id'];

        // workflow_steps
        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => '1',
                'is_editable' => '1',
                'is_removable' => '1',
                'is_system_defined' => '1',
                'workflow_id' => $byIncomingWorkflowId,
                'params' => '{"institution_owner":"1"}',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byIncomingWorkflowId,
                'params' => '{"institution_owner":"1"}',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Outgoing Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byIncomingWorkflowId,
                'params' => '{"institution_owner":"2"}',
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
                'params' => '{"institution_owner":"1"}',
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
                'params' => '{"institution_owner":"1"}',
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
                'params' => '{"institution_owner":"1"}',
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
                'event_key' => NULL,
                'workflow_step_id' => $pendingAsssignmentStatusId,
                'next_workflow_step_id' => $assignedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        // STAFF-TRANSFER-1002 (by outgoing)
        $byOutgoingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = 'STAFF-TRANSFER-1002'")['id'];

        // workflow_steps
        $workflowStepData = [
            [
                'name' => 'Open',
                'category' => '1',
                'is_editable' => '1',
                'is_removable' => '1',
                'is_system_defined' => '1',
                'workflow_id' => $byOutgoingWorkflowId,
                'params' => '{"institution_owner":"2"}',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byOutgoingWorkflowId,
                'params' => '{"institution_owner":"2"}',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Incoming Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $byOutgoingWorkflowId,
                'params' => '{"institution_owner":"1"}',
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
                'params' => '{"institution_owner":"2"}',
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
                'params' => '{"institution_owner":"2"}',
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
                'params' => '{"institution_owner":"2"}',
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
                'name' => 'Assign',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingTransferStatusId,
                'next_workflow_step_id' => $transferredStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);
    }

    // rollback
    public function down()
    {
        // rename z_3997_institution_staff_assignments
        $InstitutionStaffAssignments = $this->table('z_3997_institution_staff_assignments');
        $InstitutionStaffAssignments->rename('institution_staff_assignments');

        // drop institution_staff_transfers
        $this->dropTable('institution_staff_transfers');

        // delete workflow_models
        $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $this->workflowModelId);

        $byIncomingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = 'STAFF-TRANSFER-1001'")['id'];
        $byOutgoingWorkflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = 'STAFF-TRANSFER-1002'")['id'];

        // delete workflows
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
        $this->execute("DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = " . $this->workflowModelId);

        // delete workflow_transitions
        $this->execute("DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = " . $this->workflowModelId);

        // remove params column in workflow_steps
        $WorkflowSteps = $this->table('workflow_steps');
        $WorkflowSteps->removeColumn('params')
            ->save();
    }
}
