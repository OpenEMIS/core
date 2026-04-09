<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

//POCOR-9610: start - Institution Registrations and Accreditations tabs
// Two new tables: institution_registrations, institution_accreditations.
// CakePHP UI: read-only for all roles (data pushed from OpenEMIS Accreditations via API).
// API v5 (Security → Roles → API tab): view for all standard roles;
//   write (add/edit/delete) only for roles with order < 5
//   (Group Administrator=1, Administrator=2, District Officer=3).
// Superrole bypasses PermissionService entirely (super_admin flag).
class POCOR9610 extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        //POCOR-9610: start - Full backups of all tables touched by this migration
        $this->backupTable('institutions',            'z_9610_institutions');
        $this->backupTable('security_functions',      'z_9610_security_functions');
        $this->backupTable('security_role_functions', 'z_9610_security_role_functions');
        //POCOR-9610: end

        //POCOR-9610: start - Create institution_registrations
        // valid_from is NOT NULL; app layer defaults it to institution.date_opened when left blank.
        if (!$this->hasTable('institution_registrations')) {
            $this->execute("
                CREATE TABLE `institution_registrations` (
                    `id`               INT AUTO_INCREMENT PRIMARY KEY,
                    `institution_id`   INT NOT NULL,
                    `valid_from`       DATE NOT NULL,
                    `valid_to`         DATE NULL,
                    `modified_user_id` INT NULL,
                    `modified`         DATETIME NULL,
                    `created_user_id`  INT NOT NULL,
                    `created`          DATETIME NOT NULL,
                    KEY `idx_ir_institution_id`   (`institution_id`),
                    KEY `idx_ir_modified_user_id` (`modified_user_id`),
                    KEY `idx_ir_created_user_id`  (`created_user_id`),
                    CONSTRAINT `fk_ir_institution_id`   FOREIGN KEY (`institution_id`)   REFERENCES `institutions`(`id`),
                    CONSTRAINT `fk_ir_modified_user_id` FOREIGN KEY (`modified_user_id`) REFERENCES `security_users`(`id`),
                    CONSTRAINT `fk_ir_created_user_id`  FOREIGN KEY (`created_user_id`)  REFERENCES `security_users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            // Table already exists — backup its data, backfill NULLs, enforce NOT NULL
            $this->backupTable('institution_registrations', 'z_9610_institution_registrations');
            $this->execute("
                UPDATE `institution_registrations` ir
                JOIN `institutions` i ON i.`id` = ir.`institution_id`
                SET ir.`valid_from` = i.`date_opened`
                WHERE ir.`valid_from` IS NULL
            ");
            $this->execute("ALTER TABLE `institution_registrations` MODIFY COLUMN `valid_from` DATE NOT NULL");
        }
        //POCOR-9610: end

        //POCOR-9610: start - Create institution_accreditations
        // education_programme_id FK; valid_from NOT NULL.
        if (!$this->hasTable('institution_accreditations')) {
            $this->execute("
                CREATE TABLE `institution_accreditations` (
                    `id`                     INT AUTO_INCREMENT PRIMARY KEY,
                    `institution_id`         INT NOT NULL,
                    `education_programme_id` INT NOT NULL,
                    `valid_from`             DATE NOT NULL,
                    `valid_to`               DATE NULL,
                    `modified_user_id`       INT NULL,
                    `modified`               DATETIME NULL,
                    `created_user_id`        INT NOT NULL,
                    `created`                DATETIME NOT NULL,
                    KEY `idx_ia_institution_id`         (`institution_id`),
                    KEY `idx_ia_education_programme_id` (`education_programme_id`),
                    KEY `idx_ia_modified_user_id`       (`modified_user_id`),
                    KEY `idx_ia_created_user_id`        (`created_user_id`),
                    CONSTRAINT `fk_ia_institution_id`         FOREIGN KEY (`institution_id`)         REFERENCES `institutions`(`id`),
                    CONSTRAINT `fk_ia_education_programme_id` FOREIGN KEY (`education_programme_id`) REFERENCES `education_programmes`(`id`),
                    CONSTRAINT `fk_ia_modified_user_id`       FOREIGN KEY (`modified_user_id`)       REFERENCES `security_users`(`id`),
                    CONSTRAINT `fk_ia_created_user_id`        FOREIGN KEY (`created_user_id`)        REFERENCES `security_users`(`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            $this->backupTable('institution_accreditations', 'z_9610_institution_accreditations');
            $this->execute("
                UPDATE `institution_accreditations` ia
                JOIN `institutions` i ON i.`id` = ia.`institution_id`
                SET ia.`valid_from` = i.`date_opened`
                WHERE ia.`valid_from` IS NULL
            ");
            $this->execute("ALTER TABLE `institution_accreditations` MODIFY COLUMN `valid_from` DATE NOT NULL");
        }
        //POCOR-9610: end

        $now = date('Y-m-d H:i:s');

        //POCOR-9610: start - Institutions-module security_functions (CakePHP sidebar tabs — view only)
        // _add/_edit/_delete are NULL because the CakePHP UI is read-only (toggle disabled in Table class).
        // API permissions are handled separately by the API-module rows below.
        $registrationsExists = $this->fetchRow(
            "SELECT id FROM `security_functions`
             WHERE `name` = 'Registrations' AND `module` = 'Institutions' AND `controller` = 'Institutions'
             LIMIT 1"
        );
        if (empty($registrationsExists)) {
            $this->execute("
                INSERT INTO `security_functions`
                    (`name`, `controller`, `module`, `category`, `parent_id`,
                     `_view`, `_add`, `_edit`, `_delete`, `_execute`,
                     `order`, `visible`, `created_user_id`, `created`)
                VALUES
                    ('Registrations', 'Institutions', 'Institutions', 'General', 8,
                     'Registrations.index|Registrations.view',
                     NULL, NULL, NULL,
                     NULL, 52, 1, 1, '$now')
            ");
            $this->execute("
                INSERT INTO `security_role_functions`
                    (`security_role_id`, `security_function_id`, `_view`, `_add`, `_edit`, `_delete`, `created_user_id`, `created`)
                SELECT r.`id`, LAST_INSERT_ID(), 1, 0, 0, 0, 1, '$now'
                FROM `security_roles` r
                WHERE r.`id` IN (1, 2, 3, 4, 10)
            ");
        }

        $accreditationsExists = $this->fetchRow(
            "SELECT id FROM `security_functions`
             WHERE `name` = 'Accreditations' AND `module` = 'Institutions' AND `controller` = 'Institutions'
             LIMIT 1"
        );
        if (empty($accreditationsExists)) {
            $this->execute("
                INSERT INTO `security_functions`
                    (`name`, `controller`, `module`, `category`, `parent_id`,
                     `_view`, `_add`, `_edit`, `_delete`, `_execute`,
                     `order`, `visible`, `created_user_id`, `created`)
                VALUES
                    ('Accreditations', 'Institutions', 'Institutions', 'General', 8,
                     'Accreditations.index|Accreditations.view',
                     NULL, NULL, NULL,
                     NULL, 53, 1, 1, '$now')
            ");
            $this->execute("
                INSERT INTO `security_role_functions`
                    (`security_role_id`, `security_function_id`, `_view`, `_add`, `_edit`, `_delete`, `created_user_id`, `created`)
                SELECT r.`id`, LAST_INSERT_ID(), 1, 0, 0, 0, 1, '$now'
                FROM `security_roles` r
                WHERE r.`id` IN (1, 2, 3, 4, 10)
            ");
        }
        //POCOR-9610: end

        //POCOR-9610: start - API-module security_functions (Security → Roles → Permissions → API tab)
        // These rows control PermissionService::checkPermission() in the Laravel API.
        // module='API', controller='API', category='API', parent_id=10000 (standard for all API rows).
        // Roles with order < 5 get full write; Principal and below get view-only.
        $apiRegistrationsExists = $this->fetchRow(
            "SELECT id FROM `security_functions`
             WHERE `name` = 'Institution Registrations' AND `module` = 'API'
             LIMIT 1"
        );
        if (empty($apiRegistrationsExists)) {
            $this->execute("
                INSERT INTO `security_functions`
                    (`name`, `controller`, `module`, `category`, `parent_id`,
                     `_view`, `_add`, `_edit`, `_delete`, `_execute`,
                     `order`, `visible`, `created_user_id`, `created`)
                VALUES
                    ('Institution Registrations', 'API', 'API', 'API', 10000,
                     'InstitutionRegistrations.view|InstitutionRegistrations.list',
                     'InstitutionRegistrations.add',
                     'InstitutionRegistrations.edit',
                     'InstitutionRegistrations.delete',
                     NULL, 1244, 1, 1, '$now')
            ");
            $this->execute("
                INSERT INTO `security_role_functions`
                    (`security_role_id`, `security_function_id`, `_view`, `_add`, `_edit`, `_delete`, `created_user_id`, `created`)
                SELECT r.`id`, LAST_INSERT_ID(),
                    1,
                    CASE WHEN r.`order` < 5 THEN 1 ELSE 0 END,
                    CASE WHEN r.`order` < 5 THEN 1 ELSE 0 END,
                    CASE WHEN r.`order` < 5 THEN 1 ELSE 0 END,
                    1, '$now'
                FROM `security_roles` r
                WHERE r.`id` IN (1, 2, 3, 4, 5, 6, 9, 10)
            ");
        }

        $apiAccreditationsExists = $this->fetchRow(
            "SELECT id FROM `security_functions`
             WHERE `name` = 'Institution Accreditations' AND `module` = 'API'
             LIMIT 1"
        );
        if (empty($apiAccreditationsExists)) {
            $this->execute("
                INSERT INTO `security_functions`
                    (`name`, `controller`, `module`, `category`, `parent_id`,
                     `_view`, `_add`, `_edit`, `_delete`, `_execute`,
                     `order`, `visible`, `created_user_id`, `created`)
                VALUES
                    ('Institution Accreditations', 'API', 'API', 'API', 10000,
                     'InstitutionAccreditations.view|InstitutionAccreditations.list',
                     'InstitutionAccreditations.add',
                     'InstitutionAccreditations.edit',
                     'InstitutionAccreditations.delete',
                     NULL, 1245, 1, 1, '$now')
            ");
            $this->execute("
                INSERT INTO `security_role_functions`
                    (`security_role_id`, `security_function_id`, `_view`, `_add`, `_edit`, `_delete`, `created_user_id`, `created`)
                SELECT r.`id`, LAST_INSERT_ID(),
                    1,
                    CASE WHEN r.`order` < 5 THEN 1 ELSE 0 END,
                    CASE WHEN r.`order` < 5 THEN 1 ELSE 0 END,
                    CASE WHEN r.`order` < 5 THEN 1 ELSE 0 END,
                    1, '$now'
                FROM `security_roles` r
                WHERE r.`id` IN (1, 2, 3, 4, 5, 6, 9, 10)
            ");
        }
        //POCOR-9610: end

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    public function down(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        //POCOR-9610: start - Restore security_role_functions from backup (full table restore)
        if ($this->hasTable('z_9610_security_role_functions')) {
            $this->execute('DROP TABLE `security_role_functions`');
            $this->execute('RENAME TABLE `z_9610_security_role_functions` TO `security_role_functions`');
        }
        //POCOR-9610: end

        //POCOR-9610: start - Restore security_functions from backup (full table restore)
        if ($this->hasTable('z_9610_security_functions')) {
            $this->execute('DROP TABLE `security_functions`');
            $this->execute('RENAME TABLE `z_9610_security_functions` TO `security_functions`');
        }
        //POCOR-9610: end

        //POCOR-9610: start - Restore institution_accreditations from backup or drop if newly created
        if ($this->hasTable('z_9610_institution_accreditations')) {
            $this->execute('DROP TABLE IF EXISTS `institution_accreditations`');
            $this->execute('RENAME TABLE `z_9610_institution_accreditations` TO `institution_accreditations`');
        } elseif ($this->hasTable('institution_accreditations')) {
            $this->execute('DROP TABLE `institution_accreditations`');
        }
        //POCOR-9610: end

        //POCOR-9610: start - Restore institution_registrations from backup or drop if newly created
        if ($this->hasTable('z_9610_institution_registrations')) {
            $this->execute('DROP TABLE IF EXISTS `institution_registrations`');
            $this->execute('RENAME TABLE `z_9610_institution_registrations` TO `institution_registrations`');
        } elseif ($this->hasTable('institution_registrations')) {
            $this->execute('DROP TABLE `institution_registrations`');
        }
        //POCOR-9610: end

        //POCOR-9610: start - Restore institutions from snapshot
        if ($this->hasTable('z_9610_institutions')) {
            $this->execute('DROP TABLE `institutions`');
            $this->execute('RENAME TABLE `z_9610_institutions` TO `institutions`');
        }
        //POCOR-9610: end

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }

    //POCOR-9610: start - Reusable backup helper (skips if backup already exists — idempotent)
    private function backupTable(string $original, string $backup): void
    {
        if (!$this->hasTable($backup)) {
            $this->execute("CREATE TABLE `$backup` LIKE `$original`");
            $this->execute("INSERT INTO `$backup` SELECT * FROM `$original`");
        }
    }
    //POCOR-9610: end
}
//POCOR-9610: end
