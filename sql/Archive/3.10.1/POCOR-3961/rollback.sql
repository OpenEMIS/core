-- db_patches
DROP TABLE `assessment_periods`;

ALTER TABLE `z_3961_assessment_periods`
RENAME TO  `assessment_periods` ;

UPDATE `security_functions` SET `_edit`='AssessmentPeriods.edit' WHERE `id`=5058;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3961';
