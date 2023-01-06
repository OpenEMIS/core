-- POCOR-3257
-- security functions
UPDATE `security_functions` SET `_view` = 'index|view', `_edit` = 'edit' WHERE `name` = 'Configurations';

DELETE FROM `config_items` WHERE `code` = 'area_api';

-- Auto_increment
ALTER TABLE `security_functions` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `config_items` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3257';


-- POCOR-3258
-- drop config_product_lists table
DROP TABLE `config_product_lists`;

-- config_items
DELETE FROM `config_items` WHERE `type` = 'Product Lists';

UPDATE `config_items`
INNER JOIN `z_3258_config_items` ON `z_3258_config_items`.`code` = `config_items`.`code`
SET `config_items`.`id` = `z_3258_config_items`.`id`;

DROP TABLE `z_3258_config_items`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3258';


-- POCOR-3110
-- import_mapping
DELETE FROM `import_mapping` WHERE `model` = 'Institution.Staff' AND `column_name` = 'end_date';
UPDATE `import_mapping` SET `order`='2' WHERE `model`='Institution.Staff' AND `column_name`='start_date';
UPDATE `import_mapping` SET `order`='1' WHERE `model`='Institution.Staff' AND `column_name`='institution_position_id';
UPDATE `import_mapping` SET `order`='5' WHERE `model`='Institution.Staff' AND `column_name`='staff_type_id';
UPDATE `import_mapping` SET `order`='6' WHERE `model`='Institution.Staff' AND `column_name`='staff_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3110';


-- POCOR_3319
-- replace date-closed and year-closed with backup values
UPDATE `institutions`
INNER JOIN `z_3319_institutions`
ON `institutions`.`id` = `z_3319_institutions`.`id`
SET `institutions`.`date_closed` = `z_3319_institutions`.`date_closed`, `institutions`.`year_closed` = `z_3319_institutions`.`year_closed`;

-- remove back up table
DROP TABLE`z_3319_institutions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3319';


-- POCOR-3193
-- replace deleted records into institution_subjects table
INSERT INTO `institution_subjects`
SELECT * FROM `z_3193_institution_subjects`;

-- remove backup table
DROP TABLE `z_3193_institution_subjects`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3193';


-- POCOR-3272
-- replace values with original values from backup user contacts table
UPDATE `user_contacts`
INNER JOIN `z_3272_user_contacts`
ON `user_contacts`.`id` = `z_3272_user_contacts`.`id`
SET `user_contacts`.`value` = `z_3272_user_contacts`.`value`;

-- remove backup table
DROP TABLE `z_3272_user_contacts`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3272';


-- POCOR-3340
-- workflow_actions
UPDATE `workflow_actions`
INNER JOIN `z_3340_workflow_actions` ON `z_3340_workflow_actions`.`id` = `workflow_actions`.`id`
SET `workflow_actions`.`event_key` = `z_3340_workflow_actions`.`event_key`;

DROP TABLE `z_3340_workflow_actions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3340';


-- POCOR-3138
-- security_user
ALTER TABLE `security_users` DROP `identity_number`;

-- translations
DELETE FROM `translations`
WHERE `en` = 'Please set other identity type as default before deleting the current one';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3138';


-- POCOR-3338
-- workflow_actions
UPDATE `workflow_actions`
INNER JOIN `z_3338_workflow_actions` ON `z_3338_workflow_actions`.`id` = `workflow_actions`.`id`
SET `workflow_actions`.`event_key` = `z_3338_workflow_actions`.`event_key`;

DROP TABLE `z_3338_workflow_actions`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3338';


-- 3.6.2
UPDATE config_items SET value = '3.6.2' WHERE code = 'db_version';
