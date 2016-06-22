-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3115', NOW());


-- code here
-- Student
UPDATE `security_functions` SET `_add` = 'Students.add' WHERE id = 1012;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('1043', 'Student Profile', 'Institutions', 'Institutions', 'Students', '8', NULL, NULL, 'StudentUser.add', NULL, NULL, '1013', '1', NULL, NULL, '1', NOW());

-- Staff
UPDATE `security_functions` SET `_add` = 'Staff.add' WHERE id = 1016;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('1044', 'Staff Profile', 'Institutions', 'Institutions', 'Staff', '8', NULL, NULL, 'StaffUser.add', NULL, NULL, '1017', '1', NULL, NULL, '1', NOW());
