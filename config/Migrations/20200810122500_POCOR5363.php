<?php

use Phinx\Migration\AbstractMigration;

class POCOR5363 extends AbstractMigration
{
    public function up()
    {
        // Create tables
        $this->execute("CREATE TABLE `student_mark_type_statuses` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `academic_period_id` int(11) DEFAULT NULL,
          `student_attendance_mark_type_id` int(11) DEFAULT NULL,
          `date_enabled` date DEFAULT NULL,
          `date_disabled` date DEFAULT NULL,
          PRIMARY KEY (`id`)
        )");
        $this->execute("CREATE TABLE `student_mark_type_status_grades` (
          `id` char(36) NOT NULL,
          `education_grade_id` int(11) NOT NULL,
          `student_mark_type_status_id` int(11) NOT NULL,
          PRIMARY KEY (`id`),
          KEY `education_grade_id` (`education_grade_id`),
          KEY `student_mark_type_status_id` (`student_mark_type_status_id`)
        )");
        $this->execute('TRUNCATE `student_attendance_mark_types`');
        $this->execute('ALTER TABLE `student_attendance_mark_types` DROP PRIMARY KEY');
        $this->execute('ALTER TABLE `student_attendance_mark_types` DROP COLUMN `education_grade_id`');
        $this->execute('ALTER TABLE `student_attendance_mark_types` DROP COLUMN `academic_period_id`');

        $this->execute("ALTER TABLE `student_attendance_mark_types` ADD COLUMN `id` int(11) PRIMARY KEY NOT NULL AUTO_INCREMENT FIRST");
        $this->execute("ALTER TABLE `student_attendance_mark_types` ADD COLUMN `name` varchar(100)  AFTER `id`");
        $this->execute("ALTER TABLE `student_attendance_mark_types` ADD COLUMN `code` varchar(100)  AFTER `name`");

         $this->execute('TRUNCATE `student_attendance_per_day_periods`');
         $this->execute('ALTER TABLE `student_attendance_per_day_periods` DROP COLUMN `education_grade_id`');
         $this->execute('ALTER TABLE `student_attendance_per_day_periods` DROP COLUMN `academic_period_id`');
         $this->execute("ALTER TABLE `student_attendance_per_day_periods` ADD COLUMN `student_attendance_mark_type_id` int(11) AFTER `name`");

         $this->execute('CREATE TABLE `z_5363_locale_contents` LIKE `locale_contents`');
        $this->execute('INSERT INTO `z_5363_locale_contents` SELECT * FROM `locale_contents`');
        // locale_contents
        $localeContent = [
            [
                'en' => 'Student Attendance Mark Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Attendance Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Student Attendance Type',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],            
            [
                'en' => 'Attendance Per Day',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
            [
                'en' => 'Attendances',
                'created_user_id' => 1,
                'created' => date('Y-m-d H:i:s')
            ],
        ];
        $this->insert('locale_contents', $localeContent);  
    }

    public function down()
    {
        // For tables
        $this->execute('DROP TABLE IF EXISTS `student_mark_type_statuses`');
        $this->execute('DROP TABLE IF EXISTS `student_mark_type_status_grades`');
        $this->execute('TRUNCATE `student_attendance_mark_types`');
        $this->execute('ALTER TABLE `student_attendance_mark_types` ADD COLUMN `education_grade_id` int(11) PRIMARY KEY NOT NULL FIRST');
        $this->execute('ALTER TABLE `student_attendance_mark_types` ADD COLUMN `academic_period_id` int(11) PRIMARY KEY NOT NULL AFTER `education_grade_id`');
        $this->execute("ALTER TABLE `student_attendance_mark_types` DROP COLUMN `id`");
       $this->execute("ALTER TABLE `student_attendance_mark_types` DROP COLUMN `name`");
       $this->execute('TRUNCATE `student_attendance_per_day_periods`');
       $this->execute("ALTER TABLE `student_attendance_per_day_periods` DROP COLUMN `student_attendance_mark_type_id`");
       $this->execute('ALTER TABLE `student_attendance_per_day_periods` ADD COLUMN `education_grade_id` int(11) PRIMARY KEY NOT NULL AFTER `name`');
        $this->execute('ALTER TABLE `student_attendance_per_day_periods` ADD COLUMN `academic_period_id`  int(11) PRIMARY KEY NOT NULL AFTER `education_grade_id`');  
        $this->execute('DROP TABLE IF EXISTS `locale_contents`');
        $this->execute('RENAME TABLE `z_5363_locale_contents` TO `locale_contents`');      
    }
}
