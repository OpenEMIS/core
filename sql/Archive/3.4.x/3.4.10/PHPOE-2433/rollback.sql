UPDATE security_functions SET _execute = REPLACE(_execute, '|StudentUser.excel', '')  WHERE id = 1012;
UPDATE security_functions SET _execute = REPLACE(_execute, '|StaffUser.excel', '')  WHERE id = 1016;

-- SELECT * FROM security_functions WHERE id IN (1012, 1016);

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2433';