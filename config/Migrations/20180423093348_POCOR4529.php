<?php

use Cake\Utility\Text;
use Phinx\Migration\AbstractMigration;

class POCOR4529 extends AbstractMigration
{
    public function up()
    {
        $workflowModelId = 19;
        $workflowCode = 'STAFF-APPRAISAL-1001';

        // workflow_models
        $WorkflowModels = $this->table('workflow_models');
        $modelData = [
            'id' => $workflowModelId,
            'name' => 'Staff > Career > Appraisals',
            'model' => 'Institution.StaffAppraisals',
            'filter' => 'StaffAppraisal.AppraisalTypes',
            'is_school_based' => 1,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];

        $WorkflowModels
            ->insert($modelData)
            ->saveData();

        // workflows
        $Workflows = $this->table('workflows');
        $workflowData = [
            'code' => $workflowCode,
            'name' => 'Staff Appraisal - General',
            'workflow_model_id' => $workflowModelId,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ];

        $Workflows
            ->insert($workflowData)
            ->saveData();

        // workflow_steps
        $workflowEntity = $this->fetchRow("SELECT `id` FROM `workflows` WHERE `code` = '" . $workflowCode . "'");
        $workflowId = $workflowEntity['id'];

        $WorkflowSteps = $this->table('workflow_steps');
        $stepData = [
            [
                'name' => 'Open',
                'category' => 1,
                'is_editable' => 1,
                'is_removable' => 1,
                'is_system_defined' => 1,
                'workflow_id' => $workflowId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending For Approval',
                'category' => 2,
                'is_editable' => 0,
                'is_removable' => 0,
                'is_system_defined' => 1,
                'workflow_id' => $workflowId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Closed',
                'category' => 3,
                'is_editable' => 0,
                'is_removable' => 0,
                'is_system_defined' => 1,
                'workflow_id' => $workflowId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $WorkflowSteps
            ->insert($stepData)
            ->saveData();

        // workflows_filters
        $WorkflowsFilters = $this->table('workflows_filters');
        $filterData = [
            [
                'id' => Text::uuid(),
                'workflow_id' => $workflowId,
                'filter_id' => 0
            ]
        ];

        $WorkflowsFilters
            ->insert($filterData)
            ->saveData();

        // workflow_actions
        $openStepEntity = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `category` = 1 AND `workflow_id` = " . $workflowId);
        $pendingStepEntity = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `category` = 2  AND `workflow_id` = " . $workflowId);
        $closedStepEntity = $this->fetchRow("SELECT `id` FROM `workflow_steps` WHERE `category` = 3 AND `workflow_id` = " . $workflowId);

        $openStepId = $openStepEntity['id'];
        $pendingStepId = $pendingStepEntity['id'];
        $closedStepId = $closedStepEntity['id'];

        $WorkflowActions = $this->table('workflow_actions');
        $actionData = [
            [
                'name' => 'Submit For Approval',
                'action' => 0,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $pendingStepId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Cancel',
                'action' => 1,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'workflow_step_id' => $openStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'action' => 0,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'event_key' => 'Workflow.onAssignBack',
                'workflow_step_id' => $pendingStepId,
                'next_workflow_step_id' => $closedStepId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'action' => 1,
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 1,
                'event_key' => 'Workflow.onAssignBack',
                'workflow_step_id' => $pendingStepId,
                'next_workflow_step_id' => $openStepId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Approve',
                'action' => 0,
                'visible' => 0,
                'comment_required' => 0,
                'allow_by_assignee' => 0,
                'workflow_step_id' => $closedStepId,
                'next_workflow_step_id' => 0,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'action' => 1,
                'visible' => 0,
                'comment_required' => 0,
                'allow_by_assignee' => 0,
                'workflow_step_id' => $closedStepId,
                'next_workflow_step_id' => 0,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reopen',
                'visible' => 1,
                'comment_required' => 0,
                'allow_by_assignee' => 0,
                'workflow_step_id' => $closedStepId,
                'next_workflow_step_id' => $openStepId,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $WorkflowActions
            ->insert($actionData)
            ->saveData();

        // institution_staff_appraisals
        $StaffAppraisal = $this->table('institution_staff_appraisals');
        $StaffAppraisal
            ->addColumn('assignee_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'default' => 0,
                'comment' => 'links to security_users.id',
                'after' => 'appraisal_period_id'
            ])
            ->addColumn('status_id', 'integer', [
                'limit' => 11,
                'null' => false,
                'comment' => 'links to workflow_steps.id',
                'after' => 'assignee_id'
            ])
            ->save();

        $this->execute('UPDATE institution_staff_appraisals SET status_id = ' . $openStepId);
    }

    public function down()
    {
        $workflowModelId = 19;

        // workflow_models
        $this->execute('DELETE FROM workflow_models WHERE `id` = ' . $workflowModelId);

        // workflow
        $this->execute('
            DELETE FROM `workflows` WHERE NOT EXISTS (
                SELECT 1 FROM `workflow_models` WHERE `workflow_models`.`id` = `workflows`.`workflow_model_id`
            )
        ');

        // workflow_steps
        $this->execute('
            DELETE FROM `workflow_steps` WHERE NOT EXISTS (
                SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflow_steps`.`workflow_id`
            )
        ');

        // workflow_actions
        $this->execute('
            DELETE FROM `workflow_actions` WHERE NOT EXISTS (
                SELECT 1 FROM `workflow_steps` WHERE `workflow_steps`.`id` = `workflow_actions`.`workflow_step_id`
            )
        ');

        // workflows_filters
        $this->execute('
            DELETE FROM `workflows_filters` WHERE NOT EXISTS (
                SELECT 1 FROM `workflows` WHERE `workflows`.`id` = `workflows_filters`.`workflow_id`
            )
        ');

        // institution_staff_appraisals
        $StaffAppraisal = $this->table('institution_staff_appraisals');
        $StaffAppraisal
            ->removeColumn('assignee_id')
            ->removeColumn('status_id')
            ->save();
    }
}
