<?php

use Migrations\AbstractMigration;

class POCOR8030 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8030_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_8030_security_functions` SELECT * FROM `security_functions`');
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8030_config_items` LIKE `config_items`');
        $this->execute('INSERT IGNORE INTO `z_8030_config_items` SELECT * FROM `config_items`');
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8030_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT IGNORE INTO `z_8030_config_item_options` SELECT * FROM `config_item_options`');
        //  add permission in security function

        $createdAt = (new DateTime())->format('Y-m-d H:i:s');
        $order = $this->fetchRow("SELECT MAX(`order`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $this->fetchRow("SELECT MAX(`parent_id`) FROM `security_functions` WHERE `module` = 'Institutions' AND `category` = 'Report Cards'");
        $parent_id = $parent_id[0] + 1;
        $order = $order[0] + 1;

        $record = [
            [
                'name' => 'Departments', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Appointment', 'parent_id' => $parent_id,'_view' => 'Departments.add|Departments.view', '_edit' => 'Departments.edit', '_add' => 'Departments.add', '_delete' => NULL, '_execute' => NULL, 'order' => $order, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => $createdAt,
            ]
        ];
        $this->table('security_functions')->insert($record)->save();

        $this->execute("CREATE TABLE `institution_departments`
                      (`id` int(11) NOT NULL AUTO_INCREMENT,
                      `name` varchar(100) NOT NULL,
                      `code` varchar(50) NOT NULL,
                      `institution_id` int(11) NOT NULL,
                      `manager_id` int(11) NOT NULL,
                      `staff_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_institution_id`
            FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`)");
        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_manager_id`
            FOREIGN KEY (`manager_id`) REFERENCES `security_users`(`id`)");
        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_staff_id`
            FOREIGN KEY (`staff_id`) REFERENCES `security_users`(`id`)");

        // Assigning Staff to Multiple Departments system configuration
        $this->execute('INSERT INTO `config_items` 
            (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `created_user_id`, `created`) VALUES 
            ("Assigning Staff to Multiple Departments", "AssigningStafftoMultipleDepartments", "Departments", "Assigning Staff to Multiple Departments", "Enable", "1", "0", 1, 1, "Dropdown", "department_type", 1, CURRENT_DATE())');

        $this->execute("INSERT INTO `config_item_options` 
            (`option_type`, `option`, `value`, `order`, `visible`) VALUES 
            ('department_type', 'Enable', 'Enable', 1, 1)"
        );

        $this->execute("INSERT INTO `config_item_options` 
            (`option_type`, `option`, `value`, `order`, `visible`) VALUES 
            ('department_type', 'Disable', 'Disable', 2, 1)"
        );

    }

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8030_security_functions` TO `security_functions`');
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `z_8030_config_items` TO `config_items`');
        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `z_8030_config_item_options` TO `config_item_options`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
