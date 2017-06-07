-- POCOR-3961
-- db_patches
DROP TABLE `assessment_periods`;

ALTER TABLE `z_3961_assessment_periods`
RENAME TO  `assessment_periods` ;

UPDATE `security_functions` SET `_edit`='AssessmentPeriods.edit' WHERE `id`=5058;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3961';


-- POCOR-4013
-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|ClassStudents.excel' WHERE `id` = 1015;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4013';


-- 3.9.13.2
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.13.2' WHERE code = 'db_version';
