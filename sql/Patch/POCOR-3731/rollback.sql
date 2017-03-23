-- staff_behaviours
DROP TABLE IF EXISTS `staff_behaviours`;
RENAME TABLE `z_3731_staff_behaviours` TO `staff_behaviours`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3731';
