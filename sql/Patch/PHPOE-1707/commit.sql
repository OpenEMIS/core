-- PATCH --
INSERT INTO `db_patches` VALUES ('PHPOE-1707', NOW());

UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '2000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '3000';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.view', ''), _edit = REPLACE(_view, '|Accounts.edit', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|Accounts.index', ''), _edit = REPLACE(_view, '|Accounts.index', '')  WHERE id = '5022';
UPDATE security_functions SET _view = REPLACE(_view, '|StudentAccount.view', ''), _edit = REPLACE(_view, '|StudentAccount.edit', '')  WHERE id = '1012';


INSERT INTO `security_functions` (`name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES
('Student Accounts', 'Students', 'Administration', 'Accounts', 5000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5000, 1, NULL, NULL, 1, NOW()),
('Institution Student Accounts', 'Institution', 'Administration', 'Accounts', 5000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5000, 1, NULL, NULL, 1, NOW()),
('Staff Accounts', 'Staff', 'Administration', 'Accounts', 5000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5000, 1, NULL, NULL, 1, NOW()),
('Institution Staff Accounts', 'Institution', 'Administration', 'Accounts', 5000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5000, 1, NULL, NULL, 1, NOW()),
('Guardian Accounts', 'Guardians', 'Administration', 'Accounts', 5000, 'Accounts.view', 'Accounts.edit', NULL, '', NULL, 5000, 1, NULL, NULL, 1, NOW());