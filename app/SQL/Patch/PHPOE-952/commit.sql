-- Fix Area
UPDATE `navigations` SET `pattern` = 'index$|levels|edit|AreaEducation|Area' WHERE `controller` = 'Areas' AND `action` = 'index';

-- Students
UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStudent.index' WHERE `controller` = 'Students' AND `action` = 'index';

-- Delete Students link from Institution Sites
DELETE FROM `navigations` WHERE `id` = 10;

-- Insert 'Add existing Student' link and reorder
SET @ordering := 0;
SELECT `order` + 1 into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'add';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Student', 'Students', 'Students', NULL, 'Add existing Student', 'InstitutionSiteStudent/add', 'InstitutionSiteStudent.add', NULL, 60, 0, @ordering, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`, `created`) VALUES
(56, 'StudentStatus', 'Status', 'Student', 58, 0, 1, '0000-00-00 00:00:00');

ALTER TABLE `field_option_values` ADD `old_id` INT( 11 ) NULL AFTER `national_code` ;

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `old_id`, `field_option_id`, `created_user_id`, `created`)
SELECT NULL, `name`, `order`, `visible`, 0, 0, `international_code`, `national_code`, `id`, 56, 1, '0000-00-00 00:00:00' FROM `student_statuses`;
DROP TABLE IF EXISTS `student_statuses`;

UPDATE `institution_site_students` SET `student_status_id` = (SELECT `id` from `field_option_values` WHERE `field_option_id` = 56 and `old_id` = `student_status_id`);

ALTER TABLE `institution_site_students` ADD `institution_site_id` INT( 11 ) NOT NULL AFTER `student_status_id` , ADD INDEX ( `institution_site_id` ) ;
ALTER TABLE `institution_site_students` ADD `education_programme_id` INT( 11 ) NOT NULL AFTER `institution_site_id` , ADD INDEX ( `education_programme_id` ) ;

UPDATE `institution_site_students`
JOIN `institution_site_programmes`
	ON `institution_site_programmes`.`id` = `institution_site_students`.`institution_site_programme_id`
SET `institution_site_students`.`institution_site_id` = `institution_site_programmes`.`institution_site_id`,
`institution_site_students`.`education_programme_id` = `institution_site_programmes`.`education_programme_id`;

-- Insert 'Programmes' link to Student
SELECT `order` + 1 into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'guardians';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Student', 'Students', 'Students', 'DETAILS', 'Programmes', 'Programme', 'Programme', NULL, 62, 0, @ordering, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Staff
UPDATE `navigations` SET `pattern` = 'index$|advanced|InstitutionSiteStaff.index' WHERE `controller` = 'Staff' AND `action` = 'index';
SET @ordering := 0;
SELECT `order` + 1 into @ordering FROM `navigations` WHERE `controller` = 'Staff' AND `action` = 'add';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Staff', 'Staff', 'Staff', NULL, 'Add existing Staff', 'InstitutionSiteStaff/add', 'InstitutionSiteStaff.add', NULL, 87, 0, @ordering, 1, NULL, NULL, 1, '0000-00-00 00:00:00');

-- Staff Status
SET @staffStatusId := 0;
SELECT MAX(`id`)+1 INTO @staffStatusId FROM `field_options`;

INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`, `created`) VALUES
(@staffStatusId, 'StaffStatus', 'Status', 'Staff', @staffStatusId, 0, 1, '0000-00-00 00:00:00');

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `old_id`, `field_option_id`, `created_user_id`, `created`)
SELECT NULL, `name`, `order`, `visible`, 0, 0, `international_code`, `national_code`, `id`, @staffStatusId, 1, '0000-00-00 00:00:00' FROM `staff_statuses`;
UPDATE `field_option_values` SET `default` = 1 WHERE `field_option_id` = @staffStatusId ORDER BY `order` LIMIT 1;
DROP TABLE IF EXISTS `staff_statuses`;

UPDATE `institution_site_staff` SET `staff_status_id` = (SELECT `id` from `field_option_values` WHERE `field_option_id` = @staffStatusId and `old_id` = `staff_status_id`);

-- Change FTE to decimal
ALTER TABLE `institution_site_staff` CHANGE `FTE` `FTE` DECIMAL( 5, 2 ) NULL DEFAULT NULL ;

-- Delete Staff link from Institution Sites
DELETE FROM `navigations` WHERE `id` = 11;

-- Institution Site Positions
UPDATE `navigations` SET `action` = 'InstitutionSitePosition', `pattern` = 'InstitutionSitePosition' WHERE `controller` = 'InstitutionSites' AND `title` = 'Positions';
UPDATE `security_functions` SET 
`_view` = 'InstitutionSitePosition|InstitutionSitePosition.index|InstitutionSitePosition.view',
`_edit` = '_view:InstitutionSitePosition.edit|InstitutionSitePosition.staffEdit',
`_add` = '_view:InstitutionSitePosition.add',
`_delete` = '_view:InstitutionSitePosition.remove|InstitutionSitePosition.staffDelete'
WHERE `controller` =  'InstitutionSites' AND `name` = 'Positions';

-- Student Security
UPDATE `security_functions` SET `_view` = 'view|InstitutionSiteStudent.index', `_add` = '_view:add|InstitutionSiteStudent.add' WHERE `controller` = 'Students' AND `parent_id` = -1;
SET @ordering := 0;
SET @maxId := 0;
SELECT `order` + 1 into @ordering FROM `security_functions` WHERE `controller` = 'Students' AND `name` = 'Guardians';
SELECT MAX(`id`) + 1 INTO @maxId FROM `security_functions`;
UPDATE `security_functions` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `created_user_id`, `created`) VALUES
(@maxId, 'Programmes', 'Students', 'Students', 'Details', 66, 'Programme|Programme.index|Programme.view', '_view:Programme.edit', NULL, '_view:Programme.remove', NULL, @ordering, 1, 1, NOW());

-- Staff Security
UPDATE `security_functions` SET `_view` = 'view|InstitutionSiteStaff.index', `_add` = '_view:add|InstitutionSiteStaff.add' WHERE `controller` = 'Staff' AND `parent_id` = -1;

-- Update missing permissions
UPDATE `security_functions` set `_execute` = '_view:compile' WHERE `controller` = 'Translations' and `name` = 'Translations';
UPDATE `security_functions` SET `_view` = 'index|view|fetchYearbookImage', `_edit` = '_view:edit|autoGeneratedEdit|save' WHERE `controller` = 'Config' and `parent_id` = -1;

