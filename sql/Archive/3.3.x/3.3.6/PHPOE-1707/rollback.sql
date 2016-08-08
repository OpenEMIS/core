
UPDATE security_functions SET _view = CONCAT(_view, '|Accounts.view'), _edit = CONCAT(_edit, '|Accounts.edit')  WHERE id = '2000';
UPDATE security_functions SET _view = CONCAT(_view, '|Accounts.view'), _edit = CONCAT(_edit, '|Accounts.edit')  WHERE id = '3000';
UPDATE security_functions SET _view = CONCAT(_view, '|Accounts.index|Accounts.view'), _edit = CONCAT(_edit, '|Accounts.edit')  WHERE id = '5022';

UPDATE security_functions SET _view = CONCAT(_view, '|StudentAccount.view'), _edit = CONCAT(_edit, '|StudentAccount.edit')  WHERE id = '1012';

DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Students' AND `module` = 'Students' AND `category` = 'General';
DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'General';
DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Guardians' AND `module` = 'Guardians' AND `category` = 'General';
DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Students';
DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff';
DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Securities' AND `module` = 'Administration' AND `category` = 'Security';



 -- PATCH --
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1707';
