-- POCOR-3927
-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_3927_security_functions` TO `security_functions`;

-- staff_trainings
DROP TABLE IF EXISTS `staff_trainings`;
RENAME TABLE `z_3927_staff_trainings` TO `staff_trainings`;

-- alerts Table
DELETE FROM `alerts` WHERE `name` = 'LicenseRenewal';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3927';


-- POCOR-3876
-- labels
DELETE FROM `labels`
WHERE `id` = 'b7b9aad6-1ff1-11e7-a840-525400b263eb';

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3876';


-- 3.9.10
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.10' WHERE code = 'db_version';
