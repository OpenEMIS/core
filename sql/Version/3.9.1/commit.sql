-- POCOR-3459
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3459', NOW());

-- institutions
ALTER TABLE `institutions`
MODIFY COLUMN `classification` INT(1) NOT NULL DEFAULT '1' COMMENT '1 -> Academic Institution, 2 -> Non-academic institution';

UPDATE `institutions`
SET `classification` = 2
WHERE `classification` = 0;


-- POCOR-3342
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3342', NOW());

-- competency_grading_types
DROP TABLE IF EXISTS `competency_grading_types`;
CREATE TABLE `competency_grading_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `code` (`code`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of grading types that can be used for an assessable competency';

-- competency_grading_options
DROP TABLE IF EXISTS `competency_grading_options`;
CREATE TABLE `competency_grading_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` TEXT NULL,
  `competency_grading_type_id` int(11) NOT NULL COMMENT 'links to competency_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `competency_grading_type_id` (`competency_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all options linked to a specific grading type for competency';


-- Table structure for table `competency_templates`
DROP TABLE IF EXISTS `competency_templates`;
CREATE TABLE `competency_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`academic_period_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the competency template for a specific grade';

-- Table structure for table `competency_items`
DROP TABLE IF EXISTS `competency_items`;
CREATE TABLE `competency_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`academic_period_id`, `competency_template_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of competency items for a given competency template';

-- Table structure for table `competency_criterias`
DROP TABLE IF EXISTS `competency_criterias`;
CREATE TABLE `competency_criterias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_grading_type_id` int(11) NOT NULL COMMENT 'links to competency_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`academic_period_id`, `competency_item_id`, `competency_template_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_item_id` (`competency_item_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `competency_grading_type_id` (`competency_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of competency criterias for a given competency item';

-- Table structure for table `competency_periods`
DROP TABLE IF EXISTS `competency_periods`;
CREATE TABLE `competency_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(250) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`,`academic_period_id`),
  KEY `id` (`id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of periods for a specific competency';

-- Table structure for table `competency_items_periods`
DROP TABLE IF EXISTS `competency_items_periods`;
CREATE TABLE `competency_items_periods` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_period_id` int(11) NOT NULL COMMENT 'links to competency_periods.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `competency_template_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`competency_item_id`,`competency_period_id`,`academic_period_id`,`competency_template_id`),
  UNIQUE KEY `id` (`id`),
  KEY `id_index` (`id`),
  KEY `competency_item_id` (`competency_item_id`),
  KEY `competency_period_id` (`competency_period_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of competency items for a specific competency period';

-- Table structure for table `student_competency_results`
DROP TABLE IF EXISTS `student_competency_results`;
CREATE TABLE `student_competency_results` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `competency_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `competency_template_id` int(11) NOT NULL COMMENT 'links to competency_templates.id',
  `competency_item_id` int(11) NOT NULL COMMENT 'links to competency_items.id',
  `competency_criteria_id` int(11) NOT NULL COMMENT 'links to competency_criterias.id',
  `competency_period_id` int(11) NOT NULL COMMENT 'links to competency_periods.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`student_id`,`competency_template_id`,`competency_item_id`,`competency_criteria_id`,`competency_period_id`,`institution_id`,`academic_period_id`),
  KEY `id` (`id`),
  KEY `student_id` (`student_id`),
  KEY `competency_template_id` (`competency_template_id`),
  KEY `competency_item_id` (`competency_item_id`),
  KEY `competency_criteria_id` (`competency_criteria_id`),
  KEY `competency_period_id` (`competency_period_id`),
  KEY `institution_id` (`institution_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `competency_grading_option_id` (`competency_grading_option_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the competency results for an individual student in an institution';

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('0e77e3d5-e39d-11e6-a064-525400b263eb', 'Criterias', 'competency_grading_type_id', 'Administration -> Competencies -> Criterias', 'Criteria Grading Type', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('7daa7045-e920-11e6-b872-525400b263eb', 'Items', 'name', 'Administration > Competencies > Templates > Items', 'Competency Item', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('d24f6444-e922-11e6-b872-525400b263eb', 'Criterias', 'name', 'Administration > Competencies > Criterias > Items', 'Criteria Name', '1', '1', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 3
WHERE `order` >= 5056 AND `order` < 6000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES
(5061, 'Competency Setup', 'Competencies', 'Administration', 'Competencies', 5000, 'Templates.index|Templates.view|Items.index|Items.view|Criterias.index|Criterias.view', 'Templates.edit|Items.edit|Criterias.edit', 'Templates.add|Items.add|Criterias.add', 'Templates.remove|Items.remove|Criterias.remove', NULL, 5056, 1, NULL, NULL, NULL, 1, NOW()),
(5062, 'Periods', 'Competencies', 'Administration', 'Competencies', 5000, 'Periods.index|Periods.view', 'Periods.edit', 'Periods.add', 'Periods.remove', NULL, 5057, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00'),
(5063, 'GradingTypes', 'Competencies', 'Administration', 'Competencies', 5000, 'GradingTypes.index|GradingTypes.view', 'GradingTypes.edit', 'GradingTypes.add', 'GradingTypes.remove', NULL, 5058, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00'),
(1053, 'Competency Results', 'Institutions', 'Institutions', 'Students', 8, 'StudentCompetencies.index|StudentCompetencies.view', 'StudentCompetencies.edit', NULL, 'StudentCompetencies.remove', NULL, 1054, 1, NULL, NULL, NULL, 1, '2017-01-27 00:00:00');


-- POCOR-3562
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


-- 3.9.1
UPDATE config_items SET value = '3.9.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
