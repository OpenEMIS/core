-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3857', NOW());

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('126', 'Validate Area Level', 'institution_validate_area_level_id', 'Institution', 'Validate Area Level', '2', '1', '1', '1', 'Dropdown', 'database:Area.AreaLevels', NULL, NULL, '1', '2017-03-08 00:00:00');

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`) 
VALUES ('127', 'Validate Area Administrative Level', 'institution_validate_area_administrative_level_id', 'Institution', 'Validate Area Administrative Level', '2', '1', '1', '1', 'Dropdown', 'database:Area.AreaAdministrativeLevels', NULL, NULL, '1', '2017-03-08 00:00:00');