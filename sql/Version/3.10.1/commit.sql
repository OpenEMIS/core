-- POCOR-3533
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3533', NOW());

-- report_cards
DROP TABLE IF EXISTS `report_cards`;
CREATE TABLE IF NOT EXISTS `report_cards` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
 `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
 `description` text COLLATE utf8mb4_unicode_ci,
 `start_date` date NOT NULL,
 `end_date` date NOT NULL,
 `principal_comments_required` int(1) NOT NULL,
 `homeroom_teacher_comments_required` int(1) NOT NULL,
 `teacher_comments_required` int(1) NOT NULL,
 `excel_template_name` varchar(250) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `excel_template` longblob,
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `education_grade_id` (`education_grade_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the report cards for a specific grade and academic period';

-- report_card_subjects
DROP TABLE IF EXISTS `report_card_subjects`;
CREATE TABLE IF NOT EXISTS `report_card_subjects` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `report_card_id` int(11) NOT NULL COMMENT 'links to report_cards.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
 `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`report_card_id`, `education_subject_id`),
 KEY `report_card_id` (`report_card_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `education_grade_id` (`education_grade_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the subjects that are included in a particular report card';

-- institution_students_report_cards
DROP TABLE IF EXISTS `institution_students_report_cards`;
CREATE TABLE IF NOT EXISTS `institution_students_report_cards` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `status` int(1) NOT NULL DEFAULT '1' COMMENT '1 -> New, 2 -> In Progress, 3 -> Generated, 4 -> Published',
 `principal_comments` text COLLATE utf8mb4_unicode_ci,
 `homeroom_teacher_comments` text COLLATE utf8mb4_unicode_ci,
 `file_name` varchar(250) COLLATE utf8mb4_unicode_ci,
 `file_content` longblob,
 `report_card_id` int(11) NOT NULL COMMENT 'links to report_cards.id',
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
 `institution_class_id` int(11) NOT NULL COMMENT 'links to institution_classes.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`report_card_id`, `student_id`, `institution_id`, `academic_period_id`, `education_grade_id`),
 KEY `report_card_id` (`report_card_id`),
 KEY `student_id` (`student_id`),
 KEY `institution_id` (`institution_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `education_grade_id` (`education_grade_id`),
 KEY `institution_class_id` (`institution_class_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the report cards generated for a student';

-- institution_students_report_cards_comments
DROP TABLE IF EXISTS `institution_students_report_cards_comments`;
CREATE TABLE IF NOT EXISTS `institution_students_report_cards_comments` (
 `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
 `comments` text COLLATE utf8mb4_unicode_ci,
 `report_card_comment_code_id` int(11) COMMENT 'links to report_card_comment_codes.id',
 `report_card_id` int(11) NOT NULL COMMENT 'links to report_cards.id',
 `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
 `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
 `education_grade_id` int(11) NOT NULL COMMENT 'links to education_grades.id',
 `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
 `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`report_card_id`, `student_id`, `institution_id`, `academic_period_id`, `education_grade_id`, `education_subject_id`),
 KEY `report_card_comment_code_id` (`report_card_comment_code_id`),
 KEY `report_card_id` (`report_card_id`),
 KEY `student_id` (`student_id`),
 KEY `institution_id` (`institution_id`),
 KEY `academic_period_id` (`academic_period_id`),
 KEY `education_grade_id` (`education_grade_id`),
 KEY `education_subject_id` (`education_subject_id`),
 KEY `staff_id` (`staff_id`),
 KEY `modified_user_id` (`modified_user_id`),
 KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the comments from subject teachers for a particular institution student report card';

-- report_card_comment_codes
DROP TABLE IF EXISTS `report_card_comment_codes`;
CREATE TABLE IF NOT EXISTS `report_card_comment_codes` (
 `id` int(11) NOT NULL AUTO_INCREMENT,
 `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
 `order` int(3) NOT NULL,
 `visible` int(1) NOT NULL DEFAULT '1',
 `editable` int(1) NOT NULL DEFAULT '1',
 `default` int(1) NOT NULL DEFAULT '0',
 `international_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `national_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
 `modified_user_id` int(11) DEFAULT NULL,
 `modified` datetime DEFAULT NULL,
 `created_user_id` int(11) NOT NULL,
 `created` datetime NOT NULL,
 PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This is a field option table containing the list of user-defined comment codes that can be used for student report cards';

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 4
WHERE `order` >= 1050 AND `order` <= 1056;

UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 2018 AND `order` <= 2031;

UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 5067 AND `order` <= 5071;

UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 7017 AND `order` <= 7051;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1057, 'Comments', 'Institutions', 'Institutions', 'Report Cards', 1000, 'ReportCardComments.index|ReportCardComments.view|Comments.index', 'Comments.edit', NULL, NULL, NULL, 1050, 1, NULL, NULL, NULL, 1, NOW()),
(1058, 'Statuses', 'Institutions', 'Institutions', 'Report Cards', 1000, 'ReportCardStatuses.index|ReportCardStatuses.view', NULL, NULL, NULL, NULL, 1051, 1, NULL, NULL, NULL, 1, NOW()),
(1059, 'Generate/Download', 'Institutions', 'Institutions', 'Report Cards', 1000, NULL, NULL, NULL, NULL, 'ReportCardStatuses.generate|ReportCardStatuses.generateAll|InstitutionStudentsReportCards.download|ReportCardStatuses.downloadAll', 1052, 1, NULL, NULL, NULL, 1, NOW()),
(1060, 'Publish/Unpublish', 'Institutions', 'Institutions', 'Report Cards', 1000, NULL, NULL, NULL, NULL, 'ReportCardStatuses.publish|ReportCardStatuses.publishAll|ReportCardStatuses.unpublish|ReportCardStatuses.unpublishAll', 1053, 1, NULL, NULL, NULL, 1, NOW()),
(2034, 'Report Cards', 'Students', 'Institutions', 'Students - Academic', 1000, 'ReportCards.index|ReportCards.view', NULL, NULL, NULL, 'ReportCards.download', 2018, 1, NULL, NULL, NULL, 1, NOW()),
(5072, 'Templates', 'ReportCards', 'Administration', 'Report Cards', 5000, 'Templates.index|Templates.view', 'Templates.edit|Templates.downloadTemplate', 'Templates.add', 'Templates.remove', 'Templates.download', 5067, 1, NULL, NULL, NULL, 1, NOW()),
(7053, 'Report Cards', 'Directories', 'Directory', 'Students - Academic', 7000, 'StudentReportCards.index|StudentReportCards.view', NULL, NULL, NULL, 'StudentReportCards.download', 7017, 1, NULL, NULL, NULL, 1, NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES
('1ef9db3d-3f7f-11e7-9c23-525400b263eb', 'ReportCardStatuses', 'openemis_no', 'Institutions -> Report Cards -> Statuses', 'OpenEMIS ID', 1, 1, NOW());


-- POCOR-3977
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3977', NOW());

ALTER TABLE `deleted_records`
DROP INDEX `reference_key` ;

ALTER TABLE `deleted_records`
CHANGE COLUMN `reference_key` `reference_key` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL ;


-- 3.10.1
UPDATE config_items SET value = '3.10.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
