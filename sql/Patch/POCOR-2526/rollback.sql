-- authentication_type_attributes
ALTER TABLE `authentication_type_attributes` 
DROP COLUMN `created_user_id`,
DROP COLUMN `created`,
DROP COLUMN `modified_user_id`,
DROP COLUMN `modified`,
CHANGE COLUMN `value` `value` VARCHAR(100) NULL DEFAULT NULL COMMENT '' ;

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'authentication_type' AND `option` = 'Saml2';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2526';