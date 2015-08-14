CREATE TABLE IF NOT EXISTS `db_patches` (
  `issue` varchar(15) NOT NULL,
  PRIMARY KEY (`issue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- PHPOE-1821
INSERT INTO `db_patches` VALUES ('PHPOE-1821');

UPDATE `security_functions` SET
`_view` = 'AllClasses.index|AllClasses.view|Sections.index|Sections.view',
`_edit` = 'AllClasses.edit|Sections.edit',
`parent_id` = 1000
WHERE `id` = 1006;

UPDATE `security_functions` SET
`_view` = 'Sections.index|Sections.view',
`_edit` = 'Sections.edit'
WHERE `id` = 1007;

-- PHPOE-1815
INSERT INTO `db_patches` VALUES ('PHPOE-1815');

-- field_options
INSERT INTO `field_options` (`plugin`, `old_id`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Students', 0, 'StudentTransferReasons', 'Transfer Reasons', 'Student', NULL, 17, 1, NULL, NULL, 1, '0000-00-00 00:00:00');
INSERT INTO `field_option_values` (`name`, `order`, `visible`, `default`, `field_option_id`, `created_user_id`, `created`) VALUES
('Relocation', 1, 1, 1, (SELECT `field_options`.`id` FROM `field_options` WHERE `code` = 'StudentTransferReasons'), 1, NOW());

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` ADD `student_transfer_reason_id` INT(11) NOT NULL AFTER `previous_institution_id`;
ALTER TABLE `institution_student_transfers` ADD `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL AFTER `student_transfer_reason_id`;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'TransferRequests.add|TransferRequests.edit' WHERE `id` = 1020;
UPDATE `security_functions` SET `_execute` = 'TransferApprovals.edit' WHERE `id` = 1021;

-- PHPOE-1799
-- 30th July 2015
INSERT INTO `db_patches` VALUES ('PHPOE-1799');

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` ADD `academic_period_id` INT(11) NOT NULL AFTER `institution_id`;
ALTER TABLE `institution_student_transfers` CHANGE `education_programme_id` `education_grade_id` INT(11) NOT NULL;
ALTER TABLE `institution_student_transfers` CHANGE `comment` `comment` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NULL;

-- patch institution_site_grades
ALTER TABLE `institution_site_grades` DROP `status`;
ALTER TABLE `institution_site_grades` ADD `start_date` DATE NOT NULL AFTER `education_grade_id`, ADD `start_year` INT(4) NOT NULL AFTER `start_date`, ADD `end_date` DATE NULL AFTER `start_year`, ADD `end_year` INT(4) NULL AFTER `end_date`;

UPDATE `institution_site_grades` 
JOIN `institution_site_programmes` ON `institution_site_programmes`.`id` = `institution_site_grades`.`institution_site_programme_id`
SET `institution_site_grades`.`start_date` = `institution_site_programmes`.`start_date`,
`institution_site_grades`.`start_year` = YEAR(`institution_site_programmes`.`start_date`),
`institution_site_grades`.`end_date` = `institution_site_programmes`.`end_date`,
`institution_site_grades`.`end_year` = YEAR(`institution_site_programmes`.`end_year`);

-- insert student_statuses
INSERT INTO `student_statuses` (`id`, `code`, `name`) VALUES
(7, 'PROMOTED', 'Promoted'),
(8, 'REPEATED', 'Repeated');

-- new security_user_types table for saving different types of the same user
DROP TABLE IF EXISTS `security_user_types`;
CREATE TABLE IF NOT EXISTS `security_user_types` (
  `security_user_id` int(11) NOT NULL,
  `user_type` int(1) NOT NULL COMMENT '1 -> STUDENT, 2 -> STAFF, 3 -> GUARDIAN',
  PRIMARY KEY (`security_user_id`, `user_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `security_user_types` SELECT `security_user_id`, 1 FROM `institution_site_students` GROUP BY `security_user_id`;
INSERT INTO `security_user_types` SELECT `security_user_id`, 2 FROM `institution_site_staff` GROUP BY `security_user_id`;
INSERT INTO `security_user_types` SELECT `guardian_user_id`, 3 FROM `student_guardians` GROUP BY `guardian_user_id`;

-- institution_students
DROP TABLE IF EXISTS `institution_students`;
CREATE TABLE IF NOT EXISTS `institution_students` (
  `id` char(36) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `education_grade_id` int(11) NOT NULL,
  `academic_period_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `start_year` int(4) NOT NULL,
  `end_date` date DEFAULT NULL,
  `end_year` int(4) DEFAULT NULL,
  `institution_id` int(11) NOT NULL,
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `institution_students`
  ADD PRIMARY KEY (`id`), ADD KEY `student_id` (`student_id`), ADD KEY `education_grade_id` (`education_grade_id`), ADD KEY `academic_period_id` (`academic_period_id`), ADD KEY `institution_id` (`institution_id`);

-- patch institution_students
TRUNCATE TABLE `institution_students`;
INSERT INTO `institution_students` (`id`, `student_status_id`, `student_id`, `education_grade_id`, `academic_period_id`, `start_date`, `end_date`, `start_year`, `end_year`, `institution_id`, `created_user_id`, `created`) 
SELECT 
uuid(),
1,
`SectionStudents`.`security_user_id`, 
`SectionStudents`.`education_grade_id`,
`Sections`.`academic_period_id`, 
`Periods`.`start_date`, 
`Periods`.`end_date`,
YEAR(`Periods`.`start_date`),
YEAR(`Periods`.`end_date`),
`Sections`.`institution_site_id`,
1, NOW()
FROM `institution_site_section_students` AS `SectionStudents`
INNER JOIN `institution_site_sections` AS `Sections` ON `Sections`.`id` = `SectionStudents`.`institution_site_section_id`
INNER JOIN `institution_site_grades` AS `Grades` ON `Grades`.`education_grade_id` = `SectionStudents`.`education_grade_id`
INNER JOIN `academic_periods` AS `Periods` ON `Periods`.`id` = `Sections`.`academic_period_id`
WHERE `SectionStudents`.`security_user_id` <> 0
GROUP BY `SectionStudents`.`security_user_id`, `SectionStudents`.`education_grade_id`, `Sections`.`academic_period_id`, `Sections`.`institution_site_id`;

-- PHPOE-1799-2
ALTER TABLE `institution_site_section_students` DROP `student_category_id`;
ALTER TABLE `institution_site_section_students` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

-- Latest
ALTER TABLE `institution_site_class_students` CHANGE `security_user_id` `student_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

ALTER TABLE `institution_students` CHANGE `end_date` `end_date` DATE NOT NULL ;
ALTER TABLE `institution_students` CHANGE `end_year` `end_year` INT( 4 ) NOT NULL ;

-- insert permissions for grades
UPDATE `security_functions` SET
`id` = `id` + 1,
`order` = `order` + 1
WHERE `id` > 1004 AND `id` < 2000
ORDER BY `id` DESC;

INSERT INTO `security_functions`
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1005, 'Grades', 'Institutions', 'Institutions', 'Details', 1000, 'Grades.index', NULL, NULL, NULL, 'Grades.indexEdit', 1005, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- update role function mapping
UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` + 1
WHERE `security_function_id` > 1004 AND `security_function_id` < 2000
ORDER BY `security_function_id` DESC;

-- insert permissions for my/all subjects
UPDATE `security_functions` SET
`name` = 'All Subjects',
`_view` = 'AllSubjects.index|AllSubjects.view|Classes.index|Classes.view',
`_edit` = 'AllSubjects.edit|Classes.edit'
WHERE `id` = 1009;

UPDATE `security_functions` SET
`id` = `id` + 1,
`order` = `order` + 1
WHERE `id` > 1009 AND `id` < 2000
ORDER BY `id` DESC;

INSERT INTO `security_functions`
(`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(1010, 'My Subjects', 'Institutions', 'Institutions', 'Details', 1000, 'Classes.index|Classes.view', 'Classes.edit', NULL, NULL, NULL, 1010, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- update role function mapping
UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` + 1
WHERE `security_function_id` > 1009 AND `security_function_id` < 2000
ORDER BY `security_function_id` DESC;

INSERT INTO `labels` (`module`, `field`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Staff', 'security_user_id', NULL, 'Staff', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', '2015-08-07 00:00:00');
INSERT INTO `labels` (`module`, `field`, `code`, `en`, `ar`, `zh`, `es`, `fr`, `ru`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('Staff', 'institution_site_position_id', NULL, 'Position', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '1', '2015-08-07 00:00:00');

-- fix history permission
UPDATE `security_functions` SET
`_view` = 'History.index'
WHERE `id` = 1001;

-- fix custom fields
UPDATE `custom_modules` SET
`model` = 'Student.Students'
WHERE `code` = 'Student';

UPDATE `custom_modules` SET
`model` = 'Staff.Staff'
WHERE `code` = 'Staff';

ALTER TABLE `student_custom_forms_fields` CHANGE `name` `name` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `staff_custom_forms_fields` CHANGE `name` `name` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `infrastructure_custom_forms_fields` CHANGE `name` `name` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
ALTER TABLE `institution_custom_forms_fields` CHANGE `name` `name` VARCHAR( 250 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL ;
-- end fix

UPDATE `config_items` SET 
`code` = 'institution_area_level_id',
`type` = 'Institution'
WHERE `name` = 'Display Area Level';

ALTER TABLE `field_options` DROP `old_id` ;
UPDATE `field_options` SET `plugin` = 'Institution', `code` = 'Providers' WHERE `code` = 'InstitutionSiteProviders';

-- PHPOE-1762
INSERT INTO `db_patches` VALUES ('PHPOE-1762');
ALTER TABLE `institution_site_sections` CHANGE `section_number` `section_number` INT(11) NULL DEFAULT NULL COMMENT 'This column is being used to determine whether this section is a multi-grade or single-grade.';

-- DB version
UPDATE `config_items` SET `value` = '3.0.6' WHERE `code` = 'db_version';
-- end DB version
