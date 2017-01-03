-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3588', NOW());

-- examination_items
RENAME TABLE `examination_items` TO `z_3588_examination_items`;

DROP TABLE IF EXISTS `examination_items`;
CREATE TABLE IF NOT EXISTS `examination_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `examination_date` date DEFAULT NULL,
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `education_subject_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to education_subjects.id',
  `examination_grading_type_id` int(11) NOT NULL COMMENT 'links to examination_grading_types.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `examination_id` (`examination_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `examination_grading_type_id` (`examination_grading_type_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination subjects for a particular examination';

INSERT INTO `examination_items` (`name`, `code`, `weight`, `examination_date`, `start_time`, `end_time`, `examination_id`, `education_subject_id`, `examination_grading_type_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Subjects`.`name`, `Subjects`.`code`, `Items`.`weight`, `Items`.`examination_date`, `Items`.`start_time`, `Items`.`end_time`, `Items`.`examination_id`, `Items`.`education_subject_id`, `Items`.`examination_grading_type_id`, `Items`.`modified_user_id`, `Items`.`modified`, `Items`.`created_user_id`, `Items`.`created`
FROM `z_3588_examination_items` `Items`
INNER JOIN `education_subjects` `Subjects`
ON `Subjects`.`id` = `Items`.`education_subject_id`;

-- examination_item_results
RENAME TABLE `examination_item_results` TO `z_3588_examination_item_results`;

DROP TABLE IF EXISTS `examination_item_results`;
CREATE TABLE IF NOT EXISTS `examination_item_results` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `examination_item_id` int(11) NOT NULL COMMENT 'links to `examination_items.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `examination_grading_option_id` int(11) DEFAULT NULL COMMENT 'links to examination_grading_options.id',
  `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`academic_period_id`,`examination_id`,`examination_item_id`,`student_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `examination_item_id` (`examination_item_id`),
  KEY `student_id` (`student_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `examination_grading_option_id` (`examination_grading_option_id`),
  KEY `institution_id` (`institution_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the examination results for an individual student in a particular examination';

INSERT INTO `examination_item_results` (`id`, `marks`, `academic_period_id`, `examination_id`, `examination_item_id`, `student_id`, `education_subject_id`, `examination_centre_id`, `examination_grading_option_id`, `institution_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`Results`.`academic_period_id`, ',', `Results`.`examination_id`, ',', `Items`.`id`, ',', `Results`.`student_id`), '256'), `Results`.`marks`, `Results`.`academic_period_id`, `Results`.`examination_id`, `Items`.`id`, `Results`.`student_id`, `Results`.`education_subject_id`, `Results`.`examination_centre_id`, `Results`.`examination_grading_option_id`, `Results`.`institution_id`, `Results`.`modified_user_id`, `Results`.`modified`, `Results`.`created_user_id`, `Results`.`created`
FROM `z_3588_examination_item_results` `Results`
INNER JOIN `examination_items` `Items`
ON (`Results`.`examination_id` = `Items`.`examination_id`
AND `Results`.`education_subject_id` = `Items`.`education_subject_id`);

-- examination_centre_students
RENAME TABLE `examination_centre_students` TO `z_3588_examination_centre_students`;

DROP TABLE IF EXISTS `examination_centre_students`;
CREATE TABLE IF NOT EXISTS `examination_centre_students` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `registration_number` varchar(20) DEFAULT NULL,
  `total_mark` decimal(6,2) DEFAULT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `examination_item_id` int(11) NOT NULL COMMENT 'links to `examination_items.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to `education_subjects.id',
  `institution_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to institutions.id',
  `education_grade_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to education_grades.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_period.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examination.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`,`student_id`,`examination_item_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `student_id` (`student_id`),
  KEY `examination_item_id` (`examination_item_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `institution_id` (`institution_id`),
  KEY `education_grade_id` (`education_grade_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the students registered to an examination center for a particular examination';

INSERT INTO `examination_centre_students` (`id`, `registration_number`, `total_mark`, `examination_centre_id`, `student_id`, `examination_item_id`, `education_subject_id`, `institution_id`, `education_grade_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`Students`.`examination_centre_id`, ',', `Students`.`student_id`, ',', `Items`.`id`), '256'), `Students`.`registration_number`, `Students`.`total_mark`, `Students`.`examination_centre_id`, `Students`.`student_id`, `Items`.`id`, `Students`.`education_subject_id`, `Students`.`institution_id`, `Students`.`education_grade_id`, `Students`.`academic_period_id`, `Students`.`examination_id`, `Students`.`modified_user_id`, `Students`.`modified`, `Students`.`created_user_id`, `Students`.`created`
FROM `z_3588_examination_centre_students` `Students`
INNER JOIN `examination_items` `Items`
ON (`Students`.`examination_id` = `Items`.`examination_id`
AND `Students`.`education_subject_id` = `Items`.`education_subject_id`);

-- examination_centre_subjects
RENAME TABLE `examination_centre_subjects` TO `z_3588_examination_centre_subjects`;

DROP TABLE IF EXISTS `examination_centre_subjects`;
CREATE TABLE IF NOT EXISTS `examination_centre_subjects` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `examination_centre_id` int(11) NOT NULL COMMENT 'links to examination_centres.id',
  `examination_item_id` int(11) NOT NULL COMMENT 'links to `examination_items.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `examination_id` int(11) NOT NULL COMMENT 'links to examinations.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`examination_centre_id`,`examination_item_id`),
  KEY `examination_centre_id` (`examination_centre_id`),
  KEY `examination_item_id` (`examination_item_id`),
  KEY `education_subject_id` (`education_subject_id`),
  KEY `academic_period_id` (`academic_period_id`),
  KEY `examination_id` (`examination_id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the examination centres for a particular examination subject';

INSERT INTO `examination_centre_subjects` (`id`, `examination_centre_id`, `examination_item_id`, `education_subject_id`, `academic_period_id`, `examination_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`CentreSubjects`.`examination_centre_id`, ',', `Items`.`id`), '256'), `CentreSubjects`.`examination_centre_id`, `Items`.`id`, `CentreSubjects`.`education_subject_id`, `CentreSubjects`.`academic_period_id`, `CentreSubjects`.`examination_id`, `CentreSubjects`.`modified_user_id`, `CentreSubjects`.`modified`, `CentreSubjects`.`created_user_id`, `CentreSubjects`.`created`
FROM `z_3588_examination_centre_subjects` `CentreSubjects`
INNER JOIN `examination_items` `Items`
ON (`CentreSubjects`.`examination_id` = `Items`.`examination_id`
AND `CentreSubjects`.`education_subject_id` = `Items`.`education_subject_id`);

