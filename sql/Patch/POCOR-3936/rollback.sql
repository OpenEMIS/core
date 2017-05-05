-- staff_behaviours
DROP TABLE IF EXISTS `staff_behaviours`;
RENAME TABLE `z_3936_staff_behaviours` TO `staff_behaviours`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3936';

