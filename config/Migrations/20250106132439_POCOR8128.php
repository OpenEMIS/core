<?php
use Migrations\AbstractMigration;

class POCOR8128 extends AbstractMigration
{
    public function up()
    {
        // Create leave_policies table
        $this->execute('CREATE TABLE IF NOT EXISTS `leave_policies` (
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

//        // Create leave_policy_types table
//        $this->execute('CREATE TABLE IF NOT EXISTS `leave_policy_types` (
//            `id` CHAR(36) NOT NULL,
//            `leave_policy_id` INT UNSIGNED NOT NULL COMMENT "links to leave_policies.id",
//            `leave_type_id` INT UNSIGNED NOT NULL COMMENT "links to leave_types.id",
//            PRIMARY KEY (`id`),
//            KEY `idx_leave_policy_id` (`leave_policy_id`),
//            KEY `idx_leave_type_id` (`leave_type_id`)
//        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
//
//        // Create leave_entitlement table
//        $this->execute('CREATE TABLE IF NOT EXISTS `leave_entitlement` (
//            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
//            `academic_period_id` INT NOT NULL COMMENT "links to academic_periods.id",
//            `staff_id` INT UNSIGNED NOT NULL COMMENT "links to staff.id",
//            `leave_type_id` INT UNSIGNED NOT NULL COMMENT "links to leave_types.id",
//            `adjustment` DECIMAL(5,2) NOT NULL COMMENT "Leave days adjustment (positive or negative)",
//            `modified_user_id` INT UNSIGNED NULL,
//            `modified` DATETIME NULL,
//            `created_user_id` INT UNSIGNED NOT NULL,
//            `created` DATETIME NOT NULL,
//            PRIMARY KEY (`id`),
//            KEY `idx_staff_id` (`staff_id`),
//            KEY `idx_leave_type_id` (`leave_type_id`),
//            KEY `idx_academic_period_id` (`academic_period_id`)
//        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
//
//        // Create staff_leave_entitlements table
//        $this->execute('CREATE TABLE IF NOT EXISTS `staff_leave_entitlements` (
//            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
//            `academic_period_id` INT NOT NULL COMMENT "links to academic_periods.id",
//            `staff_id` INT UNSIGNED NOT NULL COMMENT "links to staff.id",
//            `leave_type_id` INT UNSIGNED NOT NULL COMMENT "links to leave_types.id",
//            `days_total` DECIMAL(5,2) NOT NULL COMMENT "Total leave days",
//            `days_taken` DECIMAL(5,2) NOT NULL COMMENT "Leave days taken",
//            `days_balance` DECIMAL(5,2) NOT NULL COMMENT "Remaining leave days",
//            `modified_user_id` INT UNSIGNED NULL,
//            `modified` DATETIME NULL,
//            `created_user_id` INT UNSIGNED NOT NULL,
//            `created` DATETIME NOT NULL,
//            PRIMARY KEY (`id`),
//            KEY `idx_staff_id` (`staff_id`),
//            KEY `idx_leave_type_id` (`leave_type_id`),
//            KEY `idx_academic_period_id` (`academic_period_id`)
//        ) ENGINE=InnoDB DEFAULT CHARSET=utf8;');
    }

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
//        $this->execute('DROP TABLE IF EXISTS `staff_leave_entitlements`');
//        $this->execute('DROP TABLE IF EXISTS `leave_entitlement`');
//        $this->execute('DROP TABLE IF EXISTS `leave_policy_types`');
        $this->execute('DROP TABLE IF EXISTS `leave_policies`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
