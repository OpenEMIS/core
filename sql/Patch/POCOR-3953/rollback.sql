-- guidance_types
DROP TABLE `guidance_types`;

-- institution_counselors
DROP TABLE `institution_counselors`;

-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_3953_security_functions` TO `security_functions`;

-- security_role_functions
DROP TABLE IF EXISTS `security_role_functions`;
RENAME TABLE `z_3953_security_role_functions` TO `security_role_functions`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3953';
