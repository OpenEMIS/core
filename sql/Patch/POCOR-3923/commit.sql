-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3923', NOW());

-- security_users
DROP TABLE IF EXISTS `z_3923_security_users`;
CREATE TABLE IF NOT EXISTS `z_3923_security_users` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='This table contains all user information';

ALTER TABLE `z_3923_security_users`
  ADD PRIMARY KEY (`id`);

INSERT INTO `z_3923_security_users`
SELECT `id`,`email`
FROM `security_users`
WHERE `email` IS NOT NULL;

UPDATE `security_users`
SET `email` = NULL;

UPDATE `security_users` `SU`
INNER JOIN `user_contacts` `UC` ON `UC`.`security_user_id` = `SU`.`id`
INNER JOIN `contact_types` `CT` ON `UC`.`contact_type_id` = `CT`.`id`
INNER JOIN `contact_options` `CO` ON (`CO`.`id` = `CT`.`contact_option_id` AND `CO`.`id` = 4)
SET `SU`.`email` = `UC`.`value`;
