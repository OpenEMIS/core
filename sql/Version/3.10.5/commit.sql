-- POCOR-3955
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3955', NOW());

-- security_functions
CREATE TABLE `z_3955_security_functions` LIKE `security_functions`;
INSERT `z_3955_security_functions` SELECT * FROM `security_functions`;

UPDATE `security_functions`
SET `name` = 'Trainings', `_view` = 'Trainings.index', `_add` = 'Trainings.add', `_execute` = 'Trainings.download'
WHERE `security_functions`.`id` = 6011;


-- 3.10.5
UPDATE config_items SET value = '3.10.5' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
