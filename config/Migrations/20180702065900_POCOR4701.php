<?php

use Phinx\Migration\AbstractMigration;

class POCOR4701 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE `z_4701_deleted_records` LIKE `deleted_records`');
        $this->table('z_4701_deleted_records')
            ->addColumn('status', 'integer', [
                'after' => 'deleted_date',
                'limit' => 1,
                'default' => 0,
                'null' => true
            ])
            ->save();

        $this->execute("INSERT INTO `z_4701_deleted_records` (`id`, `reference_table`, `reference_key`, `data`, `deleted_date`, `status`, `created_user_id`, `created`) SELECT `id`, `reference_table`, `reference_key`, `data`, `deleted_date`, 0, `created_user_id`, `created` FROM `deleted_records` WHERE `deleted_date` >= '20180606'
AND `reference_table` = 'Institution.InstitutionStudentsReportCards' or `reference_table` = 'Institution.StudentsReportCardsComments'");

        $this->execute('CREATE TABLE `z_4701_institution_students_report_cards_deleted` LIKE `institution_students_report_cards`');
        $this->execute('CREATE TABLE `z_4701_institution_students_report_cards_comments_deleted` LIKE `institution_students_report_cards_comments`');

        // back up institution_students_report_cards exclude attachment
        $this->execute('CREATE TABLE `z_4701_institution_students_report_cards` LIKE `institution_students_report_cards`');
        $this->execute('INSERT INTO `z_4701_institution_students_report_cards` (`id`, `status`, `principal_comments`, `homeroom_teacher_comments`, `file_name`, `file_content`, `started_on`, `completed_on`, `report_card_id`, `student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `status`, `principal_comments`, `homeroom_teacher_comments`, null, null, `started_on`, `completed_on`, `report_card_id`, `student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `institution_class_id`, `modified_user_id`, `modified`, `created_user_id`, `created` from `institution_students_report_cards`');

        // back up institution_students_report_cards_comments exclude attachment
        $this->execute('CREATE TABLE `z_4701_institution_students_report_cards_comments` LIKE `institution_students_report_cards_comments`');
        $this->execute('INSERT INTO `z_4701_institution_students_report_cards_comments` (`id`, `comments`, `report_card_comment_code_id`, `report_card_id`, `student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `education_subject_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `comments`, `report_card_comment_code_id`, `report_card_id`, `student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `education_subject_id`, `staff_id`, `modified_user_id`, `modified`, `created_user_id`, `created` from `institution_students_report_cards_comments`');
    }

    public function down()
    {
        $this->dropTable('z_4701_deleted_records');
        $this->dropTable('z_4701_institution_students_report_cards_deleted');
        $this->dropTable('z_4701_institution_students_report_cards_comments_deleted');

        $this->execute("UPDATE `institution_students_report_cards` AS `src`
        INNER JOIN `z_4701_institution_students_report_cards` AS `backup_src`
        ON `backup_src`.`report_card_id` = `src`.`report_card_id`
        AND `backup_src`.`student_id` = `src`.`student_id`
        AND `backup_src`.`institution_id` = `src`.`institution_id`
        AND `backup_src`.`academic_period_id` = `src`.`academic_period_id`
        AND `backup_src`.`education_grade_id` = `src`.`education_grade_id`
        AND `backup_src`.`institution_class_id` = `src`.`institution_class_id`
        SET `src`.`principal_comments` = `backup_src`.`principal_comments`, `src`.`homeroom_teacher_comments` = `backup_src`.`homeroom_teacher_comments`");
        $this->dropTable('z_4701_institution_students_report_cards');

        $this->execute("UPDATE `institution_students_report_cards_comments` AS `srcc`
        INNER JOIN `z_4701_institution_students_report_cards_comments` AS `backup_srcc`
        ON `backup_srcc`.`report_card_id` = `srcc`.`report_card_id`
        AND `backup_srcc`.`student_id` = `srcc`.`student_id`
        AND `backup_srcc`.`institution_id` = `srcc`.`institution_id`
        AND `backup_srcc`.`academic_period_id` = `srcc`.`academic_period_id`
        AND `backup_srcc`.`education_grade_id` = `srcc`.`education_grade_id`
        AND `backup_srcc`.`education_grade_id` = `srcc`.`education_grade_id`
        SET `srcc`.`comments` = `backup_srcc`.`comments`,
        `srcc`.`report_card_comment_code_id` = `backup_srcc`.`report_card_comment_code_id`");
        $this->dropTable('z_4701_institution_students_report_cards_comments');
    }
}
