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


-- POCOR-3115
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3115', NOW());


-- code here
-- Student
UPDATE `security_functions` SET `_add` = 'Students.add' WHERE id = 1012;
UPDATE `security_functions` SET `order` = `order`+1 WHERE `order` >= 1013 AND `order` <= 1043;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('1043', 'Student Profile', 'Institutions', 'Institutions', 'Students', '8', NULL, NULL, 'StudentUser.add', NULL, NULL, '1013', '1', NULL, NULL, '1', NOW());

-- Staff
UPDATE `security_functions` SET `_add` = 'Staff.add' WHERE id = 1016;
UPDATE `security_functions` SET `order` = `order`+1 WHERE `order` >= 1018 AND `order` <= 1044;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('1044', 'Staff Profile', 'Institutions', 'Institutions', 'Staff', '8', NULL, NULL, 'StaffUser.add', NULL, NULL, '1018', '1', NULL, NULL, '1', NOW());


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


-- 3.5.10
UPDATE config_items SET value = '3.5.10' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
