-- security_group_areas
DROP TABLE IF EXISTS `security_group_areas`; 
RENAME TABLE `z_3772_security_group_areas` TO `security_group_areas`;

-- security_group_institutions
DROP TABLE IF EXISTS `security_group_institutions`; 
RENAME TABLE `z_3772_security_group_institutions` TO `security_group_institutions`;

-- security_group_users
DROP TABLE IF EXISTS `security_group_users`; 
RENAME TABLE `z_3772_security_group_users` TO `security_group_users`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3772';
