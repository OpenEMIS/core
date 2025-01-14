<?php

use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR8128 extends AbstractMigration
{
    public function up()
    {
        $this->execute('START TRANSACTION;');

        try {
            // Create staff_leave_policies table
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
            $this->execute('
                CREATE TABLE IF NOT EXISTS `staff_leave_policy_types` (
                    `id` CHAR(36) NOT NULL,
                    `staff_leave_policy_id` INT UNSIGNED NOT NULL COMMENT "links to staff_leave_policies.id",
                    `staff_leave_type_id` INT UNSIGNED NOT NULL COMMENT "links to staff_leave_types.id",
                    `days` INT NULL COMMENT "Days allocated (nullable)",
                    `rollover` TINYINT(1) NOT NULL DEFAULT 1 COMMENT "1: Yes Can rollover unused days, 0: No",
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uq_policy_type` (`staff_leave_policy_id`, `staff_leave_type_id`),
                    KEY `idx_staff_leave_policy_id` (`staff_leave_policy_id`),
                    KEY `idx_staff_leave_type_id` (`staff_leave_type_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ');

            $this->execute('CREATE TABLE IF NOT EXISTS `staff_leave_entitlements` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `staff_id` INT UNSIGNED NOT NULL COMMENT "links to staff.id",
            `leave_type_id` INT UNSIGNED NOT NULL COMMENT "links to leave_types.id",
            `adjustment` INT SIGNED NOT NULL COMMENT "Leave days adjustment (positive or negative)",
            `modified_user_id` INT UNSIGNED NULL,
            `modified` DATETIME NULL,
            `created_user_id` INT UNSIGNED NOT NULL,
            `created` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_staff_id` (`staff_id`),
            KEY `idx_staff_leave_type_id` (`leave_type_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');

            // Create general leave policy and add national codes
            $this->createGeneralLeavePolicy();
            $this->changeStaffPositionTitles();
            $this->addNationalCodes();

            $this->execute('COMMIT;');

        } catch (\Exception $e) {
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
            $this->execute("INSERT INTO `staff_leave_policies` (`code`, `name`, `description`, `created_user_id`, `created`) VALUES ('GP', 'General Policies', 'General Policies', 1, NOW());");
        }

        // Fetch the ID of the `GP` record
        $gpPolicyId = $this->fetchRow("SELECT `id` FROM `staff_leave_policies` WHERE `code` = 'GP';")['id'];

        // Insert all staff leave types linked to the `GP` staff leave policy
        $leaveTypes = $this->fetchAll("SELECT `id`, `name` FROM `staff_leave_types`;");

        foreach ($leaveTypes as $type) {
            $uuid = Text::uuid();  // Generate UUID in PHP
            $this->execute("
                INSERT INTO `staff_leave_policy_types` (`id`, `staff_leave_policy_id`, `staff_leave_type_id`, `days`, `rollover`)
                VALUES ('{$uuid}', {$gpPolicyId}, {$type['id']}, NULL, 1)
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
        // Backup table
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8128_staff_position_titles` LIKE `staff_position_titles`;');
        $this->execute('INSERT IGNORE INTO `z_8128_staff_position_titles` SELECT * FROM `staff_position_titles`;');

        // Add `staff_leave_policy_id` column
        $this->execute('
            ALTER TABLE `staff_position_titles`
            ADD COLUMN `staff_leave_policy_id` INT UNSIGNED NULL DEFAULT NULL COMMENT "links to staff_leave_policies.id"
            AFTER `security_role_id`;
        ');

        // Update NULL `staff_leave_policy_id` to `GP` policy ID
        $this->execute('
            UPDATE `staff_position_titles`
            SET `staff_leave_policy_id` = (
                SELECT `id` FROM `staff_leave_policies` WHERE `code` = "GP" LIMIT 1
            )
            WHERE `staff_leave_policy_id` IS NULL;
        ');

        // Set column as NOT NULL and add foreign key
        $this->execute('
            ALTER TABLE `staff_position_titles`
            MODIFY `staff_leave_policy_id` INT UNSIGNED NOT NULL,
            ADD CONSTRAINT `staff_posit_title_fk_leave_policy_id`
            FOREIGN KEY (`staff_leave_policy_id`) REFERENCES `staff_leave_policies` (`id`)
            ON DELETE RESTRICT;
        ');
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

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        // Rename rollback table back to original `staff_leave_types`
        $this->execute('DROP TABLE IF EXISTS `staff_position_titles`;');
        $this->execute('RENAME TABLE `z_8128_staff_position_titles` TO `staff_position_titles`;');

        $this->execute('DROP TABLE IF EXISTS `staff_leave_types`;');
        $this->execute('RENAME TABLE `z_8128_staff_leave_types` TO `staff_leave_types`;');

        $this->execute('DROP TABLE IF EXISTS `staff_leave_entitlements`;');
        $this->execute('DROP TABLE IF EXISTS `staff_leave_policy_types`;');
        $this->execute('DROP TABLE IF EXISTS `staff_leave_policies`;');

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
