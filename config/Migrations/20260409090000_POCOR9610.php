<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR9610 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        //POCOR-9610: start - Snapshot institutions before extending the schema for external registrations integration
        $this->backupTable('institutions', 'z_9610_institutions');
        //POCOR-9610: end

        //POCOR-9610: start - Create the registration history table for institution-level external sync records
        if (!$this->hasTable('institution_registrations')) {
            $this->execute("
                CREATE TABLE `institution_registrations` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `institution_id` INT NOT NULL,
                    `external_id` VARCHAR(50) NULL,
                    `status` ENUM('pending','active','revoked','expired') NOT NULL,
                    `approved_date` DATE NULL,
                    `valid_from` DATE NULL,
                    `valid_to` DATE NULL,
                    `decision_reference` VARCHAR(255) NULL,
                    `modified_user_id` INT NULL,
                    `modified` DATETIME NULL,
                    `created_user_id` INT NOT NULL,
                    `created` DATETIME NOT NULL,
                    KEY `idx_ir_institution_id` (`institution_id`),
                    KEY `idx_ir_external_id` (`external_id`),
                    KEY `idx_ir_modified_user_id` (`modified_user_id`),
                    KEY `idx_ir_created_user_id` (`created_user_id`),
                    CONSTRAINT `fk_ir_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`),
                    CONSTRAINT `fk_ir_modified_user_id` FOREIGN KEY (`modified_user_id`) REFERENCES `security_users`(`id`),
                    CONSTRAINT `fk_ir_created_user_id` FOREIGN KEY (`created_user_id`) REFERENCES `security_users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }
        //POCOR-9610: end

        //POCOR-9610: start - Create the accreditation table for programme-level external sync records
        if (!$this->hasTable('institution_accreditations')) {
            $this->execute("
                CREATE TABLE `institution_accreditations` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `institution_id` INT NOT NULL,
                    `programme_name` VARCHAR(255) NOT NULL,
                    `programme_code` VARCHAR(50) NULL,
                    `qualification_level` VARCHAR(100) NULL,
                    `status` ENUM('pending','active','revoked','expired') NOT NULL,
                    `valid_from` DATE NULL,
                    `valid_to` DATE NULL,
                    `external_id` VARCHAR(100) NULL,
                    `modified_user_id` INT NULL,
                    `modified` DATETIME NULL,
                    `created_user_id` INT NOT NULL,
                    `created` DATETIME NOT NULL,
                    KEY `idx_ia_institution_id` (`institution_id`),
                    KEY `idx_ia_programme_code` (`programme_code`),
                    KEY `idx_ia_external_id` (`external_id`),
                    KEY `idx_ia_modified_user_id` (`modified_user_id`),
                    KEY `idx_ia_created_user_id` (`created_user_id`),
                    CONSTRAINT `fk_ia_institution_id` FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`),
                    CONSTRAINT `fk_ia_modified_user_id` FOREIGN KEY (`modified_user_id`) REFERENCES `security_users`(`id`),
                    CONSTRAINT `fk_ia_created_user_id` FOREIGN KEY (`created_user_id`) REFERENCES `security_users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
            ");
        }
        //POCOR-9610: end

        //POCOR-9610: start - Add institution sync fields used by the external registrations integration
        if (!$this->table('institutions')->hasColumn('external_id')) {
            $this->execute("ALTER TABLE `institutions` ADD COLUMN `external_id` VARCHAR(50) NULL AFTER `code`");
        }

        if (!$this->table('institutions')->hasColumn('registration_status')) {
            $this->execute("ALTER TABLE `institutions` ADD COLUMN `registration_status` ENUM('pending','active','revoked','expired') NULL AFTER `external_id`");
        }

        if (!$this->table('institutions')->hasColumn('registration_valid_until')) {
            $this->execute("ALTER TABLE `institutions` ADD COLUMN `registration_valid_until` DATE NULL AFTER `registration_status`");
        }

        $indexExists = $this->fetchRow("SHOW INDEX FROM `institutions` WHERE Key_name = 'idx_institutions_external_id'");
        if (empty($indexExists)) {
            $this->execute("ALTER TABLE `institutions` ADD INDEX `idx_institutions_external_id` (`external_id`)");
        }
        //POCOR-9610: end

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        //POCOR-9610: start - Drop integration tables before restoring the pre-ticket institutions snapshot
        if ($this->hasTable('institution_accreditations')) {
            $this->execute('DROP TABLE `institution_accreditations`');
        }

        if ($this->hasTable('institution_registrations')) {
            $this->execute('DROP TABLE `institution_registrations`');
        }
        //POCOR-9610: end

        //POCOR-9610: start - Restore institutions exactly as captured before the schema extension
        if ($this->hasTable('z_9610_institutions')) {
            $this->execute('DROP TABLE `institutions`');
            $this->execute('RENAME TABLE `z_9610_institutions` TO `institutions`');
        }
        //POCOR-9610: end

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    //POCOR-9610: start - Reusable backup helper for destructive migration rollback safety
    private function backupTable(string $original, string $backup): void
    {
        if (!$this->hasTable($backup)) {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");
        }
    }
    //POCOR-9610: end
}
