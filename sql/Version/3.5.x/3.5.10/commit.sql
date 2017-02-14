-- POCOR-3115
DELETE FROM `security_role_functions` WHERE `security_function_id` = 1043;
INSERT INTO `security_role_functions` (`id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, '0', '0', '1', '0', '0', `security_role_id`, '1043', NULL, NULL, '1', NOW()
    FROM `security_role_functions`
    WHERE `security_function_id` = 1012 AND `_add` = '1';

DELETE FROM `security_role_functions` WHERE `security_function_id` = 1044;
INSERT INTO `security_role_functions` (`id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `security_role_id`, `security_function_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT NULL, '0', '0', '1', '0', '0', `security_role_id`, '1044', NULL, NULL, '1', NOW()
    FROM `security_role_functions`
    WHERE `security_function_id` = 1016 AND `_add` = '1';


-- POCOR-2634
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-2634', NOW());

-- custom_field_types
UPDATE `custom_field_types` SET `is_mandatory` = 1 WHERE `code` = 'DROPDOWN';


-- POCOR-3093
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3093', NOW());

-- update NULL value on default record.
UPDATE `academic_periods`
SET `end_date` = '0000-00-00',
`end_year` = '0'
WHERE `code` = 'All'
AND `name` = 'All Data';

-- update NULL value on user record.
UPDATE `academic_periods`
SET `end_date` = DATE_FORMAT(start_date ,'%Y-12-31')
WHERE `end_date` IS NULL;

UPDATE `academic_periods`
SET `end_year` = start_year
WHERE `end_year` IS NULL;

-- alter both field to not NULL
ALTER TABLE `academic_periods` CHANGE `end_date` `end_date` DATE NOT NULL;
ALTER TABLE `academic_periods` CHANGE `end_year` `end_year` INT(4) NOT NULL;


-- POCOR-3067
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3067', NOW());


-- code here
-- Directoriy module
UPDATE `security_functions` SET `name` = 'Guardian Relation' WHERE id = 7009;
UPDATE `security_functions` SET `order` = 7048 WHERE id = 7009;

UPDATE `security_functions` SET `name` = 'Guardian Profile' WHERE id = 7047;
UPDATE `security_functions` SET `_view` = 'StudentGuardianUser.index|StudentGuardianUser.view' WHERE id = 7047;
UPDATE `security_functions` SET `_edit` = 'StudentGuardianUser.edit' WHERE id = 7047;
UPDATE `security_functions` SET `order` = 7009 WHERE id = 7047;

UPDATE `security_functions` SET `order` = 7047 WHERE id = 7009;

-- institutions module
UPDATE `security_functions` SET `name` = 'Guardian Relation' WHERE id = 2010;
UPDATE `security_functions` SET `order` = 2030 WHERE id = 2010;
UPDATE `security_functions` SET `category` = 'Students - Guardians' WHERE id = 2010;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('2029', 'Guardian Profile', 'Students', 'Institutions', 'Students - Guardians', '2000', 'GuardianUser.index|GuardianUser.view', 'GuardianUser.edit', 'GuardianUser.add', NULL, NULL, '2002', '1', NULL, NULL, '1', NOW());

UPDATE `security_functions` SET `order` = 2029 WHERE id = 2010;

-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2467', NOW());


-- Guardian Relations
DROP TABLE IF EXISTS `guardian_relations`;
CREATE TABLE `guardian_relations` LIKE `institution_network_connectivities`;
INSERT INTO `guardian_relations`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'GuardianRelations');

UPDATE `field_options` SET `plugin` = 'Student' WHERE `code` = 'GuardianRelations';


-- Staff Type
DROP TABLE IF EXISTS `staff_types`;
CREATE TABLE `staff_types` LIKE `institution_network_connectivities`;
INSERT INTO `staff_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTypes');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffTypes';


-- Staff Leave Type
DROP TABLE IF EXISTS `staff_leave_types`;
CREATE TABLE `staff_leave_types` LIKE `institution_network_connectivities`;
INSERT INTO `staff_leave_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffLeaveTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffLeaveTypes');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffLeaveTypes';
UPDATE `workflow_models` SET `filter` = 'Staff.StaffLeaveTypes' WHERE `model` = 'Staff.Leaves';
UPDATE `import_mapping` SET `lookup_plugin` = 'Staff' WHERE `model` = 'Institution.Staff' AND `column_name` = 'staff_type_id';

-- Added by Jeff to fix incorrect type
ALTER TABLE `contact_types` CHANGE `contact_option_id` `contact_option_id` INT(11) NOT NULL;
ALTER TABLE `institution_staff_assignments` CHANGE `institution_id` `institution_id` INT(11) NOT NULL;

-- 3.5.10
UPDATE config_items SET value = '3.5.10' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
