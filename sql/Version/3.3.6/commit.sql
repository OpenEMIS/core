-- PATCH --
INSERT INTO `db_patches` VALUES ('PHPOE-1707', NOW());

UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_edit, '|Accounts.edit', '')  WHERE id = '2000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_edit, '|Accounts.edit', '')  WHERE id = '3000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_edit, '|Accounts.edit', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.index', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|StudentAccount.view', ''), _edit = REPLACE(_edit, '|StudentAccount.edit', '')  WHERE id = '1012';


-- Student Accounts
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(2020, 'Accounts', 'Students', 'Students', 'General', 2000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 2020, 1, NULL, NULL, 1, NOW());

-- Staff Accounts
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(3027, 'Accounts', 'Staff', 'Staff', 'General', 3000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 3027, 1, NULL, NULL, 1, NOW());

-- Guardian Accounts
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(4008, 'Accounts', 'Guardians', 'Guardians', 'General', 4000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 4008, 1, NULL, NULL, 1, NOW());

-- Institution Student Accounts
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(1032, 'Accounts', 'Institutions', 'Institutions', 'Students', 1012, 'StudentAccount.view', 'StudentAccount.edit', NULL, '', NULL, 1032, 1, NULL, NULL, 1, NOW());

-- Institution Staff Accounts
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(1033, 'Accounts', 'Institutions', 'Institutions', 'Staff', 1016, 'StaffAccount.view', 'StaffAccount.edit', NULL, '', NULL, 1033, 1, NULL, NULL, 1, NOW());

-- User Accounts
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(5042, 'Accounts', 'Securities', 'Administration', 'Security', 5022, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5042, 1, NULL, NULL, 1, NOW());

UPDATE `config_items` SET `value` = '3.3.6' WHERE `code` = 'db_version';
