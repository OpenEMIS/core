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
        $this->execute('CREATE TABLE `z_6630_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_6630_locale_contents` SELECT * FROM `locale_contents`');
        // End
        $localeContent = [

            [
                'en' => 'Curricular Position',
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
        $this->execute("ALTER TABLE `curricular_types` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;");

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_curriculars` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `category` int(1) NOT NULL DEFAULT 1,Comments '1,curricular, 0,extracurricular'
            `type` int(1) NOT NULL DEFAULT 1,
            `institution_id` int(11),
            `total_male_students` int(11),
            `total_female_students` int(11),
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL
            PRIMARY KEY (`id`),
            FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`),
          )ENGINE=InnoDB DEFAULT CHARSET=utf8"
          );

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_curricular_students` (
            `id` varchar(255) NOT NULL,
            `student_id` int(11),
            `institution_curricular_id` int(11),
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL
            PRIMARY KEY (`id`),
            FOREIGN KEY (`student_id`) REFERENCES `security_users` (`id`),
            FOREIGN KEY (`institution_curricular_id`) REFERENCES `institution_curricular` (`id`),
          )ENGINE=InnoDB DEFAULT CHARSET=utf8"
          );

        $this->execute("CREATE TABLE IF NOT EXISTS `institution_curricular_staff` (
            `id` int(11) NOT NULL,
            `staff_id` int(11),
            `institution_curricular_id` int(11),
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) DEFAULT NULL,
            `created` datetime DEFAULT NULL
            PRIMARY KEY (`id`),
            FOREIGN KEY (`staff_id`) REFERENCES `security_users` (`id`),
            FOREIGN KEY (`institution_curricular_id`) REFERENCES `institution_curricular` (`id`),
          )ENGINE=InnoDB DEFAULT CHARSET=utf8"
          );
    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_6630_locale_contents` TO `locale_contents`');
    }
}
