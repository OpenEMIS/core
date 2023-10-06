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
        $WorkflowsId = $WorkflowsTable->find()->select(['id' => $WorkflowsTable->aliasField('id')])->where([$WorkflowsTable->aliasField('code')=>'POSITION-1001'])->first();

        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowStepsIdA = $WorkflowStepsTable->find()->select(['id' => $WorkflowStepsTable->aliasField('id')])->where([$WorkflowStepsTable->aliasField('workflow_id')=>$WorkflowsId['id'],$WorkflowStepsTable->aliasField('name')=>'Pending For Approval'])->first();

        $WorkflowStepsIdB = $WorkflowStepsTable->find()->select(['id' => $WorkflowStepsTable->aliasField('id')])->where([$WorkflowStepsTable->aliasField('workflow_id')=>$WorkflowsId['id'],$WorkflowStepsTable->aliasField('name')=>'Pending For Deactivation'])->first();
        $WorkflowStepsIdOneA = $WorkflowStepsTable->find()->select(['id' => $WorkflowStepsTable->aliasField('id')])->where([$WorkflowStepsTable->aliasField('workflow_id')=>$WorkflowsId['id'],$WorkflowStepsTable->aliasField('name')=>'Active'])->first();
        $WorkflowStepsIdOneB = $WorkflowStepsTable->find()->select(['id' => $WorkflowStepsTable->aliasField('id')])->where([$WorkflowStepsTable->aliasField('workflow_id')=>$WorkflowsId['id'],$WorkflowStepsTable->aliasField('name')=>'Inactive'])->first();

        $WorkflowStepsIda = $WorkflowStepsIdA['id'];
        $WorkflowStepsIdb = $WorkflowStepsIdB['id'];
        $WorkflowStepsIdOna = $WorkflowStepsIdOneA['id'];
        $WorkflowStepsIdOnb = $WorkflowStepsIdOneB['id'];
        // backup the table
        $this->execute('CREATE TABLE `z_7016_workflow_actions` LIKE `workflow_actions`');
        $this->execute('INSERT INTO `z_7016_workflow_actions` SELECT * FROM `workflow_actions`');

        $WorkflowActionTable = TableRegistry::get('Workflow.WorkflowActions');
        $this->execute("UPDATE workflow_actions SET `event_key` = 'Workflow.onApprovalofEnableStaffAssignment' WHERE `next_workflow_step_id`=$WorkflowStepsIdOna and `workflow_step_id`=$WorkflowStepsIda");
        $this->execute("UPDATE workflow_actions SET `event_key` = 'Workflow.onApprovalofDisableStaffAssignment' WHERE `next_workflow_step_id`=$WorkflowStepsIdOnb and `workflow_step_id`=$WorkflowStepsIdb");
        
    }

    public function down()
    {
        $this->dropTable('workflow_actions');
        $this->table('z_7016_workflow_actions')->rename('workflow_actions');
    } 
}
