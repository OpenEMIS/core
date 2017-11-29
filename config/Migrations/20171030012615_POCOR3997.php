<?php

use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR3997 extends AbstractMigration
{
    private $incomingWorkflowModelId = 13;
    private $outgoingWorkflowModelId = 14;

    // commit
    public function up()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');

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
            ->addColumn('previous_effective_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('transfer_type', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false,
                'comment' => '1 -> Full Transfer, 2 -> Partial Transfer, 3 -> No Change'
            ])
            ->addColumn('all_visible', 'integer', [
                'default' => '0',
                'limit' => 1,
                'null' => false
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
                'name' => 'Institutions > Staff Transfer > Receiving',
                'model' => 'Institution.StaffTransferIn',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $this->outgoingWorkflowModelId,
                'name' => 'Institutions > Staff Transfer > Sending',
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
                'name' => 'Staff Transfer - Receiving',
                'workflow_model_id' => $this->incomingWorkflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'STAFF-TRANSFER-2001',
                'name' => 'Staff Transfer - Sending',
                'workflow_model_id' => $this->outgoingWorkflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflows', $workflowData);

        // STAFF-TRANSFER-1001 (by incoming)
        $byIncomingWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->incomingWorkflowModelId])
            ->extract('id')
            ->first();

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
                'name' => 'Pending Approval From Sending Institution',
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
                'is_system_defined' => '0',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Closed',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byIncomingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byIncomingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byIncomingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $pendingApprovalOutgoingStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byIncomingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval From Sending Institution'
            ])
            ->extract('id')
            ->first();
        $pendingAsssignmentStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byIncomingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Staff Assignment'
            ])
            ->extract('id')
            ->first();
        $assignedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byIncomingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Assigned'
            ])
            ->extract('id')
            ->first();
        $closedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byIncomingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Closed'
            ])
            ->extract('id')
            ->first();

        // migrate data from z_3997_institution_staff_assignments to institution_staff_transfers
        $this->execute("INSERT INTO `institution_staff_transfers` (
                            `staff_id`,
                            `new_institution_id`,
                            `previous_institution_id`,
                            `status_id`,
                            `new_institution_position_id`,
                            `new_staff_type_id`,
                            `new_FTE`,
                            `new_start_date`,
                            `new_end_date`,
                            `comment`,
                            `all_visible`,
                            `modified_user_id`,
                            `modified`,
                            `created_user_id`,
                            `created`
                        )
                        SELECT
                            `staff_id`,
                            `institution_id`,
                            `previous_institution_id`,
                            CASE
                                WHEN `status` = 0 THEN " . $openStatusId . "
                                WHEN `status` = 1 THEN " . $assignedStatusId . "
                                WHEN `status` = 2 THEN " . $closedStatusId . "
                                WHEN `status` = 3 THEN " . $closedStatusId . "
                            END,
                            `institution_position_id`,
                            `staff_type_id`,
                            `FTE`,
                            `start_date`,
                            `end_date`,
                            `comment`,
                            IF(`status` = 0, 0, 1),
                            `modified_user_id`,
                            `modified`,
                            `created_user_id`,
                            `created`
                        FROM `z_3997_institution_staff_assignments`");

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
                'next_workflow_step_id' => $closedStatusId,
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
                'next_workflow_step_id' => $closedStatusId,
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
                'next_workflow_step_id' => $closedStatusId,
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
                'workflow_step_id' => $closedStatusId,
                'name' => 'institution_owner',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

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
        $byOutgoingWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->outgoingWorkflowModelId])
            ->extract('id')
            ->first();

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
                'name' => 'Pending Approval From Receiving Institution',
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
                'is_system_defined' => '0',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Closed',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $byOutgoingWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byOutgoingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byOutgoingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $pendingApprovalIncomingStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byOutgoingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval From Receiving Institution'
            ])
            ->extract('id')
            ->first();
        $pendingTransferStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byOutgoingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Staff Transfer'
            ])
            ->extract('id')
            ->first();
        $transferredStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byOutgoingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Transferred'
            ])
            ->extract('id')
            ->first();
        $closedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $byOutgoingWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Closed'
            ])
            ->extract('id')
            ->first();

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
                'next_workflow_step_id' => $closedStatusId,
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
                'next_workflow_step_id' => $closedStatusId,
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
                'next_workflow_step_id' => $closedStatusId,
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
                'workflow_step_id' => $closedStatusId,
                'name' => 'institution_owner',
                'value' => '2'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

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
                'field_name' => 'Position End Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'previous_effective_date',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'Effective Date',
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
                'field_name' => 'Start Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferOut',
                'field' => 'comment',
                'module_name' => 'Institution -> Staff Transfer Out',
                'field_name' => 'Transfer Comments',
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
                'field_name' => 'FTE',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_institution_position_id',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Institution Position',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_staff_type_id',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Staff Type',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_start_date',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Start Date',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'new_end_date',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'End Date',
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
            [
                'id' => Text::uuid(),
                'module' => 'StaffTransferIn',
                'field' => 'comment',
                'module_name' => 'Institution -> Staff Transfer In',
                'field_name' => 'Transfer Comments',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => Text::uuid(),
                'module' => 'WorkflowSteps',
                'field' => 'institution_owner',
                'module_name' => 'Workflow -> Steps',
                'field_name' => 'To Be Executed By',
                'visible' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('labels', $labels);

        // security_functions
        $staffTransferInSql = "UPDATE security_functions
                                SET `name` = 'Staff Transfer In',
                                `_view` = 'StaffTransferIn.index|StaffTransferIn.view|StaffTransferIn.approve',
                                `_edit` = 'StaffTransferIn.edit',
                                `_add` = null,
                                `_delete` = 'StaffTransferIn.remove',
                                `_execute` = null
                                WHERE `id` = 1039";

        $staffTransferOutSql = "UPDATE security_functions
                                SET `name` = 'Staff Transfer Out',
                                `_view` = 'StaffTransferOut.index|StaffTransferOut.view|StaffTransferOut.approve',
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
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');

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
        $byIncomingWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->incomingWorkflowModelId])
            ->extract('id')
            ->first();
        $byOutgoingWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->outgoingWorkflowModelId])
            ->extract('id')
            ->first();
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

        // revert labels
        $this->execute("DELETE FROM `labels` WHERE `module` = 'StaffTransferOut'");
        $this->execute("DELETE FROM `labels` WHERE `module` = 'StaffTransferIn'");
        $this->execute("INSERT INTO `labels` SELECT * FROM `z_3997_labels`");
        $this->dropTable('z_3997_labels');

        // revert security_functions
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
