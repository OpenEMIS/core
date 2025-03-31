<?php

use Migrations\AbstractMigration;

class POCOR8030 extends AbstractMigration
{
    public function up(): void
    {
        $this->createBackupTables();
        $this->addSecurityFunction();
        $this->createInstitutionDepartmentsTable();
        $this->addConfigItems();
    }

    private function createBackupTables(): void
    {
        $tables = ['security_functions', 'config_items', 'config_item_options'];

        foreach ($tables as $table) {
            if (!$this->hasTable("z_8030_{$table}")) {
                $this->execute("CREATE TABLE IF NOT EXISTS `z_8030_{$table}` LIKE `{$table}`");
                $this->execute("INSERT IGNORE INTO `z_8030_{$table}` SELECT * FROM `{$table}`");
            }
        }
    }

    private function addSecurityFunction(): void
    {
        $createdAt = (new DateTime())->format('Y-m-d H:i:s');
        $order = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions`
                WHERE `module` = 'Institutions'
                  AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions`
                    WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $parent_id[0] + 1;
        $order = $order[0] + 1;

        $record = [
            [
                'name' => 'Departments', 'controller' => 'Institutions',
                'module' => 'Institutions', 'category' => 'Appointment',
                'parent_id' => $parent_id,
                '_view' => 'InstitutionDepartments.index|InstitutionDepartments.view|DepartmentStaff.index|DepartmentStaff.view',
                '_edit' => 'InstitutionDepartments.edit|DepartmentStaff.edit',
                '_add' => 'InstitutionDepartments.add|DepartmentStaff.add',
                '_delete' => 'InstitutionDepartments.remove|DepartmentStaff.remove',
                '_execute' => NULL,
                'order' => $order,
                'visible' => 1,
                'description' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => $createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();
    }


    private function createInstitutionDepartmentsTable(): void
    {
        if (!$this->hasTable('institution_departments')) {
            $this->execute("CREATE TABLE `institution_departments`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `code` varchar(50) NOT NULL,
                      `institution_id` int(11) NOT NULL,
                      `manager_id` int(11) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB");

            $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_institution_departments_institution_id`
            FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`)");
            $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_institution_departments_manager_id`
            FOREIGN KEY (`manager_id`) REFERENCES `security_users`(`id`)");
        }

        if (!$this->hasTable('department_staff')) {
            $this->execute("CREATE TABLE `department_staff`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `department_id` int(11) NOT NULL,
                      `staff_id` int(11) NOT NULL,
                      PRIMARY KEY (`id`),
                      CONSTRAINT `fk_department_staff_department_id`
                      FOREIGN KEY (`department_id`) REFERENCES `institution_departments`(`id`),
                      CONSTRAINT `fk_department_staff_staff_id`
                      FOREIGN KEY (`staff_id`) REFERENCES `security_users`(`id`)
                    ) ENGINE=InnoDB");
        }
    }



    private function addConfigItems(): void
    {
        $this->execute("INSERT INTO `config_items`
            (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES
            ('Assigning Staff to Multiple Departments', 'AssigningStafftoMultipleDepartments', 'Departments', 'Assigning Staff to Multiple Departments', 'Enable', '1', '0', 1, 1, 'Dropdown', 'department_type', 1, CURRENT_DATE())");

        $this->execute("INSERT INTO `config_item_options`
            (`option_type`, `option`, `value`, `order`, `visible`) VALUES
            ('department_type', 'Enable', 'Enable', 1, 1)");

        $this->execute("INSERT INTO `config_item_options`
            (`option_type`, `option`, `value`, `order`, `visible`) VALUES
            ('department_type', 'Disable', 'Disable', 2, 1)");
    }

    public function down(): void
    {
        $this->restoreBackupTables();
        $this->dropInstitutionDepartmentsTable();
    }

    private function restoreBackupTables(): void
    {
        $tables = ['security_functions', 'config_items', 'config_item_options'];

        foreach ($tables as $table) {
            if ($this->hasTable("z_8030_{$table}")) {
                $this->execute("SET FOREIGN_KEY_CHECKS=0;");
                $this->execute("DROP TABLE IF EXISTS `{$table}`");
                $this->execute("RENAME TABLE `z_8030_{$table}` TO `{$table}`");
                $this->execute("SET FOREIGN_KEY_CHECKS=1;");
            }
        }
    }

    private function dropInstitutionDepartmentsTable(): void
    {
        $this->execute("SET FOREIGN_KEY_CHECKS=0;");

        if ($this->hasTable('institution_departments')) {
            $this->execute("ALTER TABLE `institution_departments` DROP FOREIGN KEY `fk_institution_departments_institution_id`");
            $this->execute("ALTER TABLE `institution_departments` DROP FOREIGN KEY `fk_institution_departments_manager_id`");
        }

        if ($this->hasTable('department_staff')) {
            $this->execute("ALTER TABLE `department_staff` DROP FOREIGN KEY `fk_department_staff_department_id`");
            $this->execute("ALTER TABLE `department_staff` DROP FOREIGN KEY `fk_department_staff_staff_id`");
        }

        if ($this->hasTable('department_staff')) {
            $this->execute("DROP TABLE IF EXISTS `department_staff`");
        }
        if ($this->hasTable('institution_departments')) {
            $this->execute("DROP TABLE IF EXISTS `institution_departments`");
        }

        $this->execute("SET FOREIGN_KEY_CHECKS=1;");
    }

}
