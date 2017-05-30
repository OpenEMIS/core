-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3606', NOW());

-- table `user_nationalities`
RENAME TABLE `user_nationalities` TO `z_3606_user_nationalities`;

DROP TABLE IF EXISTS `user_nationalities`;
CREATE TABLE IF NOT EXISTS `user_nationalities` (
  `id` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `comments` text COLLATE utf8mb4_unicode_ci,
  `preferred` int(1) NOT NULL DEFAULT '0',
  `nationality_id` int(11) NOT NULL COMMENT 'links to nationalities.id',
  `security_user_id` int(11) NOT NULL COMMENT 'links to security_users.id',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL,
  `created` datetime NOT NULL,
  PRIMARY KEY (`nationality_id`, `security_user_id`),
  INDEX `nationality_id` (`nationality_id`),
  INDEX `security_user_id` (`security_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='This table contains nationality information of every user';

-- reinsert data
INSERT INTO `user_nationalities` (`id`, `nationality_id`, `comments`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created`)
SELECT sha2(CONCAT(`nationality_id`, ',', `security_user_id`), '256'), `nationality_id`, `comments`, `security_user_id`, `modified_user_id`, `modified`, `created_user_id`, `created` 
FROM `z_3606_user_nationalities`
GROUP BY `security_user_id`, `nationality_id`;

-- update comment
UPDATE `user_nationalities` U
INNER JOIN `z_3606_user_nationalities` Z
  ON (Z.`nationality_id` = U.`nationality_id` 
      AND Z.`security_user_id` = U.`security_user_id`
      AND (Z.`comments` IS NOT NULL AND Z.`comments` <> ''))
SET U.`comments` = Z.`comments`;

-- update preferred
UPDATE `user_nationalities` U
INNER JOIN (
  SELECT `security_user_id`, `nationality_id`
  FROM `z_3606_user_nationalities` Z1
  WHERE `created` = (
    SELECT MAX(`created`) 
    FROM `z_3606_user_nationalities` 
    WHERE Z1.`security_user_id` = `security_user_id`
  )
)AS Z
ON U.`security_user_id` = Z.`security_user_id`
AND U.`nationality_id` = Z.`nationality_id`
SET U.`preferred` = 1;

-- update security_user for its nationality_id, identity_type and identity_number
#reset everything first.
UPDATE `security_users` S
SET S.`identity_type_id` = NULL, 
    S.`identity_number` = NULL,
    S.`nationality_id` = NULL;
#get nationality, identity and number based on the existing record.
UPDATE `security_users` S
INNER JOIN `user_nationalities` UN
ON (S.`id` = UN.`security_user_id`
    AND UN.`preferred` = 1
)
INNER JOIN `nationalities` N 
ON N.`id` = UN.`nationality_id`
LEFT JOIN(
    SELECT `security_user_id`, `identity_type_id`, `number`
    FROM `user_identities` U1
    WHERE `created` = (
      SELECT MAX(`created`) 
      FROM `user_identities` 
      WHERE U1.`security_user_id` = `security_user_id`
      AND U1.`identity_type_id` = `identity_type_id`
    )
)AS UI 
ON (UI.`identity_type_id` = N.`identity_type_id` 
    AND UI.`security_user_id` = UN.`security_user_id`
)
SET S.`identity_type_id` = N.`identity_type_id`, 
    S.`identity_number` = UI.`number`,
    S.`nationality_id` = N.`id`;
