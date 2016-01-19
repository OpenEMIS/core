INSERT INTO `db_patches` VALUES ('PHPOE-2433', NOW());

UPDATE security_functions SET _execute = concat(_execute, '|StudentUser.excel')  WHERE id = 1012;
UPDATE security_functions SET _execute = concat(_execute, '|StaffUser.excel')  WHERE id = 1016;

-- SELECT * FROM security_functions WHERE id IN (1012, 1016);

