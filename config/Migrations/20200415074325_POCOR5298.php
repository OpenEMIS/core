<?php

use Phinx\Migration\AbstractMigration;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class POCOR5298 extends AbstractMigration
{
    public function up()
    {        
        // security_roles
        $this->execute('CREATE TABLE `z_5298_security_roles` LIKE `security_roles`');
        $this->execute('INSERT INTO `z_5298_security_roles` SELECT * FROM `security_roles`');
        $this->execute("UPDATE `security_roles` SET `code` = 'MOE_ADMIN' WHERE `name` = 'MOE ADMIN'");
        $securityRolesRows = $this->fetchAll("SELECT * FROM `security_roles` WHERE `name` = 'MOE ADMIN' AND `code` = 'MOE_ADMIN'");
        
        if(!empty($securityRolesRows[0]['id'])){
        
            $securityRolesId = $securityRolesRows[0]['id'];
            // staff_position_titles
            $this->execute('CREATE TABLE `z_5298_staff_position_titles` LIKE `staff_position_titles`');
            $this->execute('INSERT INTO `z_5298_staff_position_titles` SELECT * FROM `staff_position_titles`');
            $this->execute("INSERT INTO `staff_position_titles` (`name`, `type`, `security_role_id`, `order`, `created_user_id`, `created`) VALUE('MOE ADMIN', 0, ".$securityRolesId.", 96, 2, NOW())");

            $staffPositionTitlesRows = $this->fetchAll('SELECT * FROM `staff_position_titles` ORDER BY id DESC LIMIT 1');
            $staffPositionTitlesId = $staffPositionTitlesRows[0]['id'];
            
            // staff_position_titles_grades
            $this->execute('CREATE TABLE `z_5298_staff_position_titles_grades` LIKE `staff_position_titles_grades`');
            $this->execute('INSERT INTO `z_5298_staff_position_titles_grades` SELECT * FROM `staff_position_titles_grades`');
            $this->execute("INSERT INTO `staff_position_titles_grades` (`staff_position_title_id`, `staff_position_grade_id`) 
    VALUE('".$staffPositionTitlesId."', '-1');");

            // workflow_rule_events
            $this->execute('CREATE TABLE `z_5298_workflow_rule_events` LIKE `workflow_rule_events`');
            $this->execute('INSERT INTO `z_5298_workflow_rule_events` SELECT * FROM `workflow_rule_events`');
            $this->execute("INSERT INTO `workflow_rule_events` (`workflow_rule_id`, `event_key`) VALUE('18', 'Workflow.onAssignToMoeadmin')");
                    $this->execute("INSERT INTO `workflow_rule_events` (`workflow_rule_id`, `event_key`) VALUE('19', 'Workflow.onAssignToMoeadmin')");

            // patch to insert new roles to workflow_step_roles for any institution case workflow post events rules to cater for the current logic
            $workflowRoles = [
                'Workflow.onAssignToMoeadmin' => 'MOE_ADMIN'
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
                    $SecurityRolesTable->aliasField('code IN ') => ['MOE_ADMIN']
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
            $this->execute('CREATE TABLE `z_5298_workflow_steps_roles` LIKE `workflow_steps_roles`');
            $this->execute('INSERT INTO `z_5298_workflow_steps_roles` SELECT * FROM `workflow_steps_roles`');

            if (!empty($patchData)) {
                $this->insert('workflow_steps_roles', $patchData);
            }
        }
    }

    public function down()
    {
		//security_roles
        $this->execute('DROP TABLE IF EXISTS `security_roles`');
        $this->execute('RENAME TABLE `z_5298_security_roles` TO `security_roles`');
		
		//workflow_rule_events
        $this->execute('DROP TABLE IF EXISTS `workflow_rule_events`');
        $this->execute('RENAME TABLE `z_5298_workflow_rule_events` TO `workflow_rule_events`');
		
        //workflow_steps_roles
        $this->execute('DROP TABLE IF EXISTS `workflow_steps_roles`');
        $this->execute('RENAME TABLE `z_5298_workflow_steps_roles` TO `workflow_steps_roles`');	

		// staff_position_titles
		$this->execute('DROP TABLE IF EXISTS `staff_position_titles`');
        $this->execute('RENAME TABLE `z_5298_staff_position_titles` TO `staff_position_titles`');	
		
		// staff_position_titles_grades
		$this->execute('DROP TABLE IF EXISTS `staff_position_titles_grades`');
        $this->execute('RENAME TABLE `z_5298_staff_position_titles_grades` TO `staff_position_titles_grades`');	
    }
}
