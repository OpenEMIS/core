--
-- PHPOE-1799-2 commit.sql
--

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

-- fix config items
UPDATE `config_items` SET 
`code` = 'institution_area_level_id',
`type` = 'Institution'
WHERE `name` = 'Display Area Level';

-- fix field options
ALTER TABLE `field_options` DROP `old_id` ;
UPDATE `field_options` SET `plugin` = 'Institution', `code` = 'Providers' WHERE `code` = 'InstitutionSiteProviders';


