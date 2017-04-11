-- system_patches
UPDATE `security_functions` SET `_edit`= 'StudentSurveys.edit' WHERE `id`='1012';

UPDATE `security_role_functions`
INNER JOIN `z_3737_security_role_functions` ON `z_3737_security_role_functions`.`id` = `security_role_functions`.`id`
SET `security_role_functions`.`_edit` = `z_3737_security_role_functions`.`_edit`;

DROP TABLE `z_3737_security_role_functions`;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3737';
