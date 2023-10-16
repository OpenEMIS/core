-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3562', NOW());

-- institution_subject_students
RENAME TABLE `institution_subject_students` TO `z_3562_institution_subject_students`;

DROP TABLE IF EXISTS `institution_subject_students`;
CREATE TABLE IF NOT EXISTS `institution_subject_students` (
 `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
 `total_mark` decimal(6,2) DEFAULT NULL,
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `institution_subject_id` int(11) NOT NULL COMMENT 'links to institution_subjects.id',
 `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
 `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
 `student_status_id`int(11) NOT NULL COMMENT 'links to student_statuses.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`student_id`,`institution_class_id`,`institution_id`,`academic_period_id`,`education_subject_id`, `education_grade_id`),
 KEY `student_id` (`student_id`),
 KEY `institution_subject_id` (`institution_subject_id`),
 KEY `institution_class_id` (`institution_class_id`),
 KEY `institution_id` (`institution_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `education_grade_id` (`education_grade_id`),
 KEY `student_status_id` (`student_status_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of students attending the subjects';

INSERT INTO `institution_subject_students` (`id`, `total_mark`, `student_id`, `institution_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`, `education_subject_id`, `education_grade_id`, `student_status_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `SubjectStudents`.`total_mark`, `SubjectStudents`.`student_id`, `SubjectStudents`.`institution_subject_id`, `SubjectStudents`.`institution_class_id`, `SubjectStudents`.`institution_id`, `SubjectStudents`.`academic_period_id`, `SubjectStudents`.`education_subject_id`, `ClassStudents`.`education_grade_id`, `ClassStudents`.`student_status_id`, `SubjectStudents`.`modified_user_id`, `SubjectStudents`.`modified`, `SubjectStudents`.`created_user_id`, `SubjectStudents`.`created`
FROM `z_3562_institution_subject_students` `SubjectStudents`
INNER JOIN `institution_class_students` `ClassStudents`
ON (`SubjectStudents`.`student_id` = `ClassStudents`.`student_id`
AND `SubjectStudents`.`institution_class_id` = `ClassStudents`.`institution_class_id`
AND `SubjectStudents`.`academic_period_id` = `ClassStudents`.`academic_period_id`
AND `SubjectStudents`.`institution_id` = `ClassStudents`.`institution_id`)
WHERE `SubjectStudents`.`status` = 1;

-- assessment_item_results
RENAME TABLE `assessment_item_results` TO `z_3562_assessment_item_results`;

DROP TABLE IF EXISTS `assessment_item_results`;
CREATE TABLE IF NOT EXISTS `assessment_item_results` (
 `id` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
 `marks` decimal(6,2) DEFAULT NULL,
 `assessment_grading_option_id` int(11) DEFAULT NULL,
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
 `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`student_id`,`assessment_id`,`education_subject_id`,`education_grade_id`,`academic_period_id`,`assessment_period_id`),
 KEY `assessment_grading_option_id` (`assessment_grading_option_id`),
 KEY `student_id` (`student_id`),
 KEY `assessment_id` (`assessment_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `education_grade_id` (`education_grade_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `assessment_period_id` (`assessment_period_id`),
 KEY `institution_id` (`institution_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the assessment results for an individual student in an institution' PARTITION BY HASH(`assessment_id`) PARTITIONS 101;

INSERT INTO `assessment_item_results` (`id`, `marks`, `assessment_grading_option_id`, `student_id`, `assessment_id`, `education_subject_id`, `education_grade_id`, `academic_period_id`, `assessment_period_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `Results`.`marks`, `Results`.`assessment_grading_option_id`, `Results`.`student_id`, `Results`.`assessment_id`, `Results`.`education_subject_id`, `Assessments`.`education_grade_id`, `Results`.`academic_period_id`, `Results`.`assessment_period_id`, `Results`.`institution_id`, `Results`.`modified_user_id`, `Results`.`modified`, `Results`.`created_user_id`, `Results`.`created`
FROM `z_3562_assessment_item_results` `Results`
INNER JOIN `assessments` `Assessments`
ON `Assessments`.`id` = `Results`.`assessment_id`
WHERE `Results`.`created` = (
    SELECT MAX(`Results2`.`created`)
    FROM `z_3562_assessment_item_results` `Results2`
    WHERE `Results`.`student_id` = `Results2`.`student_id`
    AND `Results`.`assessment_id` = `Results2`.`assessment_id`
    AND `Results`.`education_subject_id` = `Results2`.`education_subject_id`
    AND `Results`.`academic_period_id` = `Results2`.`academic_period_id`
    AND `Results`.`assessment_period_id` = `Results2`.`assessment_period_id`
);

DELETE `Results`
FROM `assessment_item_results` `Results`
WHERE NOT EXISTS (
    SELECT 1
    FROM `institution_subject_students` `SubjectStudents`
    WHERE `Results`.`student_id` = `SubjectStudents`.`student_id`
    AND `Results`.`education_subject_id` = `SubjectStudents`.`education_subject_id`
    AND `Results`.`institution_id` = `SubjectStudents`.`institution_id`
    AND `Results`.`academic_period_id` = `SubjectStudents`.`academic_period_id`
    AND `Results`.`education_grade_id` = `SubjectStudents`.`education_grade_id`
);
