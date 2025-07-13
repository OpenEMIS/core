<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

class POCOR8689 extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     * @return void
     */
    public function up(): void
    {
        $this->execute('CREATE TABLE `zz_8689_config_items` LIKE `config_items`');
        $this->execute('INSERT INTO `zz_8689_config_items` SELECT * FROM `config_items`');

        $this->execute('CREATE TABLE `zz_8689_config_item_options` LIKE `config_item_options`');
        $this->execute('INSERT INTO `zz_8689_config_item_options` SELECT * FROM `config_item_options`');

        
        $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
        ('Automated Student Enrollment', 'student_automated_enrollment', 'Student Settings', 'Automated Student Enrollment', '0', '', '0', '1', '1', 'Dropdown', 'graduate_type', '2', CURRENT_TIMESTAMP, '1', CURRENT_TIMESTAMP)"); //changed to graduate_type

        $this->execute("INSERT INTO `config_items` (`name`, `code`, `type`, `label`, `value`, `value_selection`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
        ('Default Institutions for Automated Student Enrolment', 'default_automated_student_enrollment', 'Default Institutions for Automated Student Enrolment', 'Default Institutions for Automated Student Enrolment Enabled', '0', '', '0', '1', '1', '', '', NULL, NULL, '1',
        CURRENT_TIMESTAMP)");

        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('graduate_type', 'Student Address Area', '0', '1', '1')");
        $this->execute("INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('graduate_type', 'Feeder Institution', '1', '2', '1')");

        $this->execute("CREATE TABLE `area_programme_institutions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `academic_period_id` int(11) NOT NULL,
        `institution_id` int(11) NOT NULL,
        `education_programme_id` int(11) NOT NULL,
        `modified_by` varchar(255) DEFAULT NULL,
        `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        `created_by` varchar(255) DEFAULT NULL,
        `created` timestamp NOT NULL DEFAULT current_timestamp(),
         PRIMARY KEY (`id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=25 DEFAULT CHARSET=utf8mb4");

        $this->execute("CREATE TABLE `area_programme_institution_areas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `area_programme_institution_id` int(11) NOT NULL,
        `area_administrative_id` int(11) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `area_programme_institution_id` (`area_programme_institution_id`),
        CONSTRAINT `area_programme_institution_areas_ibfk_1` FOREIGN KEY (`area_programme_institution_id`) REFERENCES `area_programme_institutions` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4");
    }

    public function down(): void
    {
        $this->execute('DROP TABLE IF EXISTS `config_items`');
        $this->execute('RENAME TABLE `zz_8689_config_items` TO `config_items`');

        $this->execute('DROP TABLE IF EXISTS `config_item_options`');
        $this->execute('RENAME TABLE `zz_8689_config_item_options` TO `config_item_options`');

        $this->execute('DROP TABLE IF EXISTS `area_programme_institutions`');
        $this->execute('DROP TABLE IF EXISTS `area_programme_institution_areas`');
    }
}