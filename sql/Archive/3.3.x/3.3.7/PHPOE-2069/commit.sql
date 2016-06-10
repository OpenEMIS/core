INSERT INTO `db_patches` VALUES ('PHPOE-2069', NOW());

ALTER TABLE `area_administratives` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL COMMENT '',
ADD COLUMN `is_main_country` INT(1) NOT NULL DEFAULT 0 COMMENT '' AFTER `name`;

UPDATE `area_administratives`
INNER JOIN (
	SELECT `id`
    FROM `area_administratives`
    WHERE `area_administrative_level_id` = 1
    ORDER BY `id`
    LIMIT 1
) `tmp` ON `area_administratives`.`id` = `tmp`.`id`
SET `is_main_country` = 1;

ALTER TABLE `areas` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NULL COMMENT '';