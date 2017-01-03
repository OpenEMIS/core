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
SELECT sha2(CONCAT(t.`nationality_id`, ',', t.`security_user_id`), '256'), t.`nationality_id`, t.`comments`, t.`security_user_id`, t.`modified_user_id`, t.`modified`, t.`created_user_id`, t.`created` FROM (
    SELECT * FROM `z_3606_user_nationalities`
    ORDER BY `comments` DESC
) t 
GROUP BY t.`security_user_id`, t.`nationality_id`;
