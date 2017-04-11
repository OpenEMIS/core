INSERT INTO `db_patches` VALUES ('PHPOE-1508', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `created_user_id`, `created`) 
VALUES (uuid(), 'Institutions', 'area_administrative_id', 'Institutions', 'Area (Administrative)', '0', NOW());

ALTER TABLE `institutions` 
CHANGE COLUMN `postal_code` `postal_code` VARCHAR(20) NULL;