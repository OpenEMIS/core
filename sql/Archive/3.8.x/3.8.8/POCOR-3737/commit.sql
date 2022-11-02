-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3737', NOW());

-- security_role_functions
-- clean up of unused _edit
CREATE TABLE `z_3737_security_role_functions` LIKE `security_role_functions`;

INSERT INTO `z_3737_security_role_functions`
SELECT * FROM `security_role_functions` WHERE `security_function_id` = 1012;

UPDATE `security_role_functions` SET `_edit`= 0 WHERE `security_function_id` = 1012;

-- security_functions
UPDATE `security_functions` SET `_edit`= NULL WHERE `id`=1012;
