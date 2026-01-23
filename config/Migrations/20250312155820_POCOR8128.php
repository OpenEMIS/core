<?php

use Migrations\AbstractMigration;
use Cake\Utility\Text;
use Cake\ORM\TableRegistry;

class POCOR8128 extends AbstractMigration
{
    public function up()
    {

        $this->execute('START TRANSACTION;');

        try {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');
            // Create new tables
            $this->createNewTables();

            // All other functions
            $this->createGeneralLeavePolicy();
            $this->changeStaffPositionTitles();
            $this->addNationalCodes();
            $this->makeAcademicPeriodForLeaveNullable();
            $this->addWorkflowSteps();
            $this->addSecurityFunctions();
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('COMMIT;');

        } catch (\Exception $e) {
            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('ROLLBACK;');
            throw $e;  // Re-throw the exception to ensure error reporting
        }
    }

    /**
     * Create general leave policy and associate with staff leave types.
     *
     * @return void
     */
    private function createGeneralLeavePolicy(): void
    {
        // Check if `GP` record exists
        $gpExists = $this->fetchRow("SELECT COUNT(*) as count FROM `staff_leave_policies` WHERE `code` = 'GP';")['count'] > 0;

        if (!$gpExists) {
            // Insert General Policy (GP) record
            $this->execute("INSERT INTO `staff_leave_policies`
    (`code`,
     `name`,
     `description`,
     `created_user_id`,
     `created`) VALUES ('GP', 'General Policies', 'General Policies', 1, NOW());");
        }

        // Fetch the ID of the `GP` record
        $gpPolicyId = $this->fetchRow("SELECT `id` FROM `staff_leave_policies` WHERE `code` = 'GP';")['id'];

        // Insert all staff leave types linked to the `GP` staff leave policy
        $leaveTypes = $this->fetchAll("SELECT `id`, `name` FROM `staff_leave_types`;");

        foreach ($leaveTypes as $type) {
            $uuid = Text::uuid();  // Generate UUID in PHP
            $this->execute("
                INSERT INTO `staff_leave_policy_types`
                    (`id`, `staff_leave_policy_id`, `staff_leave_type_id`, `days`, `rollover`)
                VALUES ('{$uuid}', {$gpPolicyId}, {$type['id']}, 0, 1)
                ON DUPLICATE KEY UPDATE `id` = `id`;
            ");
        }
    }

    /**
     * Add `staff_leave_policy_id` foreign key to `staff_position_titles` table.
     *
     * @return void
     */
    private function changeStaffPositionTitles(): void
    {
        // Backup the existing `staff_position_titles` table before making changes
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8128_staff_position_titles` LIKE `staff_position_titles`;');
        $this->execute('INSERT IGNORE INTO `z_8128_staff_position_titles` SELECT * FROM `staff_position_titles`;');

        // Add `staff_leave_policy_id` column and index
        $this->execute('
        ALTER TABLE `staff_position_titles`
        ADD COLUMN `staff_leave_policy_id` INT UNSIGNED NULL DEFAULT NULL COMMENT "links to staff_leave_policies.id"
        AFTER `security_role_id`,
        ADD INDEX `idx_staff_leave_policy_id` (`staff_leave_policy_id`);
    ');

        // Add foreign key constraint with RESTRICT behavior
        $this->execute('
        ALTER TABLE `staff_position_titles`
        ADD CONSTRAINT `fk_staff_position_titles_policy_id`
            FOREIGN KEY (`staff_leave_policy_id`) REFERENCES `staff_leave_policies` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT;
    ');

        $staffLeavePoliciesTable = TableRegistry::getTableLocator()->get('StaffLeavePolicies');

        // Fetch the ID of the "GP" policy safely
        $gpPolicy = $staffLeavePoliciesTable->find()
            ->select(['id'])
            ->where(['code' => 'GP'])
            ->first();

        if ($gpPolicy) { // this is done only if there is this policy
            $gpPolicyId = $gpPolicy->id;

            // Update staff_position_titles safely using PHP
            $this->execute("
                UPDATE staff_position_titles
                SET staff_leave_policy_id = {$gpPolicyId}
                WHERE staff_leave_policy_id IS NULL;
            ");


            // Modify the column to be NOT NULL after ensuring all rows have a valid `staff_leave_policy_id`
            $this->execute('
        ALTER TABLE `staff_position_titles`
        MODIFY `staff_leave_policy_id` INT UNSIGNED NOT NULL;
    ');

        }


    }

    /**
     * Add unique national codes for staff leave types.
     *
     * @return void
     */
    private function addNationalCodes(): void
    {
        $emptyNameTypes = $this->fetchAll("SELECT `id`, `name` FROM `staff_leave_types` WHERE `national_code` IS NULL OR `national_code` = '';");

        // Backup table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8128_staff_leave_types` LIKE `staff_leave_types`;');
        $this->execute('INSERT IGNORE INTO `z_8128_staff_leave_types` SELECT * FROM `staff_leave_types`;');

        // Update `national_code` with unique values
        $uniqueCodes = [];
        foreach ($emptyNameTypes as $type) {
            $nameParts = explode(' ', $type['name']);
            $firstLetters = array_map(fn($word) => strtoupper($word[0]), $nameParts);
            $baseCode = implode('', $firstLetters);

            $uniqueCode = $baseCode;
            $counter = 1;

            while (in_array($uniqueCode, $uniqueCodes)) {
                $uniqueCode = $baseCode . str_pad($counter++, 2, '0', STR_PAD_LEFT);
            }

            $uniqueCodes[] = $uniqueCode;
            $this->execute("
                UPDATE `staff_leave_types`
                SET `national_code` = '{$uniqueCode}'
                WHERE `id` = {$type['id']};
            ");
        }
    }

    /**
     * Create general leave policy and associate with staff leave types.
     *
     * @return void
     */
    private function makeAcademicPeriodForLeaveNullable(): void
    {

        $this->execute('CREATE TABLE IF NOT EXISTS `z_8128_institution_staff_leave` LIKE `institution_staff_leave`;');
        $this->execute('INSERT IGNORE INTO `z_8128_institution_staff_leave` SELECT * FROM `institution_staff_leave`;');

        $this->execute("
            ALTER TABLE `institution_staff_leave`
            MODIFY `academic_period_id` INT DEFAULT NULL NULL
                COMMENT 'links to academic_periods.id';;
        ");

    }

    private function addWorkflowSteps(): void
    {

        $this->execute('DROP TABLE IF EXISTS `z_8128_workflow_steps`');
        $this->execute('CREATE TABLE `z_8128_workflow_steps` LIKE `workflow_steps`');
        $this->execute('INSERT INTO `z_8128_workflow_steps` SELECT * FROM `workflow_steps`');

        // Backup workflow_actions table
        $this->execute('DROP TABLE IF EXISTS `z_8128_workflow_actions`');
        $this->execute('CREATE TABLE `z_8128_workflow_actions` LIKE `workflow_actions`');
        $this->execute('INSERT INTO `z_8128_workflow_actions` SELECT * FROM `workflow_actions`');

        // Load necessary tables
        $locator = TableRegistry::getTableLocator();
        $WorkflowActionsTable = $locator->get('Workflow.WorkflowActions');
        $WorkflowStepsTable = $locator->get('Workflow.WorkflowSteps');
        $WorkflowsTable = $locator->get('Workflow.Workflows');

        // Get the workflow ID for "Staff Leave" (LEAVE-1001)
        $workflow = $WorkflowsTable->find()
            ->where(['code' => 'LEAVE-1001'])
            ->first();

        if (!$workflow) {
            throw new \RuntimeException('Workflow with code LEAVE-1001 not found');
        }
        $workflowId = $workflow->id;

        // Add the new step "Pending for Recommendation"
        $workflowStepData = [
            'workflow_id' => $workflowId,
            'name' => 'Pending for Recommendation',
            'category' => 2, // "In Progress" category
            'is_editable' => 0,
            'is_removable' => 0,
            'is_system_defined' => 0,
            'created_user_id' => 1, // Adjust based on your setup
            'created' => date('Y-m-d H:i:s'),
        ];
        $WorkflowStepsTable->save($WorkflowStepsTable->newEntity($workflowStepData));

        // Fetch the new "Pending for Recommendation" step ID
        $pendingForRecommendationStep = $WorkflowStepsTable->find()
            ->where([
                'workflow_id' => $workflowId,
                'name' => 'Pending for Recommendation',
            ])
            ->first();

        if (!$pendingForRecommendationStep) {
            throw new \RuntimeException('"Pending for Recommendation" step could not be created');
        }
        $pendingForRecommendationStepId = $pendingForRecommendationStep->id;

        // Fetch the "Open" step ID (to link the new action)
        $openStep = $WorkflowStepsTable->find()
            ->where([
                'workflow_id' => $workflowId,
                'name' => 'Open',
            ])
            ->first();
        $approveStep = $WorkflowStepsTable->find()
            ->where([
                'workflow_id' => $workflowId,
                'name' => 'Approved',
            ])
            ->first();
        $rejectStep = $WorkflowStepsTable->find()
            ->where([
                'workflow_id' => $workflowId,
                'name' => 'Rejected',
            ])
            ->first();

        if (!$openStep) {
            throw new \RuntimeException('"Open" step not found');
        }
        if (!$approveStep) {
            throw new \RuntimeException('"Approve" step not found');
        }
        if (!$rejectStep) {
            throw new \RuntimeException('"Reject" step not found');
        }
        $openStepId = $openStep->id;
        $approveStepId = $approveStep->id;
        $rejectStepId = $rejectStep->id;

        // Add the new action "Submit for Recommendation"
        $workflowActionOpen = $WorkflowActionsTable->find()
            ->where(['workflow_step_id' => $openStepId])
            ->first();

        if ($workflowActionOpen) {
            // Update the record with the new data
            $workflowActionOpen->name = 'Submit for Recommendation';
            $workflowActionOpen->next_workflow_step_id = $pendingForRecommendationStepId; // Link to the new "Pending for Recommendation" step
            $workflowActionOpen->modified_user_id = 1; // Adjust based on your setup
            $workflowActionOpen->modified = date('Y-m-d H:i:s'); // Update the modified timestamp
            // Save the updated record
            $WorkflowActionsTable->save($workflowActionOpen);
        } else {
            throw new \RuntimeException('No record found with workflow_step_id = ' . $openStepId);
        }

        $workflowActionApprove = $WorkflowActionsTable->find()
            ->where(['workflow_step_id' => $pendingForRecommendationStepId,
                'name' => 'Approve'])
            ->first();

        if ($workflowActionApprove) {
            // Update the record with the new data
            $workflowActionApprove->next_workflow_step_id = $approveStepId; // Link to the new "Pending for Recommendation" step
            $workflowActionApprove->modified_user_id = 1; // Adjust based on your setup
            $workflowActionApprove->modified = date('Y-m-d H:i:s'); // Update the modified timestamp
            // Save the updated record
            $WorkflowActionsTable->save($workflowActionApprove);
        } else {
            throw new \RuntimeException('No record found with workflow_step_id = ' . $pendingForRecommendationStepId);
        }

        $workflowActionReject = $WorkflowActionsTable->find()
            ->where(['workflow_step_id' => $pendingForRecommendationStepId,
                'name' => 'Reject'])
            ->first();

        if ($workflowActionReject) {
            // Update the record with the new data
            $workflowActionReject->next_workflow_step_id = $rejectStepId; // Link to the new "Pending for Recommendation" step
            $workflowActionReject->modified_user_id = 1; // Adjust based on your setup
            $workflowActionReject->modified = date('Y-m-d H:i:s'); // Update the modified timestamp
            // Save the updated record
            $WorkflowActionsTable->save($workflowActionReject);
        } else {
            throw new \RuntimeException('No record found with workflow_step_id = ' . $pendingForRecommendationStepId);
        }
    }

    private function addSecurityFunctions(): void
    {

        // Backup security_functions table
        $this->execute('DROP TABLE IF EXISTS `z_8128_security_functions`');
        $this->execute('CREATE TABLE `z_8128_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_8128_security_functions` SELECT * FROM `security_functions`');

        // Getting max order value
        $lastOrder = $this->fetchRow("SELECT  max(`order`) FROM `security_functions`");

        $current_time = date('Y-m-d H:i:s');
        $data = [[
            'name' => 'Staff Leave Policies',
            'controller' => 'Systems',
            'module' => 'Administration',
            'category' => 'Staff Leave',
            'parent_id' => 5000,
            '_view' => 'StaffPolicies.index|StaffPolicies.view',
            '_add' => 'StaffPolicies.add',
            '_edit' => 'StaffPolicies.edit',
            '_delete' => 'StaffPolicies.remove',
            'order' => $lastOrder[0] + 1,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => $current_time
        ], [
            'name' => 'Staff Entitlements',
            'controller' => 'Systems',
            'module' => 'Administration',
            'category' => 'Staff Leave',
            'parent_id' => 5000,
            '_view' => 'StaffEntitlements.index|StaffEntitlements.view',
            '_add' => 'StaffEntitlements.add',
            '_edit' => 'StaffEntitlements.edit',
            '_delete' => 'StaffEntitlements.remove',
            'order' => $lastOrder[0] + 2,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => $current_time
        ], [
            'name' => 'Staff Entitlements',
            'controller' => 'Staff',
            'module' => 'Institutions',
            'category' => 'Staff - Career',
            'parent_id' => 1000,
            '_view' => 'StaffEntitlement.index|StaffEntitlement.view',
            'order' => $lastOrder[0] + 2,
            'visible' => 1,
            'description' => NULL,
            'created_user_id' => 1,
            'created' => $current_time
        ]];
        $table = $this->table('security_functions');
        $table->insert($data);
        $table->saveData();
    }

    public function down()
    {
        $this->execute('START TRANSACTION;');

        try {
            $this->execute('SET FOREIGN_KEY_CHECKS=0;');

            // Step 1: Remove foreign keys
            $this->removeForeignKeys();

            // Step 2: Restore backup tables and track which ones were restored
            $restoredTables = $this->restoreBackupTables();

            // Step 3: Remove inserted data only if the corresponding table was NOT restored
            $this->rollbackInsertedData($restoredTables);

            // Step 4: Drop newly created tables
            $this->dropNewTables();

            $this->execute('SET FOREIGN_KEY_CHECKS=1;');
            $this->execute('COMMIT;');

        } catch (\Exception $e) {
            $this->execute('ROLLBACK;');
            throw $e;
        }
    }

    /**
     * Remove foreign keys that were added in up()
     */
    private function removeForeignKeys(): void
    {
        $foreignKeys = [
            'ALTER TABLE `staff_leave_policy_types` DROP FOREIGN KEY `fk_staff_leave_policy_types_policy_id`;',
            'ALTER TABLE `staff_leave_policy_types` DROP FOREIGN KEY `fk_staff_leave_policy_types_type_id`;',
            'ALTER TABLE `staff_leave_entitlements` DROP FOREIGN KEY `fk_staff_leave_entitlements_staff_id`;',
            'ALTER TABLE `staff_leave_entitlements` DROP FOREIGN KEY `fk_staff_leave_entitlements_leave_type_id`;',
            'ALTER TABLE `institution_staff_leave_entitlements` DROP FOREIGN KEY `fk_inst_staff_leave_staff_id`;',
            'ALTER TABLE `institution_staff_leave_entitlements` DROP FOREIGN KEY `fk_inst_staff_leave_institution_id`;',
            'ALTER TABLE `institution_staff_leave_entitlements` DROP FOREIGN KEY `fk_inst_staff_leave_position_id`;',
            'ALTER TABLE `institution_staff_leave_entitlements` DROP FOREIGN KEY `fk_inst_staff_leave_policy_id`;',
            'ALTER TABLE `institution_staff_leave_entitlements` DROP FOREIGN KEY `fk_inst_staff_leave_type_id`;',
            'ALTER TABLE `staff_position_titles` DROP FOREIGN KEY `fk_staff_position_titles_policy_id`;'
        ];

        foreach ($foreignKeys as $query) {
            $this->execute($query);
        }
    }

    /**
     * Restore backup tables if they exist and return a list of restored tables
     * @return array
     */
    private function restoreBackupTables(): array
    {
        $backupTables = [
            'security_functions',
            'workflow_actions',
            'workflow_steps',
            'institution_staff_leave',
            'staff_position_titles',
            'staff_leave_types'
        ];

        $restoredTables = [];

        foreach ($backupTables as $table) {
            $backupTable = 'z_8128_' . $table;
            if ($this->hasTable($backupTable)) {
                $this->execute("DROP TABLE IF EXISTS `$table`;");
                $this->execute("RENAME TABLE `$backupTable` TO `$table`;");
                $restoredTables[] = $table;
            }
        }

        return $restoredTables;
    }

    /**
     * Roll back inserted or updated data only for tables that were NOT restored
     * @param array $restoredTables
     */
    private function rollbackInsertedData(array $restoredTables): void
    {
        if (!in_array('workflow_steps', $restoredTables)) {
            $this->execute("DELETE FROM `workflow_steps` WHERE `name` = 'Pending for Recommendation';");
        }

        if (!in_array('workflow_actions', $restoredTables)) {
            $this->execute("DELETE FROM `workflow_actions` WHERE `name` = 'Submit for Recommendation';");
        }

        if (!in_array('staff_leave_types', $restoredTables)) {
            $this->execute('UPDATE `staff_leave_types` SET `national_code` = NULL WHERE `national_code` IS NOT NULL;');
        }
    }

    /**
     * Drop the newly created tables
     */
    private function dropNewTables(): void
    {
        $newTables = [
            'staff_leave_policies',
            'staff_leave_policy_types',
            'staff_leave_entitlements',
            'institution_staff_leave_entitlements'
        ];

        foreach ($newTables as $table) {
            $this->execute("DROP TABLE IF EXISTS `$table`;");
        }
    }

    /**
     * @return void
     */
    private function createNewTables(): void
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `staff_leave_policies` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(50) NOT NULL,
                `name` VARCHAR(100) NOT NULL,
                `description` TEXT NULL,
                `modified_user_id` INT UNSIGNED NULL,
                `modified` DATETIME NULL,
                `created_user_id` INT UNSIGNED NOT NULL,
                `created` DATETIME NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_code_name` (`code`, `name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        // Create staff_leave_policy_types table
        $this->execute('CREATE TABLE IF NOT EXISTS `staff_leave_policy_types` (
        `id` CHAR(36) NOT NULL,
        `staff_leave_policy_id` INT UNSIGNED NOT NULL COMMENT "links to staff_leave_policies.id",
        `staff_leave_type_id` INT NOT NULL COMMENT "links to staff_leave_types.id",
        `days` INT NULL COMMENT "Days allocated (nullable)",
        `rollover` TINYINT(1) NOT NULL DEFAULT 1 COMMENT "1: Yes Can rollover unused days, 0: No",
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_policy_type` (`staff_leave_policy_id`, `staff_leave_type_id`),
        KEY `idx_staff_leave_policy_id` (`staff_leave_policy_id`),
        KEY `idx_staff_leave_type_id` (`staff_leave_type_id`),
        CONSTRAINT `fk_staff_leave_policy_types_policy_id`
            FOREIGN KEY (`staff_leave_policy_id`) REFERENCES `staff_leave_policies` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT `fk_staff_leave_policy_types_type_id`
            FOREIGN KEY (`staff_leave_type_id`) REFERENCES `staff_leave_types` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $this->execute('CREATE TABLE IF NOT EXISTS `staff_leave_entitlements` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `staff_id` INT NOT NULL COMMENT "links to staff.id",
        `staff_leave_type_id` INT NOT NULL COMMENT "links to staff_leave_types.id",
        `adjustment` INT SIGNED NOT NULL COMMENT "Leave days adjustment (positive or negative)",
        `modified_user_id` INT UNSIGNED NULL,
        `modified` DATETIME NULL,
        `created_user_id` INT UNSIGNED NOT NULL,
        `created` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_staff_id` (`staff_id`),
        KEY `idx_staff_leave_type_id` (`staff_leave_type_id`),
        CONSTRAINT `fk_staff_leave_entitlements_staff_id`
            FOREIGN KEY (`staff_id`) REFERENCES `security_users` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT `fk_staff_leave_entitlements_leave_type_id`
            FOREIGN KEY (`staff_leave_type_id`) REFERENCES `staff_leave_types` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

        $this->execute('CREATE TABLE IF NOT EXISTS `institution_staff_leave_entitlements` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `year` INT(4) NULL DEFAULT NULL COMMENT "Year",
        `staff_id` INT NOT NULL COMMENT "links to security_users.id",
        `institution_id` INT NOT NULL COMMENT "links to institutions.id",
        `institution_position_id` INT NOT NULL COMMENT "links to institution_positions.id",
        `staff_leave_policy_id` INT UNSIGNED NOT NULL COMMENT "links to leave_policies.id",
        `staff_leave_type_id` INT NOT NULL COMMENT "links to leave_types.id",
        `days_total` INT SIGNED NULL DEFAULT NULL COMMENT "Total leave days",
        `days_taken` INT SIGNED NULL DEFAULT NULL COMMENT "Leave days taken",
        `days_balance` INT SIGNED NULL DEFAULT NULL COMMENT "Remaining leave days",
        `adjustment` INT SIGNED NULL DEFAULT NULL COMMENT "Leave days adjustment (positive or negative)",
        `modified_user_id` INT UNSIGNED NULL,
        `modified` DATETIME NULL,
        `created_user_id` INT UNSIGNED NOT NULL,
        `created` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_staff_id` (`staff_id`),
        KEY `idx_institution_id` (`institution_id`),
        KEY `idx_institution_position_id` (`institution_position_id`),
        KEY `idx_leave_type_id` (`staff_leave_type_id`),
        KEY `idx_staff_leave_policy_id` (`staff_leave_policy_id`),
        CONSTRAINT `fk_inst_staff_leave_staff_id`
            FOREIGN KEY (`staff_id`) REFERENCES `security_users` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT `fk_inst_staff_leave_institution_id`
            FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT `fk_inst_staff_leave_position_id`
            FOREIGN KEY (`institution_position_id`) REFERENCES `institution_positions` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT `fk_inst_staff_leave_policy_id`
            FOREIGN KEY (`staff_leave_policy_id`) REFERENCES `staff_leave_policies` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT,
        CONSTRAINT `fk_inst_staff_leave_type_id`
            FOREIGN KEY (`staff_leave_type_id`) REFERENCES `staff_leave_types` (`id`)
            ON DELETE RESTRICT ON UPDATE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }
}
