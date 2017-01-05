-- POCOR-3668
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3668', NOW());

-- examination_centre_rooms
ALTER TABLE `examination_centre_rooms` CHANGE `size` `size` INT(3) NULL DEFAULT '0'


-- 3.8.5
UPDATE config_items SET value = '3.8.5' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
