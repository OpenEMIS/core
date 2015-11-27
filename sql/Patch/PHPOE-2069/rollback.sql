-- area_administratives
ALTER TABLE `area_administratives` 
DROP COLUMN `is_main_country`;

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2069';

