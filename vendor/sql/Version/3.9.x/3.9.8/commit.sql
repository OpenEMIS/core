-- POCOR_3733
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3733', NOW());

-- security_functions
UPDATE `security_functions`
SET `order` = `order` + 1
WHERE `order` >= 6005 AND `order` <= 6008;

INSERT INTO `security_functions` (`id`, `name`, `controller`, `module`, `category`, `parent_id`, `_view`, `_edit`, `_add`, `_delete`, `_execute`, `order`, `visible`, `description`, `modified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('6011', 'Professional Development', 'Reports', 'Reports', 'Reports', '-1', 'ProfessionalDevelopment.index', NULL, 'ProfessionalDevelopment.add', NULL, 'ProfessionalDevelopment.download', '6005', '1', NULL, NULL, NULL, '1', NOW());


-- 3.9.8
UPDATE config_items SET value = '3.9.8' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
