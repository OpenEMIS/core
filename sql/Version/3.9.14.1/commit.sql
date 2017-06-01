-- POCOR-4013
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4013', NOW());

-- security_functions
UPDATE `security_functions` SET `_execute` = 'Assessments.excel|resultsExport' WHERE `id` = 1015;


-- 3.9.14.1
UPDATE config_items SET value = '3.9.14.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
