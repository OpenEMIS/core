<?php
use Migrations\AbstractMigration;

class POCOR6822 extends AbstractMigration
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
        // Backup table
        $this->execute('CREATE TABLE `zz_6822_security_functions` LIKE `security_functions`');
        $this->execute('INSERT INTO `zz_6822_security_functions` SELECT * FROM `security_functions`');

        // security_functions
        $row = $this->fetchRow('SELECT `order` FROM `security_functions` WHERE `name` = "Staff" AND `controller` = "ProfileTemplates" AND `module` = "Administration"');
        $order = $row['order'];
        $this->execute('UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= ' . $order);
        $this->execute('INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created` ) VALUES (`Classes`, `ProfileTemplates`, `Administration`, `Profiles`, 5000, `Classes.index|Classes.view|ClassesProfiles.view`, `Classes.edit`, `Classes.add`, `Classes.remove`, `ClassesProfiles.generate|ClassesProfiles.downloadExcel|ClassesProfiles.publish|ClassesProfiles.unpublish|ClassesProfiles.email|ClassesProfiles.downloadAll|ClassesProfiles.generateAll|ClassesProfiles.publishAll|ClassesProfiles.unpublishAll`, ' . $order . ', 1, `1`, date(`Y-m-d H:i:s`))');

        // class_profile_templates
        $this->execute(
          'CREATE TABLE IF NOT EXISTS `class_profile_templates` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `code` varchar(50) NOT NULL,
            `name` varchar(150) NOT NULL,
            `description` text DEFAULT NULL,
            `generate_start_date` datetime DEFAULT NULL,
            `generate_end_date` datetime DEFAULT NULL,
            `excel_template_name` varchar(250) DEFAULT NULL,
            `excel_template` blob DEFAULT NULL,
            `academic_period_id` int(11) DEFAULT NULL COMMENT "links to academic_periods.id",
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`id`),
             FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods` (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8'
     );

        // class_profile_processes
        $this->execute(
          'CREATE TABLE IF NOT EXISTS `class_profile_processes` (
            `class_profile_template_id` int(11) NOT NULL COMMENT "links to class_profile_templates.id",
            `status` int(11) NOT NULL COMMENT "1 => New 2 => Running 3 => Completed -1 => Error",
            `institution_class_id` int(11) NOT NULL COMMENT "links to institution_classes.id",
            `institution_id` int(11) NOT NULL COMMENT "links to institutions.id",
            `academic_period_id` int(11) NOT NULL COMMENT "links to academic_periods.id",
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`class_profile_template_id`, `institution_id`),
             FOREIGN KEY (`class_profile_template_id`) REFERENCES `class_profile_templates` (`id`),
             FOREIGN KEY (`institution_class_id`) REFERENCES `institution_classes` (`id`),
             FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`),
             FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods` (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8'
     );

        // class_profiles
        $this->execute(
          'CREATE TABLE IF NOT EXISTS `class_profiles` (
            `id` varchar(50) NOT NULL,
            `status` int(11) NOT NULL COMMENT "1 => New 2 => Running 3 => Completed -1 => Error",
            `file_name` varchar(250) DEFAULT NULL,
            `file_content` longblob DEFAULT NULL,
            `file_content_pdf` longblob DEFAULT NULL,
            `started_on` datetime DEFAULT NULL,
            `completed_on` datetime DEFAULT NULL,
            `class_profile_template_id` int(11) NOT NULL COMMENT "links to class_profile_templates.id",
            `institution_class_id` int(11) NOT NULL COMMENT "links to institution_classes.id",
            `institution_id` int(11) NOT NULL COMMENT "links to institutions.id",
            `academic_period_id` int(11) NOT NULL COMMENT "links to academic_periods.id",
            `modified_user_id` int(11) DEFAULT NULL,
            `modified` datetime DEFAULT NULL,
            `created_user_id` int(11) NOT NULL,
            `created` datetime NOT NULL,
             PRIMARY KEY (`class_profile_template_id`, `institution_id`, `academic_period_id`, `institution_class_id`),
             FOREIGN KEY (`class_profile_template_id`) REFERENCES `class_profile_templates` (`id`),
             FOREIGN KEY (`institution_class_id`) REFERENCES `institution_classes` (`id`),
             FOREIGN KEY (`institution_id`) REFERENCES `institutions` (`id`),
             FOREIGN KEY (`academic_period_id`) REFERENCES `academic_periods` (`id`),
             INDEX `id` (`id`)
          )  ENGINE=InnoDB DEFAULT CHARSET=utf8'
     );
     
    // rollback
    public function down()
    {
        // rollback of security_functions
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `zz_6822_security_functions` TO `security_functions`');
        
        //rollback of class_profile_templates,class_profile_processes,class_profiles
        $this->execute('DROP TABLE IF EXISTS `class_profile_templates`');
        $this->execute('DROP TABLE IF EXISTS `class_profile_processes`');
        $this->execute('DROP TABLE IF EXISTS `class_profiles`');
    }
}
