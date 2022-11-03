<?php
use Migrations\AbstractMigration;

class POCOR6873 extends AbstractMigration
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
        // $this->execute('CREATE TABLE `zz_6873_locale_contents` LIKE `locale_contents`');
        // $this->execute('INSERT INTO `zz_6873_locale_contents` SELECT * FROM `locale_contents`');

        // $this->execute('CREATE TABLE `zz_6873_user_special_needs_assessments` LIKE `user_special_needs_assessments`');
        // $this->execute('INSERT INTO `zz_6873_user_special_needs_assessments` SELECT * FROM `user_special_needs_assessments`');

        $this->execute('CREATE TABLE `zz_6873_user_special_needs_services` LIKE `user_special_needs_services`');
        $this->execute('INSERT INTO `zz_6873_user_special_needs_services` SELECT * FROM `user_special_needs_services`');

        $this->execute('CREATE TABLE `zz_6873_user_special_needs_plans` LIKE `user_special_needs_plans`');
        $this->execute('INSERT INTO `zz_6873_user_special_needs_plans` SELECT * FROM `user_special_needs_plans`');

        $this->execute('CREATE TABLE `zz_6873_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6873_security_functions` SELECT * FROM `security_functions`');

        // /**inserting data into locale_contents table*/
        $localeContent = [
            [
                'en' => 'Diagnostics',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Diagnostics Types',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Diagnostics Degree',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ]

        ];
        $this->insert('locale_contents', $localeContent);

        $this->execute("CREATE TABLE `special_needs_plan_types` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) COLLATE utf8_general_ci NOT NULL,
            `order` int(3) NOT NULL,
            `visible` int(1) NOT NULL DEFAULT '1',
            `editable` int(1) NOT NULL DEFAULT '1',
            `default` int(1) NOT NULL DEFAULT '0',
            `international_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `national_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='This table contains the plan types for special needs'");


        // /**inserting data into special_needs_plan_types table*/
        $data = [
            [
                'name'  => 'Braille terminal',
                'order'  => '1',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Hearing aid',
                'order'  => '2',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Mobility aid',
                'order'  => '3',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Cognitive assistance',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->insert('special_needs_plan_types', $data);



        // //
        $this->execute('ALTER TABLE `user_special_needs_assessments` ADD `assessor_id` INT NULL DEFAULT NULL AFTER `security_user_id`');

        // //
        $this->execute('ALTER TABLE `user_special_needs_services` ADD `special_needs_service_classification_id` INT NOT NULL AFTER `special_needs_service_type_id`');
        $this->execute('ALTER TABLE `user_special_needs_plans` ADD `academic_period_id` INT NOT NULL AFTER `security_user_id`');


        $sql = 'INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES (NULL, "Diagnostics", "Students", "Institutions", "Students - Special Needs", "2000", "SpecialNeedsDiagnostics.index|SpecialNeedsDiagnostics.view", "SpecialNeedsDiagnostics.edit", "SpecialNeedsDiagnostics.add", "SpecialNeedsDiagnostics.remove", "SpecialNeedsDiagnostics.excel", "182", "1", NULL, "2", NOW(), "1", NOW());';
        $this->execute($sql);

        // //
        $this->execute("CREATE TABLE `user_special_needs_diagnostics` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `date` date NOT NULL,
            `file_name` varchar(250) COLLATE utf8_general_ci DEFAULT NULL,
            `file_content` longblob,
            `comment` text COLLATE utf8_general_ci,
            `special_needs_diagnostics_type_id` int(11) NOT NULL COMMENT 'links to special_needs_diagnostics_types.id',
            `special_needs_diagnostics_degree_id` int(11) NOT NULL COMMENT 'links to special_needs_diagnostics_levels.id',
            `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id',
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='This table contains diagnostics for all users'");

        // //

        $this->execute("CREATE TABLE `special_needs_diagnostics_types` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) COLLATE utf8_general_ci NOT NULL,
            `order` int(3) NOT NULL,
            `visible` int(1) NOT NULL DEFAULT '1',
            `editable` int(1) NOT NULL DEFAULT '1',
            `default` int(1) NOT NULL DEFAULT '0',
            `international_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `national_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='This table contains diagnostics types for special needs'");


        // /**inserting data into locale_contents table*/
        $dataForDisability = [
            [
                'name'  => 'Mobility and Physical Impairments',
                'order'  => '1',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Spinal Cord Disability',
                'order'  => '2',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Head Injuries - Brain Disability',
                'order'  => '3',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Vision Disability',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Hearing Disability',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Cognitive or Learning Disabilities',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Psychological Disorders',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Invisible Disabilities',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('special_needs_diagnostics_types')->insert($dataForDisability)->save(); 

        // //
        
        $this->execute("CREATE TABLE `special_needs_diagnostics_degree` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(200) COLLATE utf8_general_ci NOT NULL,
            `order` int(3) NOT NULL,
            `visible` int(1) NOT NULL DEFAULT '1',
            `editable` int(1) NOT NULL DEFAULT '1',
            `default` int(1) NOT NULL DEFAULT '0',
            `international_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `national_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='This table contains the diagnostics degree for the diagnostics types'");


        $dataForDisabilityDegree = [
            [
                'name'  => 'Upper limb(s) disability',
                'order'  => '1',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Lower limb(s) disability',
                'order'  => '2',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Manual dexterity',
                'order'  => '3',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Disability in co-ordination with different organs of the body',
                'order'  => '4',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('special_needs_diagnostics_degree')->insert($dataForDisabilityDegree)->save();
        
        $this->execute("CREATE TABLE `special_needs_service_classification` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(50) COLLATE utf8_general_ci NOT NULL,
            `order` int(3) NOT NULL,
            `visible` int(1) NOT NULL DEFAULT '1',
            `editable` int(1) NOT NULL DEFAULT '1',
            `default` int(1) NOT NULL DEFAULT '0',
            `international_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `national_code` varchar(50) COLLATE utf8_general_ci DEFAULT NULL,
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='This table is to classify the services offered for special needs'");


        $dataForServiceClassification = [
            [
                'name'  => 'Training Services',
                'order'  => '1',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'name'  => 'Medical and Dental services',
                'order'  => '2',
                'visible'  => '1',
                'editable'  => '1',
                'default'  => '0',
                'international_code'  => '',
                'national_code' => '',
                'created_user_id' => '2',
                'created' => date('Y-m-d H:i:s')
            ]
        ];

        $this->table('special_needs_service_classification')->insert($dataForServiceClassification)->save();

        // //
        $this->execute('ALTER TABLE `special_needs_diagnostics_degree` ADD `special_needs_diagnostics_types_id` INT NOT NULL AFTER `national_code`');


    }

    //rollback
    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `zz_6873_locale_contents` TO `locale_contents`'); 

        $this->execute('DROP TABLE IF EXISTS `user_special_needs_assessments`');
        $this->execute('RENAME TABLE `zz_6873_user_special_needs_assessments` TO `user_special_needs_assessments`'); 

        $this->execute('DROP TABLE IF EXISTS `user_special_needs_services`');
        $this->execute('RENAME TABLE `zz_6873_user_special_needs_services` TO `user_special_needs_services`');

        $this->execute('DROP TABLE IF EXISTS `user_special_needs_plans`');
        $this->execute('RENAME TABLE `zz_6873_user_special_needs_plans` TO `user_special_needs_plans`');

        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6873_security_functions` TO `security_functions`');
        
        //New Tables
        $this->execute('DROP TABLE IF EXISTS `special_needs_plan_types`');

        $this->execute('DROP TABLE IF EXISTS `user_special_needs_diagnostics`');

        $this->execute('DROP TABLE IF EXISTS `special_needs_diagnostics_types`');

        $this->execute('DROP TABLE IF EXISTS `special_needs_diagnostics_degree`');

        $this->execute('DROP TABLE IF EXISTS `special_needs_service_classification`');

    }
}
