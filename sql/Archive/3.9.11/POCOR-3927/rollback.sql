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
