DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2168';

UPDATE security_functions SET _execute = REPLACE(_execute, '|StudentUser.excel', '')  WHERE id = 1012;
UPDATE security_functions SET _execute = REPLACE(_execute, '|StaffUser.excel', '')  WHERE id = 1016;

-- SELECT * FROM security_functions WHERE id IN (1012, 1016);

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2433';

DROP TABLE assessment_items;
RENAME TABLE z2423_assessment_items TO assessment_items;
DROP TABLE assessment_item_results;
RENAME TABLE z2423_assessment_item_results TO assessment_item_results;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2423';

UPDATE config_items SET value = '3.4.9' WHERE code = 'db_version';
