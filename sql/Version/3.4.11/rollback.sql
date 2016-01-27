DELETE FROM `labels` WHERE `module` = 'Institutions' AND `field` = 'area_administrative_id';

ALTER TABLE `institutions` 
CHANGE COLUMN `postal_code` `postal_code` VARCHAR(20) NOT NULL DEFAULT '';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1508';

-- security_functions
UPDATE `security_functions` SET `_execute`='Promotion.index|Promotion.add' WHERE `id`=1005;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2484';

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `option` = 'Google';

-- config_items
UPDATE `config_items` SET `value` = 'Local' WHERE `type` = 'Authentication' AND `code` = 'authentication_type';

-- authentication_type_attributes
DROP TABLE `authentication_type_attributes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2505';

UPDATE config_items SET value = '3.4.10' WHERE code = 'db_version';
