<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

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

        $patchModel = 'Cases.InstitutionCases'; 

        $SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
        $WorkflowRulesTable = TableRegistry::get('Workflow.WorkflowRules');
        $WorkflowStepsRolesTable = TableRegistry::get('Workflow.WorkflowStepsRoles');
        $WorkflowsTable = TableRegistry::get('Workflow.Workflows');

        $roleCodeList = $SecurityRolesTable
            ->find('list', [
                'keyField' => 'code',
                'valueField' =>'id'
            ])
            ->where([
                $SecurityRolesTable->aliasField('code IN ') => ['HOMEROOM_TEACHER', 'PRINCIPAL']
            ])
            ->toArray();

        $workflowIds = $WorkflowsTable
            ->find()
            ->contain('WorkflowModels')
            ->where([
                'WorkflowModels.model' => $patchModel
            ])
            ->extract('id')
            ->toArray();

        $patchData = [];
        foreach ($workflowIds as $workflowId) {
            $firstStepEntity = $WorkflowRulesTable->getWorkflowFirstStep($workflowId, true);
            $firstStepId = $firstStepEntity->id;

            $eventKeys = $WorkflowRulesTable
                ->find()
                ->select(['workflow_event_key' => 'WorkflowRuleEvents.event_key'])
                ->innerJoinWith('WorkflowRuleEvents')
                ->where([$WorkflowRulesTable->aliasField('workflow_id') => $workflowId])
                ->group('workflow_event_key')
                ->extract('workflow_event_key')
                ->toArray();

            foreach ($eventKeys as $eventKey) {
                if (array_key_exists($eventKey, $workflowRoles)) {
                    $securityRoleCode = $workflowRoles[$eventKey];
                    $securityRoleId = $roleCodeList[$securityRoleCode];

                    $data = [
                        'workflow_step_id' => $firstStepId,
                        'security_role_id' => $securityRoleId
                    ];

                    if (!$WorkflowStepsRolesTable->exists($data)) {
                        $data['id'] = Text::uuid();

                        $patchData[] = $data;
                    }
                }
            }
        }

        // workflow_steps_roles
        $this->execute('CREATE TABLE `z_4666_workflow_steps_roles` LIKE `workflow_steps_roles`');
        $this->execute('INSERT INTO `z_4666_workflow_steps_roles` SELECT * FROM `workflow_steps_roles`');
        
        if (!empty($patchData)) {
            $this->insert('workflow_steps_roles', $patchData);
        }
    }

    public function down()
    {
        // workflow_steps_roles
        $this->execute('DROP TABLE IF EXISTS `workflow_steps_roles`');
        $this->execute('RENAME TABLE `z_4666_workflow_steps_roles` TO `workflow_steps_roles`');
    }
}
