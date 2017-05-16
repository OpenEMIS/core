-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3923', NOW());

-- security_users
UPDATE `security_users`
SET `email` = NULL;

UPDATE `security_users` `SU`
INNER JOIN `z_3923_security_users` `Z` ON `Z`.`id` = `SU`.`id`
SET `SU`.`email` = `Z`.`email`;

DROP TABLE IF EXISTS `z_3923_security_users`;
