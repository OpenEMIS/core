<?php

use Migrations\AbstractMigration;

class POCOR8224 extends AbstractMigration
{
    public function up()
    {
        $this->execute('CREATE TABLE IF NOT EXISTS `z_8224_security_functions` LIKE `security_functions`');
        $this->execute('INSERT IGNORE INTO `z_8224_security_functions` SELECT * FROM `security_functions`');

        // Check if the table already exists before creating it
        $this->execute('
            CREATE TABLE IF NOT EXISTS `assessment_item_student_exemptions` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `assessment_id` INT NOT NULL COMMENT "links to assessments.id",
                `education_subject_id` INT NOT NULL COMMENT "links to education_subjects.id",
                `student_id` INT UNSIGNED NOT NULL COMMENT "links to security_users.id",
                `institution_class_id` INT NOT NULL COMMENT "links to institution_classes.id",
                `education_grade_id` INT NOT NULL COMMENT "links to education_grades.id",
                `assessment_period_id` INT NOT NULL COMMENT "links to assessment_periods.id",
                `modified_user_id` INT UNSIGNED NULL COMMENT "User who last modified this record",
                `modified` DATETIME NULL,
                `created_user_id` INT UNSIGNED NOT NULL COMMENT "User who created this record",
                `created` DATETIME NOT NULL,
                PRIMARY KEY (`id`),

                -- Add a unique constraint to ensure the combination of student, class, grade, assessment, subject, and period is unique
                UNIQUE KEY `uq_student_assessment` (`student_id`, `institution_class_id`, `education_grade_id`, `assessment_id`, `education_subject_id`, `assessment_period_id`),

                -- Indexes for optimizing performance on foreign key lookups
                KEY `idx_assessment_id` (`assessment_id`),
                KEY `idx_education_subject_id` (`education_subject_id`),
                KEY `idx_student_id` (`student_id`),
                KEY `idx_institution_class_id` (`institution_class_id`),
                KEY `idx_education_grade_id` (`education_grade_id`),
                KEY `idx_assessment_period_id` (`assessment_period_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
        ');

        // Adding new entry into security_functions table
        // CHECK THAT THERE IS NO SUCH SECURITY FUNCTION FIRSt
        $query = $this->fetchRow("SELECT * FROM `security_functions`
                          WHERE `name` = 'Exemptions' AND `controller` = 'Institutions'
                          AND `module` = 'Institutions' AND `category` = 'Students'"
            );

        if (!$query) {
            $this->execute("INSERT INTO `security_functions` (
                                  `id`,
                                  `name`,
                                  `controller`,
                                  `module`,
                                  `category`,
                                  `parent_id`,
                                  `_view`,
                                  `_edit`,
                                  `_add`,
                                  `_delete`,
                                  `_execute`,
                                  `order`,
                                  `visible`,
                                  `description`,
                                  `modified_user_id`,
                                  `modified`,
                                  `created_user_id`,
                                  `created`) VALUES (NULL,
                                                     'Exemptions', 'Institutions', 'Institutions', 'Students', '8',
                                                     NULL, 'AssessmentItemExemptions.edit',
                                                     NULL, NULL, NULL,
                                                     '72', '1', NULL, NULL, NULL,
                                                     '2', '2024-09-19 00:01:04');"
                );
        }

    }

    public function down()
    {
        // Drop the table if it exists
        $this->execute('DROP TABLE IF EXISTS `assessment_item_student_exemptions`');
        $this->execute('SET FOREIGN_KEY_CHECKS=0;');
        $this->execute('DROP TABLE IF EXISTS `security_functions`');
        $this->execute('RENAME TABLE `z_8224_security_functions` TO `security_functions`');
        $this->execute('SET FOREIGN_KEY_CHECKS=1;');
    }
}
