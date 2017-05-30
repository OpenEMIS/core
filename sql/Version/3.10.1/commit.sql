-- POCOR-3977
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3977', NOW());

ALTER TABLE `deleted_records`
DROP INDEX `reference_key` ;

ALTER TABLE `deleted_records`
CHANGE COLUMN `reference_key` `reference_key` TEXT CHARACTER SET 'utf8mb4' COLLATE 'utf8mb4_unicode_ci' NOT NULL ;


-- 3.10.1
UPDATE config_items SET value = '3.10.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
