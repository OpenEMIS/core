-- security_functions
DROP TABLE IF EXISTS `security_functions`;
RENAME TABLE `z_3955_security_functions` TO `security_functions`;


-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3955';
