-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3037', NOW());


-- code here
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('7047', 'New Guardian Profile', 'Directories', 'Directory', 'Students - Guardians', '7000', NULL, NULL, 'StudentGuardianUser.add', NULL, NULL, '7047', '1', NULL, NULL, '1', NOW());