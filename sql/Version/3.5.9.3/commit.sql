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


-- 3.5.9.3
UPDATE config_items SET value = '3.5.9.3' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
