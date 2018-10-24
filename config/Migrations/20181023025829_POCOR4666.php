<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;

class POCOR4666 extends AbstractMigration
{
    public function up()
    {
        // patch to insert new roles to workflow_step_roles for any institution case workflow post events rules to cater for the current logic
        $workflowRoles = [
            'Workflow.onAssignToHomeRoomTeacher' => 'HOMEROOM_TEACHER',
            'Workflow.onAssignToSecondaryTeacher' => 'HOMEROOM_TEACHER',
            'Workflow.onAssignToPrincipal' => 'PRINCIPAL'
        ];

        $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
        $homeroomRoleEntity = $SecurityRolesTable
            ->find()
            ->where([$SecurityRolesTable->aliasField('code') => 'HOMEROOM_TEACHER'])
            ->first();

        $principalRoleEntity = $SecurityRolesTable
            ->find()
            ->where([$SecurityRolesTable->aliasField('code') => 'PRINCIPAL'])
            ->first();

        $homeroomRoleId = $homeroomRoleEntity->id;
        $principalRoleId = $principalRoleEntity->id;

        $WorkflowStepsTable = TableRegistry::get('Workflow.WorkflowSteps');
        $WorkflowRulesTable = TableRegistry::get('Workflow.WorkflowRules');
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');

        $patchModel = 'Cases.InstitutionCases'; 
        $patchFeature = 'StudentAttendances';

        $workflowIds = $WorkflowsTable
            ->find()
            ->contain('WorkflowModels')
            ->where([
                'WorkflowModels.model' => $patchModel
            ])
            ->extract('id')
            ->toArray();

        foreach ($workflowIds as $workflowId) {
            $firstStepEntity = $WorkflowRulesTable->getWorkflowFirstStep($workflowId, true);
            $firstStepId = $firstStepEntity->id;

            $securityRulesEntity = $WorkflowRulesTable
                ->find()
                ->contain('WorkflowRuleEvents')
                ->where([
                    $WorkflowRulesTable->aliasField('workflow_id') => $workflowId
                ])
                ->toArray();

            pr($securityRulesEntity);
            die;
        }

        // workflow_steps_roles
        // $this->execute('CREATE TABLE `z_4666_workflow_steps_roles` LIKE `workflow_steps_roles`');
        // $this->execute('INSERT INTO `z_4666_workflow_steps_roles` SELECT * FROM `workflow_steps_roles`');
    }

    public function down()
    {
        // workflow_steps_roles
        // $this->execute('DROP TABLE IF EXISTS `workflow_steps_roles`');
        // $this->execute('RENAME TABLE `z_4666_workflow_steps_roles` TO `workflow_steps_roles`');
    }
}