-- import_mapping
UPDATE `import_mapping`
SET `column_name` = 'examination_item_id', `description` = 'Id', `lookup_plugin` = 'Examination', `lookup_model` = 'ExaminationItems', `lookup_column` = 'id'
WHERE `model` = 'Examination.ExaminationItemResults' AND `column_name` = 'education_subject_id';

-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3683', NOW());

-- assessment_periods
RENAME TABLE `assessment_periods` TO `z_3683_assessment_periods`;

DROP TABLE IF EXISTS `assessment_periods`;
CREATE TABLE IF NOT EXISTS `assessment_periods` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `name` varchar(100) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `date_enabled` date NOT NULL,
  `date_disabled` date NOT NULL,
  `weight` decimal(6,2) DEFAULT '0.00',
  `academic_term` varchar(250) DEFAULT NULL,
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `assessment_id` (`assessment_id`),
  INDEX `modified_user_id` (`modified_user_id`),
  INDEX `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the list of periods for a specific assessment';

INSERT INTO `assessment_periods` (`id`, `code`, `name`, `start_date`, `end_date`, `date_enabled`, `date_disabled`, `weight`, `academic_term`, `assessment_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `id`, `code`, `name`, `start_date`, `end_date`, `date_enabled`, `date_disabled`, `weight`, NULL, `assessment_id`, `modified_user_id`, `modified`, `created_user_id`, `created` FROM `z_3683_assessment_periods`;

-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3527', NOW());

RENAME TABLE `db_patches` TO `system_patches`;

CREATE TABLE IF NOT EXISTS `system_updates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_released` date NOT NULL,
  `date_approved` date DEFAULT NULL,
  `approved_by` int(11) DEFAULT NULL COMMENT 'links to security_users.id',
  `status` int(11) NOT NULL DEFAULT 1 COMMENT '1 -> Pending, 2 -> Approved',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL DEFAULT 1,
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `system_updates` ADD UNIQUE(`version`);

INSERT INTO `security_functions` (id, name, controller, module, category, parent_id, _view, _execute, `order`, `visible`, created_user_id, created) VALUES
(5060, 'Updates', 'Systems', 'Administration', 'Updates', 5000, 'Updates.index|Updates.view', 'Updates.updates', 5061, 1, 1, NOW());


INSERT INTO config_items (id, name, code, type, label, value, default_value, editable, visible, field_type, option_type, created_user_id, created) VALUES
(200, 'Version Support Emails', 'version_support_emails', 'System', 'Version Support Emails', 'support@openemis.org,shasanuddin@kordit.com', 'support@openemis.org', 0, 0, '', '', 1, NOW()),
(201, 'Version API Domain', 'version_api_domain', 'System', 'Version API Domain', 'https://demo.openemis.org/core', 'https://demo.openemis.org/core', 0, 0, '', '', 1, NOW());

TRUNCATE TABLE system_updates;
INSERT INTO system_updates
SELECT
NULL,
version,
created,
created,
1,
2,
null, null, 1, created
FROM system_patches
WHERE version IS NOT NULL
AND NOT EXISTS (
  SELECT 1 FROM system_updates WHERE system_updates.version = system_patches.version
)
GROUP BY version
ORDER BY created ASC, version ASC;


-- Add below into version commit.sql at the end of the script

-- SET @maxId := 0;
-- SELECT max(id) + 1 INTO @maxId FROM system_updates;
-- INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
-- (
--   @maxId,
--   (SELECT value FROM config_items WHERE code = 'db_version'),
--   NOW(), NOW(), 1, 2, NOW()
-- );


-- 3.8.4
UPDATE config_items SET value = '3.8.4' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
