-- POCOR-3092
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3092', NOW());

CREATE TABLE `z_3092_translations` LIKE `translations`;

INSERT INTO `z_3092_translations`
SELECT * FROM `translations`
WHERE `en` IN (
    '%s with %s',
    'Transfer of student %s from %s',
    '%s in %s',
    '%s of %s',
    '%s from %s',
    'Transfer of staff %s to %s',
    'Staff Transfer Approved of %s from %s',
    'Admission of student %s',
    'Withdraw request of %s',
    '%s in %s on %s',
    '%s applying for session %s in %s',
    'Results of %s');

DELETE FROM `translations` WHERE `en` IN (
    '%s with %s',
    'Transfer of student %s from %s',
    '%s in %s',
    '%s of %s',
    '%s from %s',
    'Transfer of staff %s to %s',
    'Staff Transfer Approved of %s from %s',
    'Admission of student %s',
    'Withdraw request of %s',
    '%s in %s on %s',
    '%s applying for session %s in %s',
    'Results of %s');

INSERT INTO `translations` (`en`, `created_user_id`, `created`) VALUES
('%s with %s', 1, NOW()),
('Transfer of student %s from %s', 1, NOW()),
('%s in %s', 1, NOW()),
('%s of %s', 1, NOW()),
('%s from %s', 1, NOW()),
('Transfer of staff %s to %s', 1, NOW()),
('Staff Transfer Approved of %s from %s', 1, NOW()),
('Admission of student %s', 1, NOW()),
('Withdraw request of %s', 1, NOW()),
('%s in %s on %s', 1, NOW()),
('%s applying for session %s in %s', 1, NOW()),
('Results of %s', 1, NOW());


-- POCOR-3699
-- `system_patches`
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3699', NOW());

