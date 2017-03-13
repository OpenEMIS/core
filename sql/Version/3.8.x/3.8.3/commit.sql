-- POCOR-3576
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3576', NOW());

-- excel_templates
DROP TABLE IF EXISTS `excel_templates`;
CREATE TABLE IF NOT EXISTS `excel_templates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `module` varchar(100) NOT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains excel template for a specific report';

-- Labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('ad8fa33a-c0d8-11e6-90e8-525400b263eb', 'ExcelTemplates', 'file_content', 'CustomExcels -> ExcelTemplates', 'Attachment', NULL, NULL, 1, NULL, NULL, 1, NOW());

-- security_functions
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(5059, 'Excel Templates', 'CustomExcels', 'Administration', 'CustomExcels', '5000', 'ExcelTemplates.index|ExcelTemplates.view', 'ExcelTemplates.edit', NULL, NULL, 'ExcelTemplates.download', 5059, 1, NULL, NULL, NULL, 1, NOW());

-- assessment_item_results
RENAME TABLE `assessment_item_results` TO `z_3576_assessment_item_results`;

DROP TABLE IF EXISTS `assessment_item_results`;
CREATE TABLE IF NOT EXISTS `assessment_item_results` (
  `id` char(36) NOT NULL,
  `marks` decimal(6,2) DEFAULT NULL,
  `assessment_grading_option_id` int(11) DEFAULT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `assessment_id` int(11) NOT NULL COMMENT 'links to assessments.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id',
  `institution_id` int(11) NOT NULL COMMENT 'links to institutions.id',
  `academic_period_id` int(11) NOT NULL COMMENT 'links to academic_periods.id',
  `assessment_period_id` int(11) NOT NULL COMMENT 'links to assessment_periods.id',
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all the assessment results for an individual student in an institution' PARTITION BY HASH(`academic_period_id`) PARTITIONS 8;

INSERT INTO `assessment_item_results` SELECT * FROM `z_3576_assessment_item_results`;

-- assessment_items_grading_types
ALTER TABLE `assessment_items_grading_types`
        ADD INDEX (`assessment_grading_type_id`),
        ADD INDEX (`assessment_id`),
        ADD INDEX (`education_subject_id`),
        ADD INDEX (`assessment_period_id`);

-- examination_centres_institutions
ALTER TABLE `examination_centres_institutions`
        ADD INDEX (`examination_centre_id`),
        ADD INDEX (`institution_id`);

-- examination_centres_invigilators
ALTER TABLE `examination_centres_invigilators`
        ADD INDEX (`examination_centre_id`),
        ADD INDEX (`invigilator_id`);

-- examination_centre_rooms_invigilators
ALTER TABLE `examination_centre_rooms_invigilators`
        ADD INDEX (`examination_centre_room_id`),
        ADD INDEX (`invigilator_id`);

-- examination_centre_special_needs
ALTER TABLE `examination_centre_special_needs`
        ADD INDEX (`examination_centre_id`),
        ADD INDEX (`special_need_type_id`);

-- examination_centre_students
ALTER TABLE `examination_centre_students`
        ADD INDEX (`examination_centre_id`),
        ADD INDEX (`student_id`),
        ADD INDEX (`education_subject_id`);

-- examination_centre_subjects
ALTER TABLE `examination_centre_subjects`
        ADD INDEX (`examination_centre_id`),
        ADD INDEX (`education_subject_id`);

-- examination_items
ALTER TABLE `examination_items`
        ADD INDEX (`examination_id`),
        ADD INDEX (`education_subject_id`);

-- examination_item_results
ALTER TABLE `examination_item_results`
        ADD INDEX (`academic_period_id`),
        ADD INDEX (`examination_id`),
        ADD INDEX (`education_subject_id`),
        ADD INDEX (`student_id`);


-- POCOR-3593
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3593', NOW());

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `nationality_id` INT NULL AFTER `date_of_death`,
ADD COLUMN `identity_type_id` INT NULL AFTER `nationality_id`,
ADD COLUMN `external_reference` VARCHAR(50) NULL AFTER `identity_number`;

UPDATE `security_users`
INNER JOIN `user_nationalities` ON `user_nationalities`.`security_user_id` = `security_users`.`id`
SET `security_users`.`nationality_id` = `user_nationalities`.`nationality_id`;

UPDATE `security_users`
INNER JOIN `nationalities`
        ON `nationalities`.`id` = `security_users`.`nationality_id`
INNER JOIN `user_identities`
        ON `user_identities`.`identity_type_id` = `nationalities`.`identity_type_id`
        AND `user_identities`.`security_user_id` = `security_users`.`id`
SET `security_users`.`identity_type_id` = `user_identities`.`identity_type_id`, `security_users`.`identity_number` = `user_identities`.`number`;

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES ('102', 'external_data_source_type', 'Custom', 'Custom', '3', '1');

CREATE TABLE `z_3593_config_items` LIKE `config_items`;

INSERT INTO `z_3593_config_items`
SELECT * FROM `config_items` WHERE `config_items`.`id` = 1002;

UPDATE `config_items` SET `value` = 'None' WHERE `id` = 1002;

CREATE TABLE `z_3593_external_data_source_attributes` LIKE `external_data_source_attributes`;

INSERT INTO `z_3593_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`;

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'ConfigExternalDataSource', 'client_id', 'Configuration > External Data Source', 'Client ID', '1', '1', NOW());

DELETE FROM `external_data_source_attributes`;


-- POCOR-3632
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3632', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` > 5010 AND `order` < 6000;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('5058', 'Assessment Periods', 'Assessments', 'Administration', 'Assessments', '5000', 'AssessmentPeriods.index|AssessmentPeriods.view', 'AssessmentPeriods.edit', 'AssessmentPeriods.add', 'AssessmentPeriods.remove', NULL, '5011', '1', NULL, NULL, NULL, '1', '2015-12-19 02:41:00');


-- POCOR-3633
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3633', NOW());

-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit|StudentUser.pull' WHERE `id`='2000';


-- POCOR-3623
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3623', NOW());

-- backup user_identities
DROP TABLE IF EXISTS `z_3623_user_identities`;
CREATE TABLE `z_3623_user_identities` LIKE `user_identities`;

INSERT INTO `z_3623_user_identities`
SELECT * FROM `user_identities` UI
WHERE NOT EXISTS (
        SELECT 1
    FROM `security_users` SU
    WHERE UI.`security_user_id` = SU.`id`
);

-- delete user_identities
DELETE FROM `user_identities`
WHERE NOT EXISTS (
        SELECT 1
    FROM `security_users`
    WHERE `user_identities`.`security_user_id` = `security_users`.`id`
);


-- 3.8.3
UPDATE config_items SET value = '3.8.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
