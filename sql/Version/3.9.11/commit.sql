-- POCOR-3876
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3876', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `code`, `name`, `visible`, `mo                                                                                                           dified_user_id`, `modified`, `created_user_id`, `created`)
VALUES ('b7b9aad6-1ff1-11e7-a840-525400b263eb', 'InstitutionClasses', 'multigrade', 'Institutions -> Class                                                                                                           es', 'Multi-grade', NULL, NULL, '1', NULL, NULL, '1', '2017-04-13');


-- 3.9.11
UPDATE config_items SET value = '3.9.11' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
