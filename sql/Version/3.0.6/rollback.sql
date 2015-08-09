--
-- PHPOE-1762 rollback.sql
--

ALTER TABLE `institution_site_sections` CHANGE `section_number` `section_number` INT(11) NULL DEFAULT NULL COMMENT '';
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1762';

--
-- PHPOE-1799-2
--

ALTER TABLE `institution_site_section_students` ADD `student_category_id` INT(11) NOT NULL AFTER `education_grade_id`;
ALTER TABLE `institution_site_section_students` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;

-- Latest
ALTER TABLE `institution_site_class_students` CHANGE `student_id` `security_user_id` INT(11) NOT NULL;

-- rollback permissions for Grades
DELETE FROM `security_functions` WHERE `id` = 1005;

UPDATE `security_functions` SET
`id` = `id` - 1,
`order` = `order` - 1
WHERE `id` > 1004 AND `id` < 2000;

UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` - 1
WHERE `security_function_id` > 1004 AND `security_function_id` < 2000;

-- rollback permission for my subjects
DELETE FROM `security_functions` WHERE `id` = 1010;

UPDATE `security_functions` SET
`id` = `id` - 1,
`order` = `order` - 1
WHERE `id` > 1009 AND `id` < 2000;

UPDATE `security_role_functions` SET
`security_function_id` = `security_function_id` - 1
WHERE `security_function_id` > 1009 AND `security_function_id` < 2000;


DELETE FROM `labels` WHERE `module` = 'Staff' AND `field` = 'security_user_id';
DELETE FROM `labels` WHERE `module` = 'Staff' AND `field` = 'institution_site_position_id';

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` DROP `academic_period_id`;
ALTER TABLE `institution_student_transfers` CHANGE `education_grade_id` `education_programme_id` INT(11) NOT NULL;

-- institution_students
DROP TABLE IF EXISTS `institution_students`;

-- institution_site_grades
ALTER TABLE `institution_site_grades` ADD `status` INT(1) NOT NULL AFTER `id`;
ALTER TABLE `institution_site_grades` DROP `start_date`;
ALTER TABLE `institution_site_grades` DROP `start_year`;
ALTER TABLE `institution_site_grades` DROP `end_date`;
ALTER TABLE `institution_site_grades` DROP `end_year`;

-- student_statuses
DELETE FROM `student_statuses` WHERE `code` IN ('PROMOTED', 'REPEATED');

DROP TABLE IF EXISTS `security_user_types`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1799';

-- field_options
DELETE FROM `field_options` WHERE `plugin` = 'Students' AND `code` = 'StudentTransferReasons';
DELETE FROM `field_option_values` WHERE NOT EXISTS (SELECT 1 FROM `field_options` WHERE `field_options`.`id` = `field_option_values`.`field_option_id`);

-- institution_student_transfers
ALTER TABLE `institution_student_transfers` DROP `student_transfer_reason_id`;
ALTER TABLE `institution_student_transfers` DROP `comment`;

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Transfers.add' WHERE `id` = 1020;
UPDATE `security_functions` SET `_execute` = 'Transfers.edit' WHERE `id` = 1021;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1815';

-- PHPOE-1821
UPDATE `security_functions` SET
`_view` = 'Sections.index|Sections.view',
`_edit` = 'Sections.edit',
`parent_id` = 1000
WHERE `id` = 1006;

UPDATE `security_functions` SET
`_view` = 'MyClasses.index|MyClasses.view|Sections.index|Sections.view',
`_edit` = 'MyClasses.edit|Sections.edit'
WHERE `id` = 1007;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1821';

