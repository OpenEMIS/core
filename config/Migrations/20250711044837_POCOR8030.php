<?php

use Migrations\AbstractMigration;
use Cake\Log\Log;

class POCOR8030 extends AbstractMigration
{
    public function up(): void
    {
        // Disable Foreign Key Checks
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        Log::info('Disabled foreign key checks.');

        // 1) Backup existing tables
        $this->createBackupTables();
        Log::info('Backup tables created.');

        // 2) Add new security function
        $this->addSecurityFunction();
        Log::info('Added new security function: Departments.');

        // 3) Create institution_departments and department_staff tables
        $this->createInstitutionDepartmentsTable();
        Log::info('Created institution_departments and department_staff tables.');

        // 4) Insert configuration items
        $this->addConfigItems();
        Log::info('Inserted config_items and config_item_options.');

        // Re-enable Foreign Key Checks
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info('Re-enabled foreign key checks.');
    }

    public function down(): void
    {
        // Disable Foreign Key Checks
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        Log::info('Disabled foreign key checks.');

        // 1) Restore backup tables
        $this->restoreBackupTables();
        Log::info('Restored backup tables.');

        // 2) Drop institution_departments and department_staff tables
        $this->dropInstitutionDepartmentsTable();
        Log::info('Dropped institution_departments and department_staff tables.');

        // Re-enable Foreign Key Checks
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info('Re-enabled foreign key checks.');
    }

    private function createBackupTables(): void
    {
        $tables = ['security_functions', 'config_items', 'config_item_options'];

        foreach ($tables as $table) {
            $backup = "z_8030_{$table}";
            if (!$this->hasTable($backup)) {
                $this->execute("CREATE TABLE IF NOT EXISTS `{$backup}` LIKE `{$table}`");
                $this->execute("INSERT IGNORE INTO `{$backup}` SELECT * FROM `{$table}`");
                Log::info("Backed up table `{$table}` to `{$backup}`.");
            }
        }
    }

    private function addSecurityFunction(): void
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');
        $orderRow = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parentRow = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parentId = $parentRow[0] + 1;
        $order = $orderRow[0] + 1;

        $record = [
            [
                'name' => 'Departments',
                'controller' => 'Institutions',
                'module' => 'Institutions',
                'category' => 'Appointment',
                'parent_id' => $parentId,
                '_view' => 'Departments.index|Departments.view|InstitutionDepartments.index|InstitutionDepartments.view|DepartmentStaff.index|DepartmentStaff.view',
                '_edit' => 'InstitutionDepartments.edit|Departments.edit|DepartmentStaff.edit',
                '_add' => 'InstitutionDepartments.add|Departments.add|DepartmentStaff.add',
                '_delete' => 'InstitutionDepartments.remove|Departments.remove|DepartmentStaff.remove',
                '_execute' => null,
                'order' => $order,
                'visible' => 1,
                'description' => null,
                'modified_user_id' => null,
                'modified' => null,
                'created_user_id' => 1,
                'created' => $createdAt,
            ]
        ];

        $this->table('security_functions')->insert($record)->save();
        Log::info('Inserted security function record into security_functions.');
    }

    private function createInstitutionDepartmentsTable(): void
    {
        if (!$this->hasTable('institution_departments')) {
            $this->execute("CREATE TABLE `institution_departments` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `code` VARCHAR(50) NOT NULL,
                `institution_id` INT NOT NULL,
                `manager_id` INT DEFAULT NULL,
                `modified_user_id` INT DEFAULT NULL,
                `modified` DATETIME DEFAULT NULL,
                `created_user_id` INT NOT NULL,
                `created` DATETIME NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB");
            Log::info('Created table institution_departments.');

            $this->execute(
                "ALTER TABLE `institution_departments`
                 ADD CONSTRAINT `fk_institution_departments_institution_id`
                 FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`);"
            );
            $this->execute(
                "ALTER TABLE `institution_departments`
                 ADD CONSTRAINT `fk_institution_departments_manager_id`
                 FOREIGN KEY (`manager_id`) REFERENCES `security_users`(`id`);"
            );
            Log::info('Added foreign keys to institution_departments.');
        }

        if (!$this->hasTable('department_staff')) {
            $this->execute("CREATE TABLE `department_staff` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `institution_department_id` INT NOT NULL,
                `institution_staff_id` INT NOT NULL,
                PRIMARY KEY (`id`),
                FOREIGN KEY (`institution_department_id`) REFERENCES `institution_departments`(`id`),
                FOREIGN KEY (`institution_staff_id`) REFERENCES `institution_staff`(`id`)
            ) ENGINE=InnoDB");
            Log::info('Created table department_staff with foreign keys.');
        }
    }

    private function addConfigItems(): void
    {
        $this->execute("INSERT INTO `config_items`
            (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`)
            VALUES
            ('Assigning Staff to Multiple Departments', 'AssigningStafftoMultipleDepartments', 'Departments',
             'Assigning Staff to Multiple Departments', 'Enable', '1', '0', 1, 1, 'Dropdown', 'department_type', 1, CURRENT_DATE())");
        Log::info('Inserted config_items record.');

        $options = [
            ['department_type', 'Enable', 'Enable', 1, 1],
            ['department_type', 'Disable', 'Disable', 2, 1]
        ];
        foreach ($options as $opt) {
            [$type, $option, $value, $order, $visible] = $opt;
            $this->execute(
                "INSERT INTO `config_item_options`
                 (`option_type`, `option`, `value`, `order`, `visible`)
                 VALUES ('$type', '$option', '$value', $order, $visible)"
            );
            Log::info("Inserted config_item_option: {$option}.");
        }
    }

    private function restoreBackupTables(): void
    {
        $tables = ['security_functions', 'config_items', 'config_item_options'];

        foreach ($tables as $table) {
            $backup = "z_8030_{$table}";
            if ($this->hasTable($backup)) {
                $this->execute('SET FOREIGN_KEY_CHECKS=0;');
                $this->execute("DROP TABLE IF EXISTS `{$table}`");
                $this->execute("RENAME TABLE `{$backup}` TO `{$table}`");
                $this->execute('SET FOREIGN_KEY_CHECKS=1;');
                Log::info("Restored backup table {$backup} to {$table}.");
            }
        }
    }

    private function dropInstitutionDepartmentsTable(): void
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');

        if ($this->hasTable('department_staff')) {
            $this->execute("DROP TABLE IF EXISTS `department_staff`");
            Log::info('Dropped table department_staff and its constraints.');
        }

        if ($this->hasTable('institution_departments')) {
            $this->execute(
                "ALTER TABLE `institution_departments` DROP FOREIGN KEY `fk_institution_departments_institution_id`");
            $this->execute(
                "ALTER TABLE `institution_departments` DROP FOREIGN KEY `fk_institution_departments_manager_id`");
            $this->execute("DROP TABLE IF EXISTS `institution_departments`");
            Log::info('Dropped table institution_departments and its constraints.');
        }

        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
        Log::info('Re-enabled foreign key checks after drop.');
    }
}
