<?php
use Migrations\AbstractMigration;

class POCOR5349 extends AbstractMigration
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
        // sql to create institute staff duties
        $this->execute("CREATE TABLE `institution_staff_duties` (
            `id` int(11) NOT NULL,
            `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
            `institution_id` int(11) NOT NULL COMMENT 'links to instututions.id',
            `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
            `staff_duties_id` int(11) NOT NULL COMMENT 'links to staff_duties.id',
            `comment` text COLLATE utf8mb4_unicode_ci,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the duties records for staff'");

            $this->execute('ALTER TABLE `institution_staff_duties`
            ADD PRIMARY KEY (`id`),
            ADD KEY `staff_id` (`staff_id`),
            ADD KEY `institution_id` (`institution_id`),
            ADD KEY `academic_period_id` (`academic_period_id`),
            ADD KEY `staff_duties_id` (`staff_duties_id`)');
            $this->execute('ALTER TABLE `institution_staff_duties`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT');

            // Sql to create staff duties table
            $this->execute("CREATE TABLE `staff_duties` (
            `id` int(11) NOT NULL,
            `name` varchar(50) NOT NULL,
            `order` int(3) NOT NULL,
            `visible` int(1) NOT NULL DEFAULT 1,
            `editable` int(1) NOT NULL DEFAULT 1,
            `default` int(1) NOT NULL DEFAULT 0,
            `international_code` varchar(50) DEFAULT NULL,
            `national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains the different types of a staff position'");

            $this->execute('ALTER TABLE `staff_duties` ADD PRIMARY KEY (`id`)');
            $this->execute('ALTER TABLE `staff_duties`
                MODIFY `id` int(11) NOT NULL AUTO_INCREMENT');
            $this->execute("INSERT INTO `staff_duties` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
                (1, 'EMIS Coordinator', 1, 1, 1, 1, '', '', 1, now(), 1, now()),
                (2, 'SEN Coordinator', 2, 1, 1, 0, '', '', 1, now(), 1, now()),
                (3, 'SEN Ambassador', 3, 1, 1, 0, '', '', 1, now(), 1, now()),
                (4, 'Resource Teacher', 4, 1, 1, 0, '', '', 1, now(), 1, now())");

             // code for security function staff carrer
             $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 63');   
             $this->insert('security_functions', [
            'name' => 'Duties',
            'controller' => 'Institutions',
            'module' => 'Institutions',
            'category' => 'Staff',
            'parent_id' => 1000,
            '_view' => 'index|view',
            '_add' => 'add',
            '_execute' => '',
            'order' => 64,
            'visible' => 1,
            'description' => null,
            'created_user_id' => 1,
            'created' => date('Y-m-d H:i:s')
        ]);

            // code for security function for institute principal carrer
                $this->execute('UPDATE security_functions SET `order` = `order` + 1 WHERE `order` > 186');   
                $this->insert('security_functions', [
                'name' => 'Duties',
                'controller' => 'Staff',
                'module' => 'Institutions',
                'category' => 'Staff - Career',
                'parent_id' => 3000,
                '_view' => 'Duties.index|Duties.view',
                '_add' => 'add',
                '_execute' => '',
                'order' => 187,
                'visible' => 1,
                'description' => null,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]);

        $localeContent = [
            [
                'en' => 'Duties',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Appointments',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Duty Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Duty Date',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        
        $this->insert('locale_contents', $localeContent);

    }

    // rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_staff_duties`');
        $this->execute('DROP TABLE IF EXISTS `staff_duties`');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 63');
        $this->execute('UPDATE security_functions SET `order` = `order` - 1 WHERE `order` > 186'); 
        $this->execute('DELETE FROM security_functions WHERE name = "Duties"');
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Duties'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Appointments'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Duty Type'");
        $this->execute("DELETE FROM `locale_contents` WHERE `en` = 'Duty Date'");
    }
}
