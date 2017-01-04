-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3606', NOW());

-- table `user_nationalities`
RENAME TABLE `user_nationalities` TO `z_3606_user_nationalities`;

DROP TABLE IF EXISTS `user_nationalities`;
CREATE TABLE IF NOT EXISTS `user_nationalities` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nationality_id` int(11) NOT NULL COMMENT 'links to nationalities.id',
  `comments` text COLLATE utf8mb4_unicode_ci,
  `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`nationality_id`, `security_user_id`),
  INDEX `nationality_id` (`nationality_id`),
  INDEX `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains nationality information of every user';

INSERT INTO `user_nationalities` (`id`, `nationality_id`, `comments`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`nationality_id`, ',', `security_user_id`), '256'), `nationality_id`, `comments`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created` 
FROM `z_3606_user_nationalities`
GROUP BY `security_user_id`, `nationality_id`;

UPDATE `user_nationalities` U
INNER JOIN `z_3606_user_nationalities` Z
  ON (Z.`nationality_id` = U.`nationality_id` 
      AND Z.`security_user_id` = U.`security_user_id`
      AND (Z.`comments` IS NOT NULL AND Z.`comments` <> ''))
SET U.`comments` = Z.`comments`;
