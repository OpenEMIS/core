-- POCOR-4040
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4040', NOW());

ALTER TABLE `institution_rooms`
CHANGE COLUMN `room_type_id` `room_type_id` INT(11) NOT NULL COMMENT 'links to room_types.id',
CHANGE COLUMN `room_status_id` `room_status_id` INT(11) NOT NULL COMMENT 'links to infrastructure_statuses.id' ;


-- 3.10.1.1
UPDATE config_items SET value = '3.10.1.1' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
