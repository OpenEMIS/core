-- assessment_periods
DROP TABLE IF EXISTS `assessment_periods`;
RENAME TABLE `z_3683_assessment_periods` TO `assessment_periods`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3683';