-- `qualification_titles`
DROP TABLE IF EXISTS `qualification_titles`;
CREATE TABLE IF NOT EXISTS `qualification_titles` (
  `id` int(11) NOT NULL,
  `name` varchar(250) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `order` int(3) NOT NULL,
  `visible` int(1) NOT NULL DEFAULT '1',
  `editable` int(1) NOT NULL DEFAULT '1',
  `default` int(1) NOT NULL DEFAULT '0',
  `international_code` varchar(50) DEFAULT NULL,
  `national_code` varchar(50) DEFAULT NULL,
  `qualification_level_id` int(11) NULL COMMENT 'links to qualification_levels.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the titles of the qualifications';

ALTER TABLE `qualification_titles`
  ADD PRIMARY KEY (`id`),
  ADD KEY `qualification_level_id` (`qualification_level_id`);

ALTER TABLE `qualification_titles`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

# populate `qualification_titles` base on staff_qualifications table
INSERT INTO `qualification_titles` (`name`, `qualification_level_id`, `order`, `created_user_id`, `created`)
SELECT DISTINCT TRIM(qualification_title), qualification_level_id, 1, 1, now()
FROM `staff_qualifications`;

UPDATE `qualification_titles`
SET `order` = `id`;


-- `staff_qualifications_subjects`
DROP TABLE IF EXISTS `staff_qualifications_subjects`;
CREATE TABLE IF NOT EXISTS `staff_qualifications_subjects` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `staff_qualification_id` int(11) NOT NULL COMMENT 'links to staff_qualifications.id',
  `education_subject_id` int(11) NOT NULL COMMENT 'links to education_subjects.id'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains the subjects that can be taught by teachers';

ALTER TABLE `staff_qualifications_subjects`
  ADD PRIMARY KEY (`staff_qualification_id`, `education_subject_id`),
  ADD KEY `staff_qualification_id` (`staff_qualification_id`),
  ADD KEY `education_subject_id` (`education_subject_id`);


# populate base on `staff_qualifications` table
INSERT INTO `staff_qualifications_subjects`
SELECT sha2(CONCAT(`A`.`id`, ',', `B`.`education_subject_id`), '256'), `A`.`id`, `B`.`education_subject_id`
FROM `staff_qualifications` `A`
INNER JOIN `qualification_specialisation_subjects` `B`
  ON `B`.`qualification_specialisation_id` = `A`.`qualification_specialisation_id`;


-- `staff_qualifications_temp`
DROP TABLE IF EXISTS `staff_qualifications_temp`;
CREATE TABLE IF NOT EXISTS `staff_qualifications_temp` (
  `id` int(11) NOT NULL,
  `document_no` varchar(100) DEFAULT NULL,
  `graduate_year` int(4) DEFAULT NULL,
  `qualification_institution` varchar(255) NOT NULL,
  `gpa` varchar(5) DEFAULT NULL,
  `file_name` varchar(250) DEFAULT NULL,
  `file_content` longblob,
  `staff_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `qualification_title_id` int(11) NOT NULL COMMENT 'links to qualification_titles.id',
  `qualification_country_id` int(11) DEFAULT NULL COMMENT 'links to countries.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `staff_qualifications_temp`
  ADD PRIMARY KEY (`id`),
  ADD KEY `staff_id` (`staff_id`),
  ADD KEY `qualification_title_id` (`qualification_title_id`),
  ADD KEY `qualification_country_id` (`qualification_country_id`);

ALTER TABLE `staff_qualifications_temp`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

# re-insert data
INSERT INTO `staff_qualifications_temp`
SELECT `A`.`id`, `A`.`document_no`, `A`.`graduate_year`, `B`.`name`, `A`.`gpa`, `A`.`file_name`, `A`.`file_content`,
`A`.`staff_id`, `D`.`id`, `C`.`id`, `A`.`modified_user_id`, `A`.`modified`, `A`.`created_user_id`, `A`.`created`
FROM `staff_qualifications` `A`
LEFT JOIN `qualification_institutions` `B`
  ON `A`.`qualification_institution_id` = `B`.`id`
LEFT JOIN `countries` `C`
  ON `C`.`name` = `A`.`qualification_institution_country`
LEFT JOIN `qualification_titles` `D`
  ON (`D`.`qualification_level_id` = `A`.`qualification_level_id`
        AND TRIM(`D`.`name`) = TRIM(`A`.`qualification_title`));

-- backup old tables and rename new table.
RENAME TABLE `qualification_specialisations` TO `z_3699_qualification_specialisations`;
RENAME TABLE `qualification_specialisation_subjects` TO `z_3699_qualification_specialisation_subjects`;
RENAME TABLE `qualification_institutions` TO `z_3699_qualification_institutions`;
RENAME TABLE `staff_qualifications` TO `z_3699_staff_qualifications`;
RENAME TABLE `staff_qualifications_temp` TO `staff_qualifications`;

-- `labels`
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('5c3ddc98-0aec-11e7-b9c5-525400b263eb', 'Qualifications', 'file_content', 'Qualifications', 'Attachment', NULL, NULL, '1', NULL, NULL, '1', '2017-03-17 00:00:00');

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('a72ed550-1449-11e7-9f11-525400b263eb', 'Qualifications', 'education_subjects', 'Qualifications', 'Qualification Specialisation', NULL, NULL, '1', NULL, NULL, '1', '2017-03-29 00:00:00');


-- POCOR-3927
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3927', NOW());


-- staff_trainings
RENAME TABLE `staff_trainings` TO `z_3927_staff_trainings`;

CREATE TABLE `staff_trainings` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(60) NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `credit_hours` INT(5) NOT NULL DEFAULT '0',
    `file_name` VARCHAR(250) NULL,
    `file_content` LONGBLOB NULL,
    `completed_date` DATE DEFAULT NULL,
    `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id',
    `staff_training_category_id` int(11) NOT NULL DEFAULT '0' COMMENT 'links to staff_training_categories.id',
    `training_field_of_study_id` INT(11) NULL DEFAULT '0' COMMENT 'links to training_field_of_studies.id',
    `modified_user_id` INT(11) DEFAULT NULL,
    `modified` DATETIME DEFAULT NULL,
    `created_user_id` INT(11) NOT NULL,
    `created` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `staff_id` (`staff_id`),
    KEY `staff_training_category_id` (`staff_training_category_id`),
    KEY `training_field_of_study_id` (`training_field_of_study_id`),
    KEY `modified_user_id` (`modified_user_id`),
    KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains all training of staff';

-- insert value to the staff_training table from backup staff_training
INSERT INTO `staff_trainings` (`id`, `code`, `name`, `description`, `credit_hours`, `file_name`, `file_content`, `completed_date`, `staff_id`, `staff_training_category_id`, `training_field_of_study_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT `Z`.`id`, NULL, `cat`.`name`, NULL, 0, NULL, NULL, `Z`.`completed_date`, `Z`.`staff_id`, `Z`.`staff_training_category_id`, NULL, `Z`.`modified_user_id`, `Z`.`modified`, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3927_staff_trainings` AS `Z`
LEFT JOIN `staff_training_categories` AS `cat` on `cat`.`id` = `Z`.`staff_training_category_id`;

-- alerts Table
INSERT INTO `alerts` (`name`, `process_name`, `process_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('LicenseRenewal', 'AlertLicenseRenewal', NULL, NULL, NULL, '1', NOW());

-- alerts rule Table
ALTER TABLE `alert_rules` CHANGE `threshold` `threshold` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- security_functions
CREATE TABLE `z_3927_security_functions`  LIKE `security_functions`;
INSERT INTO `z_3927_security_functions` SELECT * FROM `security_functions`;

-- staff controller
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 3011;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 3012 AND 3038;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES  ('3011', 'Courses', 'Staff', 'Institutions', 'Staff - Training', 3000, 'Courses.index|Courses.view|Courses.download', 'Courses.edit', 'Courses.add', 'Courses.remove', NULL, 3038, '1', NULL, NULL, NULL, 1, NOW());

-- directories controller
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 7032;
UPDATE `security_functions` SET `order` = `order` - 1 WHERE `order` BETWEEN 7035 AND 7050;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('7032', 'Courses', 'Directories', 'Directory', 'Staff - Training', '7000', 'Courses.index|Courses.view|Courses.download', 'Courses.edit', 'Courses.add', 'Courses.remove', NULL, '7050', '1', NULL, NULL, NULL, '1', '2015-12-24 10:29:36');


-- POCOR-3876
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3876', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('b7b9aad6-1ff1-11e7-a840-525400b263eb', 'InstitutionClasses', 'multigrade', 'Institutions -> Classes', 'Multi-grade', NULL, NULL, '1', NULL, NULL, '1', '2017-04-13');


-- 3.9.11
UPDATE config_items SET value = '3.9.11' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
