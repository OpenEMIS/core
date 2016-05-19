-- delete tables
DROP TABLE IF EXISTS `survey_responses`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2979';
