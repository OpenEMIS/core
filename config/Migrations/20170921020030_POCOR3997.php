<?php

use Phinx\Migration\AbstractMigration;

class POCOR3997 extends AbstractMigration
{
    // commit
    public function up()
    {
        $workflowModels = [
            '13' => [
                'workflow_model' => [
                    'name' => 'Institutions > Staff > Outgoing Transfer',
                    'model' => 'Institution.InstitutionStaffOutgoingAssignments'
                ],
                'workflow' => [
                    'code' => 'STAFF-OUTGOING-TRANSFER-1001',
                    'name' => 'Staff Outgoing Transfer'
                ],
                'workflow_action' => [
                    'approve_event_key' => 'Workflow.onRequestTransferFromIncomingInstitution'
                ]
            ],
            '14' => [
                'workflow_model' => [
                    'name' => 'Institutions > Staff > Incoming Transfer',
                    'model' => 'Institution.InstitutionStaffIncomingAssignments'
                ],
                'workflow' => [
                    'code' => 'STAFF-INCOMING-TRANSFER-1001',
                    'name' => 'Staff Incoming Transfer'
                ],
                'workflow_action' => [
                    'approve_event_key' => 'Workflow.onTransferStaff'
                ]
            ]
        ];

        foreach ($workflowModels as $workflowModelId => $arr) {
            // workflow_models
            $workflowModelData = [
                'id' => $workflowModelId,
                'name' => $arr['workflow_model']['name'],
                'model' => $arr['workflow_model']['model'],
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ];
            $this->insert('workflow_models', $workflowModelData);

            // workflows
            $workflowData = [
                'code' => $arr['workflow']['code'],
                'name' => $arr['workflow']['name'],
                'workflow_model_id' => $workflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ];
            $this->insert('workflows', $workflowData);

            $workflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = '" . $arr['workflow']['code'] . "'")['id'];

            // workflowSteps
            $workflowStepData = [
                [
                    'name' => 'Open',
                    'category' => '1',
                    'is_editable' => '1',
                    'is_removable' => '1',
                    'is_system_defined' => '1',
                    'workflow_id' => $workflowId,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'name' => 'Pending Approval',
                    'category' => '2',
                    'is_editable' => '0',
                    'is_removable' => '0',
                    'is_system_defined' => '1',
                    'workflow_id' => $workflowId,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'name' => 'Closed',
                    'category' => '3',
                    'is_editable' => '0',
                    'is_removable' => '0',
                    'is_system_defined' => '1',
                    'workflow_id' => $workflowId,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ],
                [
                    'name' => 'Approved',
                    'category' => '3',
                    'is_editable' => '0',
                    'is_removable' => '0',
                    'is_system_defined' => '0',
                    'workflow_id' => $workflowId,
                    'created_user_id' => '1',
                    'created' => date('Y-m-d H:i:s')
                ]
            ];
            $this->insert('workflow_steps', $workflowStepData);

            $openStatusId = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . " AND `category` = 1")['id'];
            $pendingApprovalStatusId = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . " AND `category` = 2")['id'];
            $closedStatusId = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . " AND `category` = 3 AND `name` = 'Closed'")['id'];
            $approvedStatusId = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . " AND `category` = 3 AND `name` = 'Approved'")['id'];

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
                    'event_key' => $arr['workflow_action']['approve_event_key'],
                    'workflow_step_id' => $pendingApprovalStatusId,
                    'next_workflow_step_id' => $approvedStatusId,
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
                ]
            ];
            $this->insert('workflow_actions', $workflowActionData);
        }

        // institution_staff_outgoing_assignments
        $InstitutionStaffOutgoingAssignments = $this->table('institution_staff_outgoing_assignments', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all outgoing staff assignments for a particular institution'
        ]);

        $InstitutionStaffOutgoingAssignments
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
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
            ->addColumn('next_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('institution_staff_incoming_assignment_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_staff_incoming_assignments.id'
            ])
            ->addColumn('staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('institution_position_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_positions.id'
            ])
            ->addColumn('FTE', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 2,
                'null' => true
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
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
            ->addIndex('institution_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('next_institution_id')
            ->addIndex('institution_staff_incoming_assignment_id')
            ->addIndex('staff_type_id')
            ->addIndex('institution_position_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();


        // institution_staff_incoming_assignments
        $InstitutionStaffIncomingAssignments = $this->table('institution_staff_incoming_assignments', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all incoming staff assignments for a particular institution'
        ]);

        $InstitutionStaffIncomingAssignments
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('staff_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('institution_staff_outgoing_assignment_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_staff_outgoing_assignments.id'
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
            ->addColumn('institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('staff_type_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to staff_types.id'
            ])
            ->addColumn('institution_position_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institution_positions.id'
            ])
            ->addColumn('FTE', 'decimal', [
                'default' => null,
                'precision' => 5,
                'scale' => 2,
                'null' => false
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
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
            ->addIndex('institution_staff_outgoing_assignment_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('institution_id')
            ->addIndex('staff_type_id')
            ->addIndex('institution_position_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();
    }

    // rollback
    public function down()
    {
        $workflowModels = ['13' => 'STAFF-OUTGOING-TRANSFER-1001', '14' => 'STAFF-INCOMING-TRANSFER-1001'];

        foreach ($workflowModels as $workflowModelId => $workflowCode) {
            // delete workflow_models
            $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $workflowModelId);

            // delete workflows
            $workflowId = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = '" . $workflowCode . "'
                AND `workflow_model_id` = " . $workflowModelId)['id'];
            $this->execute("DELETE FROM `workflows` WHERE `id` = " . $workflowId);

            // delete workflow_actions
            $this->execute("DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
                    SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
                )");

            // delete workflow_steps_roles
            $this->execute("DELETE FROM `workflow_steps_roles` WHERE `workflow_steps_roles`.`workflow_step_id` IN (
                    SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
                )");

            // delete workflow_statuses_steps
            $this->execute("DELETE FROM `workflow_statuses_steps` WHERE `workflow_statuses_steps`.`workflow_step_id` IN (
                    SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
                )");

            // delete workflow_steps
            $this->execute("DELETE FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId);

            // delete workflow_statuses
            $this->execute("DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = " . $workflowModelId);

            // delete workflow_transitions
            $this->execute("DELETE FROM `workflow_transitions` WHERE `workflow_model_id` = " . $workflowModelId);
        }

        // drop institution_staff_transfers table
        $this->dropTable('institution_staff_outgoing_assignments');
        $this->dropTable('institution_staff_incoming_assignments');
    }
}
