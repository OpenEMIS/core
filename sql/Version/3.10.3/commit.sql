-- POCOR-4025
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4025', NOW());

-- education_grades_subjects
ALTER TABLE `education_grades_subjects` CHANGE `hours_required` `hours_required` DECIMAL(5,2) NULL;


-- 3.10.3
UPDATE config_items SET value = '3.10.3' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
