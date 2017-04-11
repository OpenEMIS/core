DROP TABLE IF EXISTS `api_authorizations`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2028';
