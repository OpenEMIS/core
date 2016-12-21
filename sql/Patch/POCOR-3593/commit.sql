
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3593', NOW());

-- security_users
ALTER TABLE `security_users`
ADD COLUMN `nationality_id` INT NULL AFTER `date_of_death`,
ADD COLUMN `identity_type_id` INT NULL AFTER `nationality_id`,
ADD COLUMN `external_reference` VARCHAR(50) NULL AFTER `identity_number`;

UPDATE `security_users`
INNER JOIN `user_nationalities` ON `user_nationalities`.`security_user_id` = `security_users`.`id`
SET `security_users`.`nationality_id` = `user_nationalities`.`nationality_id`;

UPDATE `security_users`
INNER JOIN `nationalities`
	ON `nationalities`.`id` = `security_users`.`nationality_id`
INNER JOIN `user_identities`
	ON `user_identities`.`identity_type_id` = `nationalities`.`identity_type_id`
	AND `user_identities`.`security_user_id` = `security_users`.`id`
SET `security_users`.`identity_type_id` = `user_identities`.`identity_type_id`, `security_users`.`identity_number` = `user_identities`.`number`;

INSERT INTO `config_item_options` (`id`, `option_type`, `option`, `value`, `order`, `visible`) VALUES ('102', 'external_data_source_type', 'Custom', 'Custom', '3', '1');

CREATE TABLE `z_3593_config_items` LIKE `config_items`;

INSERT INTO `z_3593_config_items`
SELECT * FROM `config_items` WHERE `config_items`.`id` = 1002;

UPDATE `config_items` SET `value` = 'None' WHERE `id` = 1002;

CREATE TABLE `z_3593_external_data_source_attributes` LIKE `external_data_source_attributes`;

INSERT INTO `z_3593_external_data_source_attributes` SELECT * FROM `external_data_source_attributes`;

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`) VALUES (uuid(), 'ConfigExternalDataSource', 'client_id', 'Configuration > External Data Source', 'Client ID', '1', '1', NOW());

DELETE FROM `external_data_source_attributes`;
