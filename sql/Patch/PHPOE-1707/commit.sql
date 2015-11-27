-- PATCH --
INSERT INTO `db_patches` VALUES ('PHPOE-1707', NOW());

UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '2000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '3000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.index', ''), _edit = REPLACE(_view, '|Accounts.index', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|StudentAccount.view', ''), _edit = REPLACE(_view, '|StudentAccount.edit', '')  WHERE id = '1012';




-- Student Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'General' AND module = 'Students' AND name = 'Students' LIMIT 1;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(2020, 'Accounts', 'Students', 'Students', 'General', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 2020, 1, NULL, NULL, 1, NOW());

-- Staff Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'General' AND module = 'Staff' AND name = 'Staff' LIMIT 1;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(3027, 'Accounts', 'Staff', 'Staff', 'General', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 3027, 1, NULL, NULL, 1, NOW());

-- Guardian Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'General' AND module = 'Guardians' AND name = 'Guardians' LIMIT 1;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(4008, 'Accounts', 'Guardians', 'Guardians', 'General', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 4008, 1, NULL, NULL, 1, NOW());

-- Institution Student Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'Students' AND module = 'Institutions' AND name = 'Students' LIMIT 1;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(1032, 'Accounts', 'Institutions', 'Institutions', 'Students', @currentParentId, 'StudentAccount.view', 'StudentAccount.edit', NULL, '', NULL, 1032, 1, NULL, NULL, 1, NOW());

-- Institution Staff Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'Staff' AND module = 'Institutions' AND name = 'Staff' LIMIT 1;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(1033, 'Accounts', 'Institutions', 'Institutions', 'Staff', @currentParentId, 'StaffAccount.view', 'StaffAccount.edit', NULL, '', NULL, 1033, 1, NULL, NULL, 1, NOW());

-- User Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'Security' AND module = 'Administration' AND name = 'Users' LIMIT 1;
INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	(5042, 'Accounts', 'Securities', 'Administration', 'Security', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5042, 1, NULL, NULL, 1, NOW());

	-- SELECT category FROM security_functions group by category
	-- SELECT module FROM security_functions group by module
-- 2020	Accounts	Students	Students	General
-- 3027	Accounts	Staff	Staff	Accounts
-- 4008	Accounts	Guardians	Guardians	General
-- 1032	Accounts	Institutions	Institutions	Students
-- 1033	Accounts	Institutions	Institutions	Staff
-- 5042	Accounts	Securities	Administration	Security

-- select * from security_functions where id in (2020 ,3027 ,4008 ,1032 ,1033 ,5042);