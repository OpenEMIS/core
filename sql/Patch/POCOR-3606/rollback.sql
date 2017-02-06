-- user_nationalities
DROP TABLE IF EXISTS `user_nationalities`;
RENAME TABLE `z_3606_user_nationalities` TO `user_nationalities`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3606';
