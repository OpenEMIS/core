-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` = 5027;

-- institution_site_surveys
ALTER TABLE `institution_site_surveys` CHANGE `status` `status` INT(1) NOT NULL DEFAULT '0' COMMENT '0 -> New, 1 -> Draft, 2 -> Completed';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1807';
