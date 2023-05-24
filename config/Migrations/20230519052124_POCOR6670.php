<?php
use Cake\ORM\TableRegistry;
use Phinx\Migration\AbstractMigration;
use Cake\Utility\Text;

class POCOR6670 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */
    //Auther::Rishabh Sharma
    public function up()
    {   
        // Backup staff_behaviours table
        $this->execute('DROP TABLE IF EXISTS `zz_6670_staff_behaviours`');
        $this->execute('CREATE TABLE `zz_6670_staff_behaviours` LIKE `staff_behaviours`');
        $this->execute('INSERT INTO `zz_6670_staff_behaviours` SELECT * FROM `staff_behaviours`');
        //workflow_models
        $this->execute('DROP TABLE IF EXISTS `zz_6670_workflow_models`');
        $this->execute('CREATE TABLE `zz_6670_workflow_models` LIKE `workflow_models`');
        $this->execute('INSERT INTO `zz_6670_workflow_models` SELECT * FROM `workflow_models`');


        // Backup workflows table
        $this->execute('DROP TABLE IF EXISTS `zz_6670_workflows`');
        $this->execute('CREATE TABLE `zz_6670_workflows` LIKE `workflows`');
        $this->execute('INSERT INTO `zz_6670_workflows` SELECT * FROM `workflows`');

        // Backup workflow_steps table
        $this->execute('DROP TABLE IF EXISTS `zz_6670_workflow_steps`');
        $this->execute('CREATE TABLE `zz_6670_workflow_steps` LIKE `workflow_steps`');
        $this->execute('INSERT INTO `zz_6670_workflow_steps` SELECT * FROM `workflow_steps`');

        // Backup workflow_actions table
        $this->execute('DROP TABLE IF EXISTS `zz_6670_workflow_actions`');
        $this->execute('CREATE TABLE `zz_6670_workflow_actions` LIKE `workflow_actions`');
        $this->execute('INSERT INTO `zz_6670_workflow_actions` SELECT * FROM `workflow_actions`');

        // add column 
        $table = $this->table('staff_behaviours');
        $table
            ->addColumn('assignee_id', 'integer', [
                'comment' => 'links to security_users.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'staff_behaviour_category_id'
            ])
            ->addIndex('assignee_id')
            ->addColumn('status_id', 'integer', [
                'comment' => 'links to workflow_steps.id',
                'default' => null,
                'limit' => 11,
                'null' => true,
                'after' => 'institution_id'
            ])
            ->addIndex('status_id')
            ->update();

        // add wrokflow behaviour    
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');
        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStatusesTable = TableRegistry::get('Workflow.WorkflowStatuses');

        $workflow_models = $this->query("SELECT * FROM workflow_models order by id DESC LIMIT 1 ");
        $workflow_models_id = $workflow_models->fetchAll();
        $workflow_models_id = $workflow_models_id[0]['id'];


        $workflowModelData = [
            [
                'id' => $workflow_models_id+1,
                'name' => 'Institutions > Behaviour > Staff',
                'model' => 'Institution.StaffBehaviours',
                'filter' => NULL,
                'is_school_based' => '1',
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('workflow_models', $workflowModelData);
        $get_workflow_models_last_inserted_id = $this->query("SELECT * FROM workflow_models WHERE `name` = 'Institutions > Behaviour > Staff' AND `model` = 'Institution.StaffBehaviours'");
        $workflow_models_last_inserted_id = $get_workflow_models_last_inserted_id->fetchAll();
        $workflow_models_last_inserted_id_value = $workflow_models_last_inserted_id[0]['id'];
        // workflows
        $workflowData = [ 
            [
                'code' => 'BHV-STAFF-001',
                'name' => 'Institution Behaviour Staff',
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
                'name' => 'Closed',
                'category' => '3',
                'is_editable' => '0',
                'is_removable' => '0',
                'is_system_defined' => '1',
                'workflow_id' => $workflowId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            
        ];
        $this->insert('workflow_steps', $workflowStepData);


        // Get the workflowSteps for the created workflowsteps
        $openStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 1,
                $WorkflowStepsTable->aliasField('name') => 'Open'
            ])
            ->extract('id')
            ->first();

        $pendingForApprovalStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 2,
                $WorkflowStepsTable->aliasField('name') => 'Pending Approval'
            ])
            ->extract('id')
            ->first();
        $activeStatusId = $WorkflowStepsTable->find()
            ->where([
                $WorkflowStepsTable->aliasField('workflow_id') => $workflowId,
                $WorkflowStepsTable->aliasField('category') => 3,
                $WorkflowStepsTable->aliasField('name') => 'Closed'
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
                'next_workflow_step_id' => $activeStatusId,
                'created_user_id' => '1',
                'created' => date('Y-m-d H:i:s')
            ],
            
        ];
        $this->insert('workflow_actions', $workflowActionData);

    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `staff_behaviours`');
        $this->execute('RENAME TABLE `zz_6670_staff_behaviours` TO `staff_behaviours`');

        $this->execute('DROP TABLE IF EXISTS `workflows`');
        $this->execute('RENAME TABLE `zz_6670_workflows` TO `workflows`');

        $this->execute('DROP TABLE IF EXISTS `workflow_steps`');
        $this->execute('RENAME TABLE `zz_6670_workflow_steps` TO `workflow_steps`');

        $this->execute('DROP TABLE IF EXISTS `workflow_actions`');
        $this->execute('RENAME TABLE `zz_6670_workflow_actions` TO `workflow_actions`'); 
        
        $this->execute('DROP TABLE IF EXISTS `workflow_models`');
        $this->execute('RENAME TABLE `zz_6670_workflow_models` TO `workflow_models`'); 
    }
}
