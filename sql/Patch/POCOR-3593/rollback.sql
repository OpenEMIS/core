-- security_users
ALTER TABLE `security_users`
DROP COLUMN `identity_type_id`,
DROP COLUMN `nationality_id`,
DROP COLUMN `external_reference`;

DELETE FROM `config_item_options` WHERE `id` = 102;

DROP TABLE `external_data_source_attributes`;

ALTER TABLE `z_3593_external_data_source_attributes` 
RENAME TO  `external_data_source_attributes` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3593';
