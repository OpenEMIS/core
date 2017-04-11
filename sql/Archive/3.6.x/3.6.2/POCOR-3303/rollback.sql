-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel' WHERE `id` = 1015;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3303';