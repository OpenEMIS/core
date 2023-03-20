<?php
use Migrations\AbstractMigration;
use Cake\Utility\Text;

class POCOR6673 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     * @return void
     */

    public function up()
    {
        // Backup locale_contents table
        $this->execute('CREATE TABLE `z_6673_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6673_locale_contents` SELECT * FROM `locale_contents`');

        //backup
        $this->execute('CREATE TABLE `z_6673_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `z_6673_security_functions` SELECT * FROM `security_functions`'); 

        // security_functions Set Permission
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 139');
        //insert data in security function
        $record = [
            [
                'name' => 'Curriculars', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Academic', 'parent_id' => 8,'_view' => 'Curriculars.index|Curriculars.view', '_edit' => 'Curriculars.edit', '_add' => 'Curriculars.add', '_delete' => 'Curriculars.remove', '_execute' => NULL, 'order' => 140, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

        // security_functions for student curricular
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 435');
        //insert 
        $record = [
            [
                'name' => 'Curriculars', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Students - Academic', 'parent_id' => 2000,'_view' => 'Curriculars.index|Curriculars.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 436, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

        // security_functions for staff curricular
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 237');
        //insert 
        $record = [
            [
                'name' => 'Curriculars', 'controller' => 'Staff', 'module' => 'Institutions', 'category' => 'Staff - Career', 'parent_id' => 3000,'_view' => 'Curriculars.index|Curriculars.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' =>238, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

        // Remove permission in ExtraCurricular in staff
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 238');
        //insert 
        $record = [
            [
                'name' => 'Extracurriculars', 'controller' => 'Staff', 'module' => 'Institutions', 'category' => 'Staff - Career', 'parent_id' => 3000,'_view' => 'Extracurriculars.index|Extracurriculars.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 239, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

        // Remove permission in ExtraCurricular in student
        $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 436');
        //insert 
        $record = [
            [
                'name' => 'Extracurriculars', 'controller' => 'Institutions', 'module' => 'Institutions', 'category' => 'Students - Academic', 'parent_id' => 2000,'_view' => 'Extracurriculars.index|Extracurriculars.view', '_edit' => NULL, '_add' => NULL, '_delete' => NULL, '_execute' => NULL, 'order' => 436, 'visible' => 1, 'description' => NULL, 'modified_user_id' => NULL, 'modified' => NULL, 'created_user_id' => 1, 'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('security_functions', $record);

        //Curricular data
        $localeContent = [

            [
                'en' => 'Curricular Position',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Curricular Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
            
        ];
        $this->insert('locale_contents', $localeContent);

        // create curricular table
       $this->execute('CREATE TABLE `curricular_positions` (
                      `id` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `order` int(3) DEFAULT NULL,
                      `visible` int(1) DEFAULT NULL,
                      `default` int(1) NOT NULL DEFAULT 0,
                      `international_code` varchar(10) DEFAULT NULL,
                      `national_code` varchar(10) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $this->execute('INSERT INTO `curricular_positions` SELECT * FROM `curricular_positions`');
        $curricular_positions = [
            [
                'id' => 1,
                'name' => 'Leader',
                'order' => 1,
                'visible' => 1,
                'default' => 0,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'name' => 'Member',
                'order' => 1,
                'visible' => 1,
                'default' => 0,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('curricular_positions', $curricular_positions);
        $this->execute("ALTER TABLE `curricular_positions` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `curricular_positions` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");

        // create curricular types
       $this->execute('CREATE TABLE `curricular_types` (
                      `id` int(11) NOT NULL,
                      `name` varchar(100) NOT NULL,
                      `order` int(3) DEFAULT NULL,
                      `visible` int(1) DEFAULT NULL,
                      `default` int(1) NOT NULL DEFAULT 0,
                      `category` int(1) NOT NULL DEFAULT 1,
                      `international_code` varchar(10) DEFAULT NULL,
                      `national_code` varchar(10) DEFAULT NULL,
                      `modified_user_id` int(11) DEFAULT NULL,
                      `modified` datetime DEFAULT NULL,
                      `created_user_id` int(11) DEFAULT NULL,
                      `created` datetime DEFAULT NULL
                    ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $curricular_types = [
            [
                'id' => 1,
                'name' => 'Academic',
                'order' => 1,
                'visible' => 1,
                'default' => 0,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => date('Y-m-d H:i:s'),
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('curricular_types', $curricular_types);
        $this->execute("ALTER TABLE `curricular_types` ADD PRIMARY KEY (`id`)");
        $this->execute("ALTER TABLE `curricular_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4");

        $this->execute('CREATE TABLE IF NOT EXISTS `institution_curriculars` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `category` int(1) NOT NULL DEFAULT 1 COMMENT "1-curricular/0-extracurricular",
            `curricular_type_id` int(11) NOT NULL COMMENT "link to curricular_types",
            `institution_id` int(11),
            `academic_period_id` int(11),
            `total_male_students` int(11) DEFAULT 0,
            `total_female_students` int(11) DEFAULT 0,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`)
          )ENGINE=InnoDB DEFAULT CHARSET=utf8'
          );

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_curricular_students` (
            `id` varchar(255) NOT NULL,
            `student_id` int(11),
            `institution_curricular_id` int(11) NOT NULL,
            `start_date` date NOT NULL,
            `end_date` date NOT NULL,
            `hours` int(11) DEFAULT NULL,
            `points` decimal DEFAULT NULL,
            `location` varchar(255) DEFAULT NULL,
            `curricular_position_id`int(11) DEFAULT NULL,
            `comments` varchar(255) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `security_users` (`id`)
          )ENGINE=InnoDB DEFAULT CHARSET=utf8"
          );

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_curricular_staff` (
            `id` varchar(255) NOT NULL,
            `staff_id` int(11),
            `institution_curricular_id` int(11),
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            FOREIGN KEY (`staff_id`) REFERENCES `security_users` (`id`)
          )ENGINE=InnoDB DEFAULT CHARSET=utf8"
          );
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_6673_locale_contents` TO `locale_contents`');
        $this->execute('RENAME TABLE `z_6673_security_functions` TO `security_functions`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 456');  
    }
}
