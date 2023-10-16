-- security_users
ALTER TABLE `security_users`
DROP COLUMN `identity_type_id`,
DROP COLUMN `nationality_id`,
DROP COLUMN `external_reference`;

DELETE FROM `config_item_options` WHERE `id` = 102;

DROP TABLE `external_data_source_attributes`;

ALTER TABLE `z_3593_external_data_source_attributes`
RENAME TO  `external_data_source_attributes` ;

UPDATE `config_items` INNER JOIN `z_3593_config_items` ON `z_3593_config_items`.`id` = `config_items`.`id` SET `config_items`.`value` = `z_3593_config_items`.`value`;

DROP TABLE `z_3593_config_items`;

DELETE FROM `labels` WHERE `module` = 'ConfigExternalDataSource' AND `field` = 'client_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3593';
