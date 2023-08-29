<?php

use Migrations\AbstractMigration;

class POCOR7613 extends AbstractMigration
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

        //cases_types
        $this->execute('CREATE TABLE `case_types` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `order` int(3) DEFAULT NULL,
            `visible` int(1) DEFAULT 1,
			`editable` int(1) DEFAULT 1,
			`default` int(1) DEFAULT 0,
			`international_code` varchar(50) DEFAULT NULL,
			`national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $caseTypesData = [
            [
                'name' => 'Institution',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Staff',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Students',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('case_types', $caseTypesData);

        //case_priorities
        $this->execute('CREATE TABLE `case_priorities` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) NOT NULL,
            `order` int(3) DEFAULT NULL,
            `visible` int(1) DEFAULT 1,
			`editable` int(1) DEFAULT 1,
			`default` int(1) DEFAULT 0,
			`international_code` varchar(50) DEFAULT NULL,
			`national_code` varchar(50) DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        $casePrioritiesData = [
            [
                'name' => 'High',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Medium',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name' => 'Low',
                'order' => 1,
                'visible' => 1,
                'editable' => 1,
                'default' => 1,
                'international_code' => NULL,
                'national_code' => NULL,
                'modified_user_id' => NULL,
                'modified' => NULL,
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]
        ];
        $this->insert('case_priorities', $casePrioritiesData);

        //field options
        $this->execute('CREATE TABLE `zz_7613_field_options` LIKE `field_options`');
        $this->execute('INSERT INTO `zz_7613_field_options` SELECT * FROM `field_options`');
        $order = $this->fetchRow("SELECT `order` FROM `field_options` ORDER BY `id` DESC LIMIT 1");

        $data = [
            [
                'name' => 'Case Types',
                'category' => 'Cases',
                'table_name' => 'case_types',
                'order' => $order[0] + 1,
                'modified_by' => NULL,
                'modified' => NULL,
                'created_by' => '1',
                'created' => date('Y-m-d H:i:s'),
            ],
            [
                'name' => 'Case Priorities',
                'category' => 'Cases',
                'table_name' => 'case_priorities',
                'order' => $order[0] + 2,
                'modified_by' => NULL,
                'modified' => NULL,
                'created_by' => '1',
                'created' => date('Y-m-d H:i:s'),
            ]
        ];
        $this->insert('field_options', $data);
        //institution_cases
        $this->execute('CREATE TABLE `zz_7613_institution_cases` LIKE `institution_cases`');
        $this->execute('INSERT INTO `zz_7613_institution_cases` SELECT * FROM `institution_cases`');
        $this->execute('ALTER TABLE `institution_cases` ADD COLUMN case_type_id INT(11) NOT NULL after institution_id');
        $this->execute('ALTER TABLE `institution_cases` ADD COLUMN case_priority_id INT(11) NOT NULL after case_type_id');


        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('ALTER TABLE `institution_cases` ADD FOREIGN KEY (`case_type_id`) REFERENCES `case_types` (`id`)');
        $this->execute('ALTER TABLE `institution_cases` ADD FOREIGN KEY (`case_priority_id`) REFERENCES `case_priorities` (`id`)');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');

        //institution_case_comments
        $this->execute('CREATE TABLE `institution_case_comments` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `case_id`  int(11) NOT NULL ,
            `comment`  text NOT NULL,
            `modified_user_id` int(11) NULL,
            `modified` datetime DEFAULT NULL ,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`),
             FOREIGN KEY (`case_id`) REFERENCES `institution_cases`(`id`) 
             ) ENGINE=InnoDB DEFAULT CHARSET=latin1');

        //security_function_table
        $this->execute('CREATE TABLE `zz_7316_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_7316_security_functions` SELECT * FROM `security_functions`');
        $this->execute('UPDATE `security_functions` SET `_delete` = NULL,`_edit` = NULL,`_execute` = NULL
                        WHERE `security_functions`.`name` = "Cases" and `security_functions`.`controller`="Profiles"
                        and `security_functions`.`module`="Personal" and `security_functions`.`category`="Cases"');

    }
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `institution_case_comments`');
        $this->execute('DROP TABLE IF EXISTS `institution_cases`');
        $this->execute('RENAME TABLE `zz_7613_institution_cases` TO `field_options`');
        $this->execute('DROP TABLE IF EXISTS `field_options`');
        $this->execute('RENAME TABLE `zz_7613_field_options` TO `field_options`');
        $this->execute('DROP TABLE IF EXISTS `case_types`');
        $this->execute('DROP TABLE IF EXISTS `case_priorities`');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_7316_security_functions` TO `security_functions`');
    }
}

