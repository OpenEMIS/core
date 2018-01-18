<?php
use Migrations\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class POCOR2455 extends AbstractMigration
{
    private $workflowModelId = 15;
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    public function up()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        // rename institution_staff_assignments
        $InstitutionStudentWithdraw = $this->table('institution_student_withdraw');
        $InstitutionStudentWithdraw->rename('z_2455_institution_student_withdraw');

        // institution_staff_transfers
        $InstitutionStudentWithdraw = $this->table('institution_student_withdraw', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the student withdrawal requests'
        ]);

        $InstitutionStudentWithdraw
            ->addColumn('effective_date', 'date', [
                'null' => false
            ])
            ->addColumn('student_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('status_id', 'integer', [
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
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('education_grade_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('student_withdraw_reason_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_withdraw_reasons.id'
            ])
            ->addColumn('comment', 'text', [
                'null' => false
            ])
            ->addColumn('modified_user_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'default' => null,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('modified', 'datetime', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('created_user_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
            ])
            ->addColumn('created', 'datetime', [
                'null' => false
            ])
            ->addIndex('academic_period_id')
            ->addIndex('status_id')
            ->addIndex('education_grade_id')
            ->addIndex('institution_id')
            ->addIndex('assignee_id')
            ->addIndex('student_withdraw_reason_id')
            ->addIndex('student_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        // workflow_models
        $workflowModelData = [
            [
                'id' => $this->workflowModelId,
                'name' => 'Institutions > Students > Student Withdraw',
                'model' => 'Institution.StudentWithdraw',
                'filter' => null,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_models', $workflowModelData);

        $workflowData = [
            [
                'code' => 'STUDENT-WITHDRAW-001',
                'name' => 'Student Withdraw',
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('workflows', $workflowData);

        // STUDENT-WITHDRAW-001
        $studentWithdrawId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->workflowModelId])
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
                'workflow_id' => $studentWithdrawId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending for Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $studentWithdrawId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Withdrawn',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $studentWithdrawId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $studentWithdrawId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending for Cancellation',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $studentWithdrawId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Cancelled',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $studentWithdrawId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('workflow_steps', $workflowStepData);

        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $studentWithdrawId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $studentWithdrawId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending for Approval'
            ])
            ->extract('id')
            ->first();
        $withdrawnStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $studentWithdrawId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Withdrawn'
            ])
            ->extract('id')
            ->first();
        $rejectedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $studentWithdrawId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Rejected'
            ])
            ->extract('id')
            ->first();
        $pendingCancelStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $studentWithdrawId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending for Cancellation'
            ])
            ->extract('id')
            ->first();
        $cancelledStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $studentWithdrawId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Cancelled'
            ])
            ->extract('id')
            ->first();

        // migrate data from z_3997_institution_staff_assignments to institution_staff_transfers
        $this->execute("INSERT INTO `institution_student_withdraw` (
                            `effective_date`,
                            `student_id`,
                            `status_id`,
                            `institution_id`,
                            `academic_period_id`,
                            `education_grade_id`,
                            `student_withdraw_reason_id`,
                            `comment`,
                            `modified_user_id`,
                            `modified`,
                            `created_user_id`,
                            `created`
                        )
                        SELECT
                            `effective_date`,
                            `student_id`,
                            CASE
                                WHEN `status` = 0 THEN " . $openStatusId . "
                                WHEN `status` = 1 THEN " . $withdrawnStatusId . "
                                WHEN `status` = 2 THEN " . $rejectedStatusId . "
                                WHEN `status` = 3 THEN " . $rejectedStatusId . "
                            END,
                            `institution_id`,
                            `academic_period_id`,
                            `education_grade_id`,
                            `student_withdraw_reason_id`,
                            `comment`,
                            `modified_user_id`,
                            `modified`,
                            `created_user_id`,
                            `created`
                        FROM `z_2455_institution_student_withdraw`");

        // workflow_actions
        $workflowActionData = [
            [
                'name' => 'Submit For Approval',
                'description' => null,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => null,
                'workflow_step_id' => $openStatusId,
                'next_workflow_step_id' => $pendingApprovalStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => null,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onApproval',
                'workflow_step_id' => $pendingApprovalStatusId,
                'next_workflow_step_id' => $withdrawnStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Submit For Cancellation',
                'description' => null,
                'action' => null,
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '1',
                'event_key' => null,
                'workflow_step_id' => $withdrawnStatusId,
                'next_workflow_step_id' => $pendingCancelStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'description' => null,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onCancel',
                'workflow_step_id' => $pendingCancelStatusId,
                'next_workflow_step_id' => $cancelledStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => null,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => null,
                'workflow_step_id' => $pendingCancelStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => null,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '0',
                'event_key' => null,
                'workflow_step_id' => $pendingApprovalStatusId,
                'next_workflow_step_id' => $rejectedStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        $workflowStatusData = [
            [
                'code' => 'PENDING',
                'name' => 'Pending',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'REJECTED',
                'name' => 'Rejected',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->workflowModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];

        $this->insert('workflow_statuses', $workflowStatusData);

        $pendingId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'PENDING',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();

        $approvedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'APPROVED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();

        $rejectedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'REJECTED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();

        $cancelledId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'CANCELLED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->workflowModelId
            ])
            ->extract('id')
            ->first();

        $workflowStatusStepsData = [
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingApprovalStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $approvedId,
                'workflow_step_id' => $withdrawnStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $rejectedId,
                'workflow_step_id' => $rejectedStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $openStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingCancelStatusId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $cancelledStatusId,
                'workflow_step_id' => $cancelledId
            ]
        ];

        $this->insert('workflow_statuses_steps', $workflowStatusStepsData);

        $localeContent = [
            [
                'en' => 'Submit For Cancellation',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'Cancelled',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'Reject',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ]
        ];

        $this->insert('locale_contents', $localeContent);
    }

    public function down()
    {
        $this->dropTable('institution_student_withdraw');
        $InstitutionStudentWithdraw = $this->table('z_2455_institution_student_withdraw');
        $InstitutionStudentWithdraw->rename('institution_student_withdraw');

        // delete workflow_models
        $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $this->workflowModelId);

        // delete workflow statuses
        $this->execute("DELETE FROM `workflow_statuses` WHERE `workflow_model_id` = " . $this->workflowModelId);

        $WorkflowTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');

        $workflowIds = $WorkflowTable
            ->find()
            ->select([$WorkflowTable->aliasField('id')])
            ->where([
                $WorkflowTable->aliasField('workflow_model_id') => $this->workflowModelId
            ]);

        // workflow steps
        $steps = $WorkflowStepsTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'id'])
            ->select([
                $WorkflowStepsTable->aliasField('id')
            ])
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id'). ' IN ' => $workflowIds
            ])
            ->toArray();

        $steps = '('. implode(',', $steps) . ')';

        // delete workflow
        $this->execute("DELETE FROM `workflows` WHERE `workflow_model_id` = " . $this->workflowModelId);

        // delete workflow steps
        $this->execute("DELETE FROM `workflow_steps` WHERE `id` IN $steps");

        // delete workflow_statuses_steps
        $this->execute("DELETE FROM `workflow_statuses_steps` WHERE `workflow_step_id` IN $steps");

        // delete workflow_actions
        $this->execute("DELETE FROM `workflow_actions` WHERE `workflow_step_id` IN $steps");

        // locale_contents
        $this->execute("DELETE FROM `locale_contents` WHERE `en` IN ('Submit For Cancellation', 'Cancelled', 'Reject')");
    }
}
