-- POCOR-4061
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4061', NOW());

-- system_errors
ALTER TABLE `system_errors`
CHANGE COLUMN `code` `code` VARCHAR(10) NOT NULL ;


-- POCOR-4042
-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4042', NOW());

-- institution_classes
CREATE TABLE `z_4042_institution_classes` LIKE `institution_classes`;
INSERT `z_4042_institution_classes` SELECT * FROM `institution_classes`;

ALTER TABLE `institution_classes` CHANGE `staff_id` `staff_id` INT(11) NULL DEFAULT '0' COMMENT 'links to security_users.id';


-- 3.10.4
UPDATE config_items SET value = '3.10.4' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
