-- code here
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('5032', 'Backup', 'Database', 'Administration', 'Database', '5000', NULL, NULL, NULL, NULL, 'backup', '5032', '1', '1', NULL, '1', NOW());
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('5033', 'Restore', 'Database', 'Administration', 'Database', '5000', NULL, NULL, NULL, NULL, 'restore', '5033', '1', '1', NULL, '1', NOW());


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3068';