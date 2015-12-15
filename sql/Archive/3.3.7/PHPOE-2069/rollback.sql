-- area_administratives
ALTER TABLE `area_administratives` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NOT NULL COMMENT '',
DROP COLUMN `is_main_country`;

ALTER TABLE `areas` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NOT NULL COMMENT '';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2069';

