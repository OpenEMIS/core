-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2526', NOW());

-- authentication_type_attributes
ALTER TABLE `authentication_type_attributes` 
CHANGE COLUMN `value` `value` TEXT NULL DEFAULT NULL COMMENT '' ,
ADD COLUMN `modified` DATETIME NULL COMMENT '' AFTER `value`,
ADD COLUMN `modified_user_id` INT NULL COMMENT '' AFTER `modified`,
ADD COLUMN `created` DATETIME NULL DEFAULT NOW() COMMENT '' AFTER `modified_user_id`,
ADD COLUMN `created_user_id` INT NULL DEFAULT 1 COMMENT '' AFTER `created`;

ALTER TABLE `authentication_type_attributes` 
CHANGE COLUMN `created` `created` DATETIME NOT NULL COMMENT '' ,
CHANGE COLUMN `created_user_id` `created_user_id` INT(11) NOT NULL COMMENT '' ;


-- config_item_options
INSERT INTO `config_item_options` (`option_type`, `option`, `value`, `order`, `visible`) VALUES ('authentication_type', 'Saml2', 'Saml2', 4, 1);
