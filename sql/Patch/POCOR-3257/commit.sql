-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3257', NOW());


-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('100', 'Area Education API', 'area_api', 'Administrative Boundaries', 'Area Education API', '', '', '0', '1', '', '', NULL, NULL, '2', NOW());

-- Removed Auto_increments
ALTER TABLE `security_functions` CHANGE `id` `id` INT(11) NOT NULL;
ALTER TABLE `config_items` CHANGE `id` `id` INT(11) NOT NULL;

-- Security functions
UPDATE `security_functions`
    SET `_view` = 'index|view|AdministrativeBoundaries.view|ProductLists.view|Authentication.view',
        `_edit` = 'edit|AdministrativeBoundaries.edit|ProductLists.edit|Authentication.edit'
    WHERE `name` = 'Configurations';
