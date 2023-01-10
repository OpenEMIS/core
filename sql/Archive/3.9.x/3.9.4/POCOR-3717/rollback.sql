-- config_item_options
UPDATE `config_item_options` SET `value` = 'dS F Y' WHERE `config_item_options`.`id` = 7;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3717';