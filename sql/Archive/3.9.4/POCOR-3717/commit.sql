-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3717', NOW());

-- config_item_options
UPDATE `config_item_options` SET `value` = 'jS F Y' WHERE `config_item_options`.`id` = 7;
