<?php

use Phinx\Migration\AbstractMigration;

class POCOR5319 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `student_attendance_per_day_periods` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
          `education_grade_id` int(11) DEFAULT NULL COMMENT "links to education_grades.id",
          `academic_period_id` int(11) DEFAULT NULL COMMENT "links to academic_periods.id",
          `modified_user_id` int(11) DEFAULT NULL,
          `modified` datetime DEFAULT NULL,
          `created_user_id` int(11) NOT NULL,
          `created` datetime NOT NULL,
          PRIMARY KEY (`id`),
          KEY `education_grade_id` (`education_grade_id`),
          KEY `academic_period_id` (`academic_period_id`),
          KEY `modified_user_id` (`modified_user_id`),
          KEY `created_user_id` (`created_user_id`)
        )');
    }

    public function down()
    {
        $this->execute('DROP TABLE IF EXISTS `student_attendance_per_day_periods`');
    }
}
