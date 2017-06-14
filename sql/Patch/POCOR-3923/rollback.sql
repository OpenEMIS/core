-- contact_options
DROP TABLE IF EXISTS `contact_options`;
RENAME TABLE `z_3923_contact_options` TO `contact_options`;

-- security_users
UPDATE `security_users`
SET `email` = NULL;

UPDATE `security_users` `SU`
INNER JOIN `z_3923_security_users` `Z` ON `Z`.`id` = `SU`.`id`
SET `SU`.`email` = `Z`.`email`;

DROP TABLE IF EXISTS `z_3923_security_users`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3923';