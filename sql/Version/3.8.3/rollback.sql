-- POCOR-3593
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

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3593';


-- POCOR-3632
-- security_functions
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5058;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` < 6000 AND `order` > 5010;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3632';


-- POCOR-3633
-- security_function
UPDATE `security_functions` SET `_edit`='StudentUser.edit' WHERE `id`='2000';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3633';


-- POCOR-3623
-- restore user_identities
INSERT INTO `user_identities`
SELECT * FROM `z_3623_user_identities`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3623';


-- 3.8.2
UPDATE config_items SET value = '3.8.2' WHERE code = 'db_version';
