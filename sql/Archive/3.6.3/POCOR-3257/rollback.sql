-- security functions
UPDATE `security_functions` SET `_view` = 'index|view', `_edit` = 'edit' WHERE `name` = 'Configurations';

-- config items
DELETE FROM `config_items` WHERE `code` = 'area_api';

-- Auto_increment
ALTER TABLE `security_functions` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;
ALTER TABLE `config_items` CHANGE `id` `id` INT(11) NOT NULL AUTO_INCREMENT;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3257';
