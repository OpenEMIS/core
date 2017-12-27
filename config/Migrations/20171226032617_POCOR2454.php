<?php

use Cake\Utility\Text;
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;

class POCOR2454 extends AbstractMigration
{
    private $admissionModelId = 15;
    private $incomingTransferModelId = 16;
    private $outgoingTransferModelId = 17;

    // commit
    public function up()
    {
        // workflow_models
        $workflowModelData = [
            'id' => $this->admissionModelId,
            'name' => 'Institutions > Student Admission',
            'model' => 'Institution.StudentAdmission',
            'filter' => NULL,
            'is_school_based' => '1',
            'created_user_id' => '1',
            'created' => date('Y-m-d H:i:s')
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
            ]
        ];
        $this->insert('workflows', $workflowData);

        // rename institution_student_admission
        $this->table('institution_student_admission')->rename('z_2454_institution_student_admission');

        // new institution_student_admission
        $this->execute('CREATE TABLE `institution_student_admission` LIKE `z_2454_institution_student_admission`');

        $studentAdmission = $this->table('institution_student_admission');
        $studentAdmission
            ->addColumn('status_id', 'integer', [
                'default' => null,
                'limit' => 11,
                'null' => false,
                'comment' => 'links to workflow_steps.id',
                'after' => 'student_id'
            ])
            ->addColumn('assignee_id', 'integer', [
                'default' => '0',
                'limit' => 11,
                'null' => false,
                'comment' => 'links to security_users.id',
                'after' => 'status_id'
            ])
            ->addIndex('status_id')
            ->addIndex('assignee_id')
            ->removeColumn('requested_date')
            ->removeColumn('status')
            ->removeColumn('new_education_grade_id')
            ->removeColumn('previous_institution_id')
            ->removeColumn('student_transfer_reason_id')
            ->removeColumn('type')
            ->save();

        $this->setupStudentAdmissionWorkflow();

        // // institution_student_transfers
        // $this->execute('CREATE TABLE `institution_student_transfers` LIKE `z_2454_institution_student_admission`');
        // $this->execute('INSERT INTO `institution_student_transfers`
        //     SELECT * FROM `z_2454_institution_student_admission`
        //     WHERE `z_2454_institution_student_admission`.`type` = 2');

        // $studentTransfers = $this->table('institution_student_transfers');
        // $studentTransfers
        //     ->addColumn('status_id', 'integer', [
        //         'default' => null,
        //         'limit' => 11,
        //         'null' => false,
        //         'comment' => 'links to workflow_steps.id',
        //         'after' => 'student_id'
        //     ])
        //     ->addColumn('assignee_id', 'integer', [
        //         'default' => '0',
        //         'limit' => 11,
        //         'null' => false,
        //         'comment' => 'links to security_users.id',
        //         'after' => 'status_id'
        //     ])
        //     ->addColumn('all_visible', 'integer', [
        //         'default' => '0',
        //         'limit' => 1,
        //         'null' => false
        //     ])
        //     ->addIndex('status_id')
        //     ->addIndex('assignee_id')
        //     ->removeColumn('status')
        //     ->removeColumn('type')
        //     ->save();
    }

    // STUDENT-ADMISSION-1001
    public function setupStudentAdmissionWorkflow()
    {
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');

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
                'name' => 'Closed',
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

        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 1
            ])
            ->extract('id')
            ->first();
        $pendingApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 2
            ])
            ->extract('id')
            ->first();
        $approvedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Approved'
            ])
            ->extract('id')
            ->first();
        $closedStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $admissionWorkflowId,
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
                'event_key' => 'Workflow.onApprove',
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

        // migrate data from z_2454_institution_student_admission to institution_student_admission
        $this->execute("
            INSERT INTO `institution_student_admission` (
                `start_date`, `end_date`, `student_id`,
                `status_id`,
                `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`, `comment`,
                `modified_user_id`, `modified`, `created_user_id`, `created`
            )
            SELECT
                `start_date`, `end_date`, `student_id`,
                CASE
                    WHEN `status` = 0 THEN " . $openStatusId . "
                    WHEN `status` = 1 THEN " . $approvedStatusId . "
                    WHEN `status` = 2 THEN " . $closedStatusId . "
                    WHEN `status` = 3 THEN " . $closedStatusId . "
                END,
                `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`, `comment`,
                `modified_user_id`, `modified`, `created_user_id`, `created`
            FROM `z_2454_institution_student_admission`
            WHERE `type` = 1
        ");
    }

    // rollback
    public function down()
    {
        // drop new institution_student_admission
        $this->dropTable('institution_student_admission');

        // drop institution_student_transfers
        // $this->dropTable('institution_student_transfers');

        // rename z_2454_institution_student_admission
        $this->table('z_2454_institution_student_admission')->rename('institution_student_admission');

        $workflowModelsToDelete = [$this->admissionModelId];
        foreach ($workflowModelsToDelete as $modelId) {
            $this->cascadeDeleteWorkflowModel($modelId);
        }
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
