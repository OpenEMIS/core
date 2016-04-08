-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2759', NOW());

-- assessments
RENAME TABLE `assessments` TO `z_2759_assessments`;

DROP TABLE IF EXISTS `assessments`;
CREATE TABLE IF NOT EXISTS `assessments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `type` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> Non-official, 2 -> Official',
  `academic_period_id` int(11) NOT NULL,
  `education_grade_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `education_grade_id` (`education_grade_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `assessments` (`id`, `code`, `name`, `description`, `type`, `academic_period_id`, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `description`, `type`, 0, `education_grade_id`, `modified_user_id`, `modified`, `created_user_id`, `created`
FROM `z_2759_assessments`;

UPDATE `assessments` SET `type` = 2 WHERE `type` = 1;
UPDATE `assessments` SET `type` = 1 WHERE `type` = 0;

-- assessment_items
RENAME TABLE `assessment_items` TO `z_2759_assessment_items`;

DROP TABLE IF EXISTS `assessment_items`;
CREATE TABLE IF NOT EXISTS `assessment_items` (
  `id` char(36) NOT NULL,
  `weight` decimal(6,2) DEFAULT NULL,
  `assessment_grading_type_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_grading_type_id` (`assessment_grading_type_id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `assessment_items` (`id`, `weight`, `assessment_grading_type_id`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), NULL, `assessment_grading_type_id`, `assessment_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_2759_assessment_items`;

-- assessment_item_results
RENAME TABLE `assessment_item_results` TO `z_2759_assessment_item_results`;

DROP TABLE IF EXISTS `assessment_item_results`;
CREATE TABLE IF NOT EXISTS `assessment_item_results` (
  `id` char(36) NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `assessment_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `assessment_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `assessment_period_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`assessment_id`,`education_subject_id`,`institution_id`,`academic_period_id`,`assessment_period_id`),
  INDEX `assessment_grading_option_id` (`assessment_grading_option_id`),
  INDEX `student_id` (`student_id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `assessment_period_id` (`assessment_period_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 PARTITION BY HASH(`academic_period_id`) PARTITIONS 8;

INSERT INTO `assessment_item_results` (`id`, `marks`, `assessment_grading_option_id`, `student_id`, `assessment_id`, `education_subject_id`, `institution_id`, `academic_period_id`, `assessment_period_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `AssessmentItemResults`.`marks`, `AssessmentItemResults`.`assessment_grading_option_id`, `AssessmentItemResults`.`student_id`, `AssessmentItems`.`assessment_id`, `AssessmentItems`.`education_subject_id`, `AssessmentItemResults`.`institution_id`, `AssessmentItemResults`.`academic_period_id`, 0, `AssessmentItemResults`.`modified_user_id`, `AssessmentItemResults`.`modified`, `AssessmentItemResults`.`created_user_id`, `AssessmentItemResults`.`created`
FROM `z_2759_assessment_item_results` AS `AssessmentItemResults`
INNER JOIN `z_2759_assessment_items` AS `AssessmentItems`
ON `AssessmentItems`.`id` = `AssessmentItemResults`.`assessment_item_id`;

-- assessment_grading_types
RENAME TABLE `assessment_grading_types` TO `z_2759_assessment_grading_types`;

DROP TABLE IF EXISTS `assessment_grading_types`;
CREATE TABLE IF NOT EXISTS `assessment_grading_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `pass_mark` decimal(6,2) NOT NULL,
  `max` decimal(6,2) NOT NULL,
  `result_type` varchar(20) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `code` (`code`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `assessment_grading_types` (`id`, `code`, `name`, `pass_mark`, `max`, `result_type`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, 0, 0, '', `visible`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_2759_assessment_grading_types`;

-- assessment_periods
DROP TABLE IF EXISTS `assessment_periods`;
CREATE TABLE IF NOT EXISTS `assessment_periods` (
  `id` char(36) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `weight` decimal(6,2) NULL DEFAULT NULL,
  `assessment_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Backup tables
RENAME TABLE `assessment_statuses` TO `z_2759_assessment_statuses`;
RENAME TABLE `assessment_status_periods` TO `z_2759_assessment_status_periods`;
RENAME TABLE `institution_assessments` TO `z_2759_institution_assessments`;

-- institution_subjects
ALTER TABLE `institution_subjects` ADD INDEX(`education_subject_id`);

-- institution_subject_students
RENAME TABLE `institution_subject_students` TO `z_2759_institution_subject_students`;

DROP TABLE IF EXISTS `institution_subject_students`;
CREATE TABLE `institution_subject_students` (
  `id` char(36) NOT NULL,
  `status` int(1) NOT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `institution_subject_id` int(11) NOT NULL,
  `institution_class_id` int(11) NOT NULL,
  `institution_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `education_subject_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `student_id` (`student_id`),
  INDEX `institution_subject_id` (`institution_subject_id`),
  INDEX `institution_class_id` (`institution_class_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `education_subject_id` (`education_subject_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `institution_subject_students` (`id`, `status`, `total_mark`, `student_id`, `institution_subject_id`, `institution_class_id`, `institution_id`, `academic_period_id`, `education_subject_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT uuid(), `InstitutionSubjectStudents`.`status`, NULL, `InstitutionSubjectStudents`.`student_id`, `InstitutionSubjectStudents`.`institution_subject_id`, `InstitutionSubjectStudents`.`institution_class_id`, `InstitutionSubjects`.`institution_id`, `InstitutionSubjects`.`academic_period_id`, `InstitutionSubjects`.`education_subject_id`, `InstitutionSubjectStudents`.`modified_user_id`, `InstitutionSubjectStudents`.`modified`, `InstitutionSubjectStudents`.`created_user_id`, `InstitutionSubjectStudents`.`created`
FROM `z_2759_institution_subject_students` AS `InstitutionSubjectStudents`
INNER JOIN `institution_subjects` AS `InstitutionSubjects`
ON `InstitutionSubjects`.`id` = `InstitutionSubjectStudents`.`institution_subject_id`;

-- institution_students
RENAME TABLE `institution_students` TO `z_2759_institution_students`;

DROP TABLE IF EXISTS `institution_students`;
CREATE TABLE `institution_students` (
  `id` char(36) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `education_grade_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date NOT NULL,
  `end_year` int(4) NOT NULL,
  `final_result` decimal(6,2) DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `student_status_id` (`student_status_id`),
  INDEX `student_id` (`student_id`),
  INDEX `education_grade_id` (`education_grade_id`),
  INDEX `academic_period_id` (`academic_period_id`),
  INDEX `institution_id` (`institution_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `institution_students` (`id`, `student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `start_year`, `end_date`, `end_year`, `final_result`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `start_year`, `end_date`, `end_year`, NULL, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_2759_institution_students`;
