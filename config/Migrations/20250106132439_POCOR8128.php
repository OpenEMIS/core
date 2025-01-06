<?php

use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR8128 extends AbstractMigration
{
    public function up()
    {
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

        // Insert General Policy (GP) record
        $this->execute("INSERT INTO `staff_leave_policies` (`code`, `name`, `description`, `created_user_id`, `created`) VALUES ('GP', 'General Policies', 'General Policies', 1, NOW());");

        // Fetch the ID of the newly inserted `GP` record
        $gpPolicyId = $this->fetchRow("SELECT `id` FROM `staff_leave_policies` WHERE `code` = 'GP';")['id'];

        // Create the `staff_leave_policy_types` table
        $this->execute('
            CREATE TABLE IF NOT EXISTS `staff_leave_policy_types` (
                `id` CHAR(36) NOT NULL,
                `staff_leave_policy_id` INT UNSIGNED NOT NULL COMMENT "links to staff_leave_policies.id",
                `staff_leave_type_id` INT UNSIGNED NOT NULL COMMENT "links to staff_leave_types.id",
                `days` INT NULL COMMENT "Days allocated (nullable)",
                `rollover` TINYINT(1) NOT NULL DEFAULT 1 COMMENT "1: Yes Can rollover unused days, 0: No",
                PRIMARY KEY (`id`),
                KEY `idx_staff_leave_policy_id` (`staff_leave_policy_id`),
                KEY `idx_staff_leave_type_id` (`staff_leave_type_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        // Insert all `visible = 1` staff leave types linked to the `GP` staff leave policy
        $visibleTypes = $this->fetchAll("SELECT `id` FROM `staff_leave_types` WHERE `visible` = 1;");

        foreach ($visibleTypes as $type) {
            $uuid = Text::uuid();  // Generate UUID in PHP
            $this->execute("
                INSERT INTO `staff_leave_policy_types` (`id`, `staff_leave_policy_id`, `staff_leave_type_id`, `days`, `rollover`)
                VALUES ('{$uuid}', {$gpPolicyId}, {$type['id']}, NULL, 1);
            ");
        }
    }

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `staff_leave_policy_types`;');
        $this->execute('DROP TABLE IF EXISTS `staff_leave_policies`;');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
