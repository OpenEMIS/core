UPDATE `navigations` SET `action` = 'create', `pattern` = 'create' WHERE `controller` = 'Students' AND `action` = 'add';
UPDATE `security_functions` SET `_add` = 'create' WHERE `controller` = 'Students' AND `category` = 'General' AND `_add` = 'add';

-- Insert 'Add existing Student' link and reorder
SET @ordering := 0;
SELECT `order` + 1 into @ordering FROM `navigations` WHERE `controller` = 'Students' AND `action` = 'create';
UPDATE `navigations` SET `order` = `order` + 1 WHERE `order` >= @ordering;
INSERT INTO `navigations` (`id`, `module`, `plugin`, `controller`, `header`, `title`, `action`, `pattern`, `attributes`, `parent`, `is_wizard`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
(NULL, 'Student', 'Students', 'Students', NULL, 'Add existing Student', 'add', 'add$', NULL, 60, 0, @ordering, 1, NULL, NULL, 1, '0000-00-00 00:00:00'),


INSERT INTO `field_options` (`id`, `code`, `name`, `parent`, `order`, `visible`, `created_user_id`, `created`) VALUES
(56, 'StudentStatus', 'Status', 'Student', 58, 0, 1, '0000-00-00 00:00:00');

ALTER TABLE `field_option_values` ADD `old_id` INT( 11 ) NULL AFTER `national_code` ;

INSERT INTO `field_option_values` (`id`, `name`, `order`, `visible`, `editable`, `default`, `international_code`, `national_code`, `old_id`, `field_option_id`, `created_user_id`, `created`)
SELECT
NULL,
`name`,
`order`,
`visible`,
0,
0,
`international_code`,
`national_code`,
`id`,
56,
1,
'0000-00-00 00:00:00'
FROM `student_statuses`;

UPDATE `institution_site_students` SET `student_status_id` = (SELECT `id` from `field_option_values` WHERE `field_option_id` = 56 and `old_id` = `student_status_id`);

UPDATE `navigations` SET `action` = 'InstitutionSiteStudent', `pattern` = 'InstitutionSiteStudent' WHERE `controller` = 'InstitutionSites' AND `action` = 'students';

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
(NULL, 'Student', 'Students', 'Students', 'DETAILS', 'Programmes', 'Programme', 'Programme', NULL, 62, 0, @ordering, 1, NULL, NULL, 1, '0000-00-00 00:00:00')

-- Delete Students link from Institution Sites
DELETE FROM `navigations` WHERE `id` = 10;

-- add security function for new link
