
UPDATE security_functions SET _view = CONCAT(_view, '|Accounts.view'), _edit = CONCAT(_edit, '|Accounts.edit')  WHERE id = '2000';
UPDATE security_functions SET _view = CONCAT(_view, '|Accounts.view'), _edit = CONCAT(_edit, '|Accounts.edit')  WHERE id = '3000';
UPDATE security_functions SET _view = CONCAT(_view, '|Accounts.index|Accounts.view'), _edit = CONCAT(_edit, '|Accounts.edit')  WHERE id = '5022';

UPDATE security_functions SET _view = CONCAT(_view, '|StudentAccount.view'), _edit = CONCAT(_edit, '|StudentAccount.edit')  WHERE id = '1012';


DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Students' AND `module` = 'Students' AND `category` = 'General';
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'General' AND module = 'Students' AND name = 'Students' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE module = 'Students' AND security_functions.order > @currentOrder;

DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Staff' AND `module` = 'Staff' AND `category` = 'General';
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'General' AND module = 'Staff' AND name = 'Staff' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE module = 'Staff' AND security_functions.order > @currentOrder;

DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Guardians' AND `module` = 'Guardians' AND `category` = 'General';
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'General' AND module = 'Guardians' AND name = 'Guardians' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE module = 'Guardians' AND security_functions.order > @currentOrder;

DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Students';
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'Students' AND module = 'Institutions' AND name = 'Students' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE module = 'Institutions' AND security_functions.order > @currentOrder;

DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Institutions' AND `module` = 'Institutions' AND `category` = 'Staff';
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'Staff' AND module = 'Institutions' AND name = 'Staff' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE module = 'Institutions' AND security_functions.order > @currentOrder;

DELETE FROM security_functions WHERE `name` = 'Accounts' AND `controller` = 'Securities' AND `module` = 'Administration' AND `category` = 'Security';
SELECT security_functions.order INTO @currentOrder FROM security_functions WHERE category = 'Security' AND module = 'Administration' AND name = 'Users' LIMIT 1;
UPDATE security_functions SET security_functions.order = security_functions.order - 1 WHERE module = 'Administration' AND security_functions.order > @currentOrder;



 -- PATCH --
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1707';
