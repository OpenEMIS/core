-- PATCH --
INSERT INTO `db_patches` VALUES ('PHPOE-1707', NOW());

UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '2000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '3000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.index', ''), _edit = REPLACE(_view, '|Accounts.index', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|StudentAccount.view', ''), _edit = REPLACE(_view, '|StudentAccount.edit', '')  WHERE id = '1012';




-- Student Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'General' AND module = 'Students' AND name = 'Students' LIMIT 1;
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'General' AND module = 'Students' AND name = 'Students' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE module = 'Students' AND security_functions.order > @currentOrder;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	('Accounts', 'Students', 'Students', 'General', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, @currentOrder+1, 1, NULL, NULL, 1, NOW());

-- Staff Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'General' AND module = 'Staff' AND name = 'Staff' LIMIT 1;
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'General' AND module = 'Staff' AND name = 'Staff' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE module = 'Staff' AND security_functions.order > @currentOrder;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	('Accounts', 'Staff', 'Staff', 'General', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, @currentOrder+1, 1, NULL, NULL, 1, NOW());

-- Guardian Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'General' AND module = 'Guardians' AND name = 'Guardians' LIMIT 1;
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'General' AND module = 'Guardians' AND name = 'Guardians' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE module = 'Guardians' AND security_functions.order > @currentOrder;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	('Accounts', 'Guardians', 'Guardians', 'General', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, @currentOrder+1, 1, NULL, NULL, 1, NOW());

-- Institution Student Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'Students' AND module = 'Institutions' AND name = 'Students' LIMIT 1;
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'Students' AND module = 'Institutions' AND name = 'Students' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE module = 'Institutions' AND security_functions.order > @currentOrder;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	('Accounts', 'Institutions', 'Institutions', 'Students', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, @currentOrder+1, 1, NULL, NULL, 1, NOW());

-- Institution Staff Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'Staff' AND module = 'Institutions' AND name = 'Staff' LIMIT 1;
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'Staff' AND module = 'Institutions' AND name = 'Staff' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE module = 'Institutions' AND security_functions.order > @currentOrder;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	('Accounts', 'Institutions', 'Institutions', 'Staff', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, @currentOrder+1, 1, NULL, NULL, 1, NOW());

-- User Accounts
SELECT id INTO @currentParentId FROM security_functions WHERE category = 'Security' AND module = 'Administration' AND name = 'Users' LIMIT 1;
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'Security' AND module = 'Administration' AND name = 'Users' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order + 1 WHERE module = 'Administration' AND security_functions.order > @currentOrder;
INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES 
	('Accounts', 'Securities', 'Administration', 'Security', @currentParentId, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, @currentOrder+1, 1, NULL, NULL, 1, NOW());

	-- SELECT category FROM security_functions group by category
	-- SELECT module FROM security_functions group by module