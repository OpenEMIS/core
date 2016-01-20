DELETE FROM `labels` WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';

ALTER TABLE `institutions` 
CHANGE COLUMN `postal_code` `postal_code` VARCHAR(20) NOT NULL DEFAULT '';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1508';