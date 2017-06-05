-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|ClassStudents.excel' WHERE `id` = 1015;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4013';
