-- db_patches
DROP TABLE `assessment_periods`;

ALTER TABLE `z_3961_assessment_periods`
RENAME TO  `assessment_periods` ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3961';
