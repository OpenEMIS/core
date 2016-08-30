-- code here
DELETE FROM `config_items` WHERE `type` = 'Administrative Boundaries';
DROP TABLE IF EXISTS `config_administrative_boundaries`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3257';


