-- POCOR-3857
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3857', NOW());

-- config_items
INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('126', 'Validate Area Level', 'institution_validate_area_level_id', 'Institution', 'Validate Area Level', '2', '1', '1', '1', 'Dropdown', 'database:Area.AreaLevels', NULL, NULL, '1', '2017-03-08 00:00:00');

INSERT INTO `config_items` (`id`, `name`, `code`, `type`, `label`, `value`, `default_value`, `editable`, `visible`, `field_type`, `option_type`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('127', 'Validate Area Administrative Level', 'institution_validate_area_administrative_level_id', 'Institution', 'Validate Area Administrative Level', '2', '1', '1', '1', 'Dropdown', 'database:Area.AreaAdministrativeLevels', NULL, NULL, '1', '2017-03-08 00:00:00');


-- 3.9.6
UPDATE config_items SET value = '3.9.6' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
