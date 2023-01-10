-- security_users
DROP TABLE IF EXISTS `security_users`;
RENAME TABLE `z_3809_security_users` TO `security_users`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3809';
