<?php

use Phinx\Migration\AbstractMigration;

class POCOR4704 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4704_institution_classes` LIKE `institution_classes`');
        $this->execute('INSERT INTO `z_4704_institution_classes` SELECT * FROM `institution_classes`');

        $this->execute('CREATE TABLE `z_4704_institution_subjects` LIKE `institution_subjects`');
        $this->execute('INSERT INTO `z_4704_institution_subjects` SELECT * FROM `institution_subjects`');

        $this->execute("update `institution_classes`
            SET `total_male_students` = (
                select count(`institution_class_students`.`id`) as `male_cnt`
                from `institution_class_students`
                inner join `student_statuses` on `student_statuses`.`id` = `institution_class_students`.`student_status_id`
                inner join `security_users` on `security_users`.`id` = `institution_class_students`.`student_id`
                inner join `genders` on `genders`.`id` = `security_users`.`gender_id`
                where `institution_class_students`.`institution_class_id` = `institution_classes`.`id`
                and `student_statuses`.`code` NOT IN ('TRANSFERRED', 'WITHDRAWN')
                and `genders`.`code` = 'M'
            )");

        $this->execute("update `institution_classes`
            SET `total_female_students` = (
                select count(`institution_class_students`.`id`) as `female_cnt`
                from `institution_class_students`
                inner join `student_statuses` on `student_statuses`.`id` = `institution_class_students`.`student_status_id`
                inner join `security_users` on `security_users`.`id` = `institution_class_students`.`student_id`
                inner join `genders` on `genders`.`id` = `security_users`.`gender_id`
                where `institution_class_students`.`institution_class_id` = `institution_classes`.`id`
                and `student_statuses`.`code` NOT IN ('TRANSFERRED', 'WITHDRAWN')
                and `genders`.`code` = 'F'
            )");

        $this->execute("update `institution_subjects`
            SET `total_male_students` = (
                select count(`institution_subject_students`.`id`) as `male_cnt`
                from `institution_subject_students`
                inner join `student_statuses` on `student_statuses`.`id` = `institution_subject_students`.`student_status_id`
                inner join `security_users` on `security_users`.`id` = `institution_subject_students`.`student_id`
                inner join `genders` on `genders`.`id` = `security_users`.`gender_id`
                where `institution_subject_students`.`institution_subject_id` = `institution_subjects`.`id`
                and `student_statuses`.`code` NOT IN ('TRANSFERRED', 'WITHDRAWN')
                and `genders`.`code` = 'M'
            )");

        $this->execute("update `institution_subjects`
            SET `total_female_students` = (
                select count(`institution_subject_students`.`id`) as `female_cnt`
                from `institution_subject_students`
                inner join `student_statuses` on `student_statuses`.`id` = `institution_subject_students`.`student_status_id`
                inner join `security_users` on `security_users`.`id` = `institution_subject_students`.`student_id`
                inner join `genders` on `genders`.`id` = `security_users`.`gender_id`
                where `institution_subject_students`.`institution_subject_id` = `institution_subjects`.`id`
                and `student_statuses`.`code` NOT IN ('TRANSFERRED', 'WITHDRAWN')
                and `genders`.`code` = 'F'
            )");
    }

    public function down()
    {
        $this->dropTable("institution_classes");
        $this->table("z_4704_institution_classes")->rename("institution_classes");

        $this->dropTable("institution_subjects");
        $this->table("z_4704_institution_subjects")->rename("institution_subjects");
    }
}
