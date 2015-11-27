INSERT INTO `db_patches` VALUES ('PHPOE-2069', NOW());

ALTER TABLE `area_administratives` 
ADD COLUMN `is_main_country` INT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `name`;