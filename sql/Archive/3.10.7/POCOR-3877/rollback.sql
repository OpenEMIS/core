DROP TABLE `security_role_functions`;

ALTER TABLE `z_3877_security_role_functions`
RENAME TO  `security_role_functions` ;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3877';
