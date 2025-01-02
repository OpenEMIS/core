<?php

use Migrations\AbstractMigration;

class POCOR8030 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8030_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_8030_security_functions` SELECT * FROM `security_functions`');
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
                      `manager_id` int(11) NOT NULL,
                      `staff_id` int(11) NOT NULL,
                      `institution_id` int(11) NOT NULL,
                      `academic_period_id` int(11) NOT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) NOT NULL,
                      `created` datetime NOT NULL,
                       PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_manager_id`
            FOREIGN KEY (`manager_id`) REFERENCES `security_users`(`id`)");
        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_staff_id`
            FOREIGN KEY (`staff_id`) REFERENCES `security_users`(`id`)");
        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_institution_id`
            FOREIGN KEY (`institution_id`) REFERENCES `institutions`(`id`)");
        $this->execute("ALTER TABLE `institution_departments`
            ADD CONSTRAINT `fk_academic_period_id`
            FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods`(`id`)");
    }

    public function down()
    {
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8030_security_functions` TO `security_functions`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
