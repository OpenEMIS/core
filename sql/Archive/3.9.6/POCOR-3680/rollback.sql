-- security_group_users
DROP TABLE IF EXISTS `security_group_users`;
RENAME TABLE `z_3680_security_group_users` TO `security_group_users`;

DROP TABLE IF EXISTS `institution_staff`;
RENAME TABLE `z_3680_institution_staff` TO `institution_staff`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3680';
