-- POCOR-3731
-- staff_behaviours
DROP TABLE IF EXISTS `staff_behaviours`;
RENAME TABLE `z_3731_staff_behaviours` TO `staff_behaviours`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3731';


-- POCOR-3726
-- restore alert_logs
DROP TABLE IF EXISTS `alert_logs`;
RENAME TABLE `z_3726_alert_logs` TO `alert_logs`;

ALTER TABLE `workflows` DROP `message`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3726';


-- POCOR-3111
-- labels
DELETE FROM `labels` WHERE `id` = '6143285a-ac8d-11e6-8bda-525400b263eb';

-- user_attachments_roles
DROP TABLE IF EXISTS `user_attachments_roles`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3111';


-- POCOR-3570
-- examination_item_results
DROP TABLE IF EXISTS `institutions`;
RENAME TABLE `z_3570_institutions` TO `institutions`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3570';


-- POCOR-3886
-- staff_employments
ALTER TABLE `staff_employments`
  DROP `file_name`,
  DROP `file_content`;

-- labels
DELETE FROM `labels`
WHERE `id` = 'cdf0fca9-0a07-11e7-b9c5-525400b263eb';

-- security_functions
UPDATE `security_functions` SET `_execute` = NULL WHERE `id` IN (3019, 7020);

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3886';


-- 3.9.6.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.6.1' WHERE code = 'db_version';
