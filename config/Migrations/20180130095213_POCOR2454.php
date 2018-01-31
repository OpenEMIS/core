<?php

use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR2454 extends AbstractMigration
{
    private $admissionModelId = 16;
    private $incomingTransferModelId = 17;
    private $outgoingTransferModelId = 18;

    // commit
    public function up()
    {
        // workflow_models
        $workflowModelData = [
            [
                'id' => $this->admissionModelId,
                'name' => 'Institutions > Students > Student Admission',
                'model' => 'Institution.StudentAdmission',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $this->incomingTransferModelId,
                'name' => 'Institutions > Student Transfer > Receiving',
                'model' => 'Institution.StudentTransferIn',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => $this->outgoingTransferModelId,
                'name' => 'Institutions > Student Transfer > Sending',
                'model' => 'Institution.StudentTransferOut',
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
                'code' => 'STUDENT-ADMISSION-1001',
                'name' => 'Student Admission',
                'workflow_model_id' => $this->admissionModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'STUDENT-TRANSFER-1001',
                'name' => 'Student Transfer - Receiving',
                'workflow_model_id' => $this->incomingTransferModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'STUDENT-TRANSFER-2001',
                'name' => 'Student Transfer - Sending',
                'workflow_model_id' => $this->outgoingTransferModelId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('workflows', $workflowData);

        // rename institution_student_admission
        $this->table('institution_student_admission')->rename('z_2454_institution_student_admission');

        // new institution_student_admission
        $studentAdmission = $this->table('institution_student_admission', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the student admission requests'
        ]);
        $studentAdmission
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => false
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
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
            ->addColumn('institution_class_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
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
            ->addIndex('student_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('institution_class_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->setupAdmissionWorkflow();

        // institution_student_transfers
        $studentTransfers = $this->table('institution_student_transfers', [
            'collation' => 'utf8mb4_unicode_ci',
            'comment' => 'This table contains all the student transfer requests'
        ]);
        $studentTransfers
            ->addColumn('start_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('end_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('requested_date', 'date', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('student_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id'
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
            ->addColumn('institution_class_id', 'integer', [
                'limit' => 11,
                'null' => true,
                'comment' => 'links to institution_classes.id'
            ])
            ->addColumn('previous_institution_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to institutions.id'
            ])
            ->addColumn('previous_academic_period_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to academic_periods.id'
            ])
            ->addColumn('previous_education_grade_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to education_grades.id'
            ])
            ->addColumn('student_transfer_reason_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to student_transfer_reasons.id'
            ])
            ->addColumn('comment', 'text', [
                'default' => null,
                'null' => true
            ])
            ->addColumn('all_visible', 'integer', [
                'default' => '0',
                'limit' => 1,
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
            ->addIndex('student_id')
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->addIndex('institution_id')
            ->addIndex('academic_period_id')
            ->addIndex('education_grade_id')
            ->addIndex('institution_class_id')
            ->addIndex('previous_institution_id')
            ->addIndex('previous_academic_period_id')
            ->addIndex('previous_education_grade_id')
            ->addIndex('student_transfer_reason_id')
            ->addIndex('modified_user_id')
            ->addIndex('created_user_id')
            ->save();

        $this->setupIncomingTransferWorkflow();
        $this->setupOutgoingTransferWorkflow();
    }

    // STUDENT-ADMISSION-1001
    public function setupAdmissionWorkflow()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        $admissionWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->admissionModelId])
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
                'workflow_id' => $admissionWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $admissionWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approved',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $admissionWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $admissionWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Cancellation',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $admissionWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Cancelled',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $admissionWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $openStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $approvedStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Approved'
            ])
            ->extract('id')
            ->first();
        $closedStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Rejected'
            ])
            ->extract('id')
            ->first();
        $pendingCancelStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Cancellation'
            ])
            ->extract('id')
            ->first();
        $cancelledStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Cancelled'
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
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $pendingApprovalStepId,
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
                'event_key' => 'Workflow.onApprove',
                'workflow_step_id' => $pendingApprovalStepId,
                'next_workflow_step_id' => $approvedStepId,
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
                'workflow_step_id' => $pendingApprovalStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Submit For Cancellation',
                'description' => null,
                'action' => null,
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '1',
                'event_key' => null,
                'workflow_step_id' => $approvedStepId,
                'next_workflow_step_id' => $pendingCancelStepId,
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
                'event_key' => 'Workflow.onCancel',
                'workflow_step_id' => $pendingCancelStepId,
                'next_workflow_step_id' => $cancelledStepId,
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
                'workflow_step_id' => $pendingCancelStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        // workflow_statuses
        $workflowStatusesData = [
            [
                'code' => 'PENDING',
                'name' => 'Pending',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->admissionModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->admissionModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'REJECTED',
                'name' => 'Rejected',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->admissionModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->admissionModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_statuses', $workflowStatusesData);

        $pendingId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'PENDING',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->admissionModelId
            ])
            ->extract('id')
            ->first();
        $approvedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'APPROVED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->admissionModelId
            ])
            ->extract('id')
            ->first();
        $rejectedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'REJECTED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->admissionModelId
            ])
            ->extract('id')
            ->first();
        $cancelledId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'CANCELLED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->admissionModelId
            ])
            ->extract('id')
            ->first();

        // workflow_statuses_steps
        $workflowStatusesStepsData = [
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $openStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingApprovalStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $approvedId,
                'workflow_step_id' => $approvedStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $rejectedId,
                'workflow_step_id' => $closedStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingCancelStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $cancelledId,
                'workflow_step_id' => $cancelledStepId
            ]
        ];
        $this->insert('workflow_statuses_steps', $workflowStatusesStepsData);

        // migrate data from z_2454_institution_student_admission to institution_student_admission
        $this->execute("
            INSERT INTO `institution_student_admission` (
                `id`, `start_date`, `end_date`, `student_id`,
                `status_id`,
                `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`,
                `comment`,
                `modified_user_id`, `modified`, `created_user_id`, `created`
            )
            SELECT
                NULL, `start_date`, `end_date`, `student_id`,
                CASE
                    WHEN `status` = 0 THEN " . $openStepId . "
                    WHEN `status` = 1 THEN " . $approvedStepId . "
                    WHEN `status` = 2 THEN " . $closedStepId . "
                    WHEN `status` = 3 THEN " . $closedStepId . "
                END,
                `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`,
                IF(LENGTH(`comment`), `comment`, NULL),
                `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_2454_institution_student_admission`
            WHERE `type` = 1
        ");
    }

    // STUDENT-TRANSFER-1001
    public function setupIncomingTransferWorkflow()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        $incomingTransferWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->incomingTransferModelId])
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
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Sending Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Student Admission',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Admitted',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Cancellation',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Cancelled',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $incomingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $openStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $pendingApprovalOutgoingStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval From Sending Institution'
            ])
            ->extract('id')
            ->first();
        $pendingAdmissionStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Student Admission'
            ])
            ->extract('id')
            ->first();
        $admittedStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Admitted'
            ])
            ->extract('id')
            ->first();
        $closedStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Rejected'
            ])
            ->extract('id')
            ->first();
        $pendingCancelStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Cancellation'
            ])
            ->extract('id')
            ->first();
        $cancelledStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $incomingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Cancelled'
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
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $pendingApprovalStepId,
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
                'workflow_step_id' => $pendingApprovalStepId,
                'next_workflow_step_id' => $pendingApprovalOutgoingStepId,
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
                'workflow_step_id' => $pendingApprovalStepId,
                'next_workflow_step_id' => $closedStepId,
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
                'workflow_step_id' => $pendingApprovalOutgoingStepId,
                'next_workflow_step_id' => $pendingAdmissionStepId,
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
                'workflow_step_id' => $pendingApprovalOutgoingStepId,
                'next_workflow_step_id' => $closedStepId,
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
                'event_key' => 'Workflow.onTransferStudent',
                'workflow_step_id' => $pendingAdmissionStepId,
                'next_workflow_step_id' => $admittedStepId,
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
                'workflow_step_id' => $pendingAdmissionStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Submit For Cancellation',
                'description' => null,
                'action' => null,
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '1',
                'event_key' => null,
                'workflow_step_id' => $admittedStepId,
                'next_workflow_step_id' => $pendingCancelStepId,
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
                'event_key' => 'Workflow.onCancel',
                'workflow_step_id' => $pendingCancelStepId,
                'next_workflow_step_id' => $cancelledStepId,
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
                'workflow_step_id' => $pendingCancelStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        // workflow_steps_params
        $institutionOwner = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $openStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalOutgoingStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingAdmissionStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $admittedStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $closedStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingCancelStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $cancelledStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

        $validateApprove = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalOutgoingStepId,
                'name' => 'validate_approve',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $validateApprove);

        // workflow_statuses
        $workflowStatusesData = [
            [
                'code' => 'PENDING',
                'name' => 'Pending',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->incomingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->incomingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'REJECTED',
                'name' => 'Rejected',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->incomingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->incomingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_statuses', $workflowStatusesData);

        $pendingId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'PENDING',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->incomingTransferModelId
            ])
            ->extract('id')
            ->first();
        $approvedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'APPROVED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->incomingTransferModelId
            ])
            ->extract('id')
            ->first();
        $rejectedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'REJECTED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->incomingTransferModelId
            ])
            ->extract('id')
            ->first();
        $cancelledId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'CANCELLED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->incomingTransferModelId
            ])
            ->extract('id')
            ->first();

        // workflow_statuses_steps
        $workflowStatusesStepsData = [
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $openStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingApprovalStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingApprovalOutgoingStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingAdmissionStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $approvedId,
                'workflow_step_id' => $admittedStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $rejectedId,
                'workflow_step_id' => $closedStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingCancelStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $cancelledId,
                'workflow_step_id' => $cancelledStepId
            ]
        ];
        $this->insert('workflow_statuses_steps', $workflowStatusesStepsData);

        // migrate data from z_2454_institution_student_admission to institution_student_transfers
        $this->execute("
            INSERT INTO `institution_student_transfers` (
                `id`, `start_date`, `end_date`, `requested_date`, `student_id`,
                `status_id`,
                `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`,
                `previous_institution_id`, `previous_academic_period_id`, `previous_education_grade_id`, `student_transfer_reason_id`,
                `comment`,
                `all_visible`,
                `modified_user_id`, `modified`, `created_user_id`, `created`
            )
            SELECT
                NULL, `start_date`, `end_date`, `requested_date`, `student_id`,
                CASE
                    WHEN `status` = 0 THEN " . $openStepId . "
                    WHEN `status` = 1 THEN " . $admittedStepId . "
                    WHEN `status` = 2 THEN " . $closedStepId . "
                    WHEN `status` = 3 THEN " . $closedStepId . "
                END,
                `institution_id`, `academic_period_id`, `new_education_grade_id`, `institution_class_id`,
                `previous_institution_id`, `academic_period_id`, `education_grade_id`, `student_transfer_reason_id`,
                IF(LENGTH(`comment`), `comment`, NULL),
                IF(`status` = 0, 0, 1),
                `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_2454_institution_student_admission`
            WHERE `type` = 2
        ");
    }

    // STUDENT-TRANSFER-2001
    public function setupOutgoingTransferWorkflow()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        $outgoingTransferWorkflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $this->outgoingTransferModelId])
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
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval From Receiving Institution',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Student Transfer',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Transferred',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Rejected',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Cancellation',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Cancelled',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $outgoingTransferWorkflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);

        $openStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $pendingApprovalIncomingStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval From Receiving Institution'
            ])
            ->extract('id')
            ->first();
        $pendingTransferStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Student Transfer'
            ])
            ->extract('id')
            ->first();
        $transferredStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Transferred'
            ])
            ->extract('id')
            ->first();
        $closedStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Rejected'
            ])
            ->extract('id')
            ->first();
        $pendingCancelStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Cancellation'
            ])
            ->extract('id')
            ->first();
        $cancelledStepId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $outgoingTransferWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Cancelled'
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
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $pendingApprovalStepId,
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
                'workflow_step_id' => $pendingApprovalStepId,
                'next_workflow_step_id' => $pendingApprovalIncomingStepId,
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
                'workflow_step_id' => $pendingApprovalStepId,
                'next_workflow_step_id' => $closedStepId,
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
                'workflow_step_id' => $pendingApprovalIncomingStepId,
                'next_workflow_step_id' => $pendingTransferStepId,
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
                'workflow_step_id' => $pendingApprovalIncomingStepId,
                'next_workflow_step_id' => $closedStepId,
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
                'event_key' => 'Workflow.onTransferStudent',
                'workflow_step_id' => $pendingTransferStepId,
                'next_workflow_step_id' => $transferredStepId,
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
                'workflow_step_id' => $pendingTransferStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Submit For Cancellation',
                'description' => null,
                'action' => null,
                'visible' => '1',
                'comment_required' => '1',
                'allow_by_assignee' => '1',
                'event_key' => null,
                'workflow_step_id' => $transferredStepId,
                'next_workflow_step_id' => $pendingCancelStepId,
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
                'event_key' => 'Workflow.onCancel',
                'workflow_step_id' => $pendingCancelStepId,
                'next_workflow_step_id' => $cancelledStepId,
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
                'workflow_step_id' => $pendingCancelStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

        // workflow_steps_params
        $institutionOwner = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $openStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalIncomingStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingTransferStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $transferredStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $closedStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingCancelStepId,
                'name' => 'institution_owner',
                'value' => '1'
            ],
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $cancelledStepId,
                'name' => 'institution_owner',
                'value' => '2'
            ]
        ];
        $this->insert('workflow_steps_params', $institutionOwner);

        $validateApprove = [
            [
                'id' => Text::uuid(),
                'workflow_step_id' => $pendingApprovalIncomingStepId,
                'name' => 'validate_approve',
                'value' => '1'
            ]
        ];
        $this->insert('workflow_steps_params', $validateApprove);

        // workflow_statuses
        $workflowStatusesData = [
            [
                'code' => 'PENDING',
                'name' => 'Pending',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->outgoingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'APPROVED',
                'name' => 'Approved',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->outgoingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'REJECTED',
                'name' => 'Rejected',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->outgoingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'code' => 'CANCELLED',
                'name' => 'Cancelled',
                'is_editable' => 0,
                'is_removable' => 0,
                'workflow_model_id' => $this->outgoingTransferModelId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_statuses', $workflowStatusesData);

        $pendingId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'PENDING',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->outgoingTransferModelId
            ])
            ->extract('id')
            ->first();
        $approvedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'APPROVED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->outgoingTransferModelId
            ])
            ->extract('id')
            ->first();
        $rejectedId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'REJECTED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->outgoingTransferModelId
            ])
            ->extract('id')
            ->first();
        $cancelledId = $WorkflowStatusesTable->find()
            ->where([
                $WorkflowStatusesTable->aliasField('code') => 'CANCELLED',
                $WorkflowStatusesTable->aliasField('workflow_model_id') => $this->outgoingTransferModelId
            ])
            ->extract('id')
            ->first();

        // workflow_statuses_steps
        $workflowStatusesStepsData = [
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $openStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingApprovalStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingApprovalIncomingStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingTransferStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $approvedId,
                'workflow_step_id' => $transferredStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $rejectedId,
                'workflow_step_id' => $closedStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $pendingId,
                'workflow_step_id' => $pendingCancelStepId
            ],
            [
                'id' => Text::uuid(),
                'workflow_status_id' => $cancelledId,
                'workflow_step_id' => $cancelledStepId
            ]
        ];
        $this->insert('workflow_statuses_steps', $workflowStatusesStepsData);

        // security_functions
        $studentAdmissionSql = "UPDATE security_functions
                                SET `name` = 'Student Admission',
                                `_view` = 'StudentAdmission.index|StudentAdmission.view',
                                `_edit` = 'StudentAdmission.edit',
                                `_add` = null,
                                `_delete` = 'StudentAdmission.remove',
                                `_execute` = 'StudentAdmission.execute'
                                WHERE `id` = 1028";

        $studentTransferInSql = "UPDATE security_functions
                                SET `name` = 'Student Transfer In',
                                `_view` = 'StudentTransferIn.index|StudentTransferIn.view|StudentTransferIn.approve',
                                `_edit` = 'StudentTransferIn.edit',
                                `_add` = null,
                                `_delete` = 'StudentTransferIn.remove',
                                `_execute` = null
                                WHERE `id` = 1022";

        $studentTransferOutSql = "UPDATE security_functions
                                SET `name` = 'Student Transfer Out',
                                `_view` = 'StudentTransferOut.index|StudentTransferOut.view|StudentTransferOut.approve|StudentTransferOut.associated',
                                `_edit` = 'StudentTransferOut.edit',
                                `_add` = 'StudentTransferOut.add',
                                `_delete` = 'StudentTransferOut.remove',
                                `_execute` = 'Transfer.add'
                                WHERE `id` = 1023";

        $this->execute($studentAdmissionSql);
        $this->execute($studentTransferInSql);
        $this->execute($studentTransferOutSql);

        // locale_contents
        $localeContent = [
            [
                'en' => 'Student Transfer In',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'Student Transfer Out',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'Previous Institution',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'Current Institution',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'New Institution',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ],
            [
                'en' => 'Previous Education Grade',
                'created_user_id' => 1,
                'created' => '2018-01-18 17:09:49'
            ]
        ];
        $this->insert('locale_contents', $localeContent);
    }

    // rollback
    public function down()
    {
        // drop new institution_student_admission
        $this->dropTable('institution_student_admission');

        // drop institution_student_transfers
        $this->dropTable('institution_student_transfers');

        // rename z_2454_institution_student_admission
        $this->table('z_2454_institution_student_admission')->rename('institution_student_admission');

        $workflowModelsToDelete = [$this->admissionModelId, $this->incomingTransferModelId, $this->outgoingTransferModelId];
        foreach ($workflowModelsToDelete as $modelId) {
            $this->cascadeDeleteWorkflowModel($modelId);
        }

        // revert security_functions
        $studentAdmissionSql = "UPDATE security_functions
                                SET `name` = 'Student Admission',
                                `_view` = 'StudentAdmission.index|StudentAdmission.view',
                                `_edit` = null,
                                `_add` = null,
                                `_delete` = null,
                                `_execute` = 'StudentAdmission.edit|StudentAdmission.view|StudentAdmission.execute'
                                WHERE `id` = 1028";

        $studentTransferInSql = "UPDATE security_functions
                                SET `name` = 'Transfer Request',
                                `_view` = 'TransferRequests.index|TransferRequests.view',
                                `_edit` = null,
                                `_add` = null,
                                `_delete` = 'TransferRequests.remove',
                                `_execute` = 'TransferRequests.add|TransferRequests.edit|Transfer.add'
                                WHERE `id` = 1022";

        $studentTransferOutSql = "UPDATE security_functions
                                SET `name` = 'Transfer Approval',
                                `_view` = 'TransferApprovals.view',
                                `_edit` = null,
                                `_add` = null,
                                `_delete` = null,
                                `_execute` = 'TransferApprovals.edit|TransferApprovals.view'
                                WHERE `id` = 1023";

        $this->execute($studentAdmissionSql);
        $this->execute($studentTransferInSql);
        $this->execute($studentTransferOutSql);
    }

    public function cascadeDeleteWorkflowModel($workflowModelId)
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');

        // delete workflow_models
        $this->execute("DELETE FROM `workflow_models` WHERE `id` = " . $workflowModelId);

        // delete workflows
        $workflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $workflowModelId])
            ->extract('id')
            ->first();
        $this->execute("DELETE FROM `workflows` WHERE `id` = " . $workflowId);

        // delete workflow_actions
        $this->execute("DELETE FROM `workflow_actions` WHERE `workflow_actions`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
            )");

        // delete workflow_steps_roles
        $this->execute("DELETE FROM `workflow_steps_roles` WHERE `workflow_steps_roles`.`workflow_step_id` IN (
                SELECT `id` FROM `workflow_steps` WHERE `workflow_id` = " . $workflowId . "
            )");

        // delete workflow_steps_params
        $this->execute("DELETE FROM `workflow_steps_params` WHERE `workflow_steps_params`.`workflow_step_id` IN (
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
}
