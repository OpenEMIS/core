<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR7016 extends AbstractMigration
{
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

        $workflow_models = $this->query("SELECT * FROM workflow_models order by id DESC LIMIT 1 ");
        $workflow_models_id = $workflow_models->fetchAll();
        $workflow_models_id = $workflow_models_id[0]['id'];

        $workflowModelData = [
            [
                'id' => $workflow_models_id+1,
                'name' => 'Institutions > Status',
                'model' => 'Institution.Institutions',
                'filter' => NULL,
                'is_school_based' => '0',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_models', $workflowModelData);


        $get_workflow_models_last_inserted_id = $this->query("SELECT * FROM workflow_models WHERE `name` = 'Institutions > Status' AND `model` = 'Institution.Institutions'");
        $workflow_models_last_inserted_id = $get_workflow_models_last_inserted_id->fetchAll();
        $workflow_models_last_inserted_id_value = $workflow_models_last_inserted_id[0]['id'];



        // workflows
        $workflowData = [
            [
                'code' => 'INSTITUTION-1001',
                'name' => 'Institution Status',
                'workflow_model_id' =>  $workflow_models_last_inserted_id_value,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflows', $workflowData);

        // get the workflowId for the created workflow
        $workflowId = $WorkflowsTable->find()
            ->where([$WorkflowsTable->aliasField('workflow_model_id') => $workflow_models_last_inserted_id_value])
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
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Pending Approval',
                'category' => '2',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '0',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Active',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Inactive',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_steps', $workflowStepData);


        // Get the workflowSteps for the created workflowsteps
        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflow_models_last_inserted_id_value,
                $WorkflowStepsTable->aliasField('category') => 1,
                $WorkflowStepsTable->aliasField('name') => 'Open'
            ])
            ->extract('id')
            ->first();

        $pendingForApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflow_models_last_inserted_id_value,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $activeStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflow_models_last_inserted_id_value,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Active'
            ])
            ->extract('id')
            ->first();
        $InactiveStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflow_models_last_inserted_id_value,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Inactive'
            ])
            ->extract('id')
            ->first();

        //  workflow_actions
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
                'next_workflow_step_id' => $pendingForApprovalStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Cancel',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $openStatusId,
                'next_workflow_step_id' => $InactiveStatusId,
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
                'workflow_step_id' => $pendingForApprovalStatusId,
                'next_workflow_step_id' => $activeStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Reject',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $pendingForApprovalStatusId,
                'next_workflow_step_id' => $InactiveStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Submit for Closure',
                'description' => NULL,
                'action' => '0',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => 'Workflow.onApproveScholarship',
                'workflow_step_id' => $activeStatusId,
                'next_workflow_step_id' => $InactiveStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Submit for Approval',
                'description' => NULL,
                'action' => '1',
                'visible' => '1',
                'comment_required' => '0',
                'allow_by_assignee' => '0',
                'event_key' => NULL,
                'workflow_step_id' => $InactiveStatusId,
                'next_workflow_step_id' => $pendingForApprovalStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_actions', $workflowActionData);

    }
}
