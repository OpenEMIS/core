-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3674', NOW());

-- translations
RENAME TABLE `translations` TO `z_3674_translations`;

-- locales
DROP TABLE IF EXISTS `locales`;
CREATE TABLE IF NOT EXISTS  `locales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `iso` varchar(6) NOT NULL,
  `name` varchar(50) NOT NULL,
  `editable` int(1) NOT NULL DEFAULT '1',
  `direction` char(3) NOT NULL DEFAULT 'lrt' COMMENT 'lrt = left to right, ltr = right to left',
  `modified_user_id` int(11) DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  `created_user_id` int(11) NOT NULL DEFAULT '1',
  `created` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `modified_user_id` (`modified_user_id`),
  KEY `created_user_id` (`created_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- locale_content_translations
DROP TABLE IF EXISTS `locale_content_translations`;
CREATE TABLE IF NOT EXISTS `locale_content_translations` (
  `translation` TEXT NULL,
  `locale_content_id` INT(11) NOT NULL,
  `locale_id` INT(11) NOT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`locale_content_id`, `locale_id`),
  INDEX `locale_content_id` (`locale_content_id`),
  INDEX `locale_id` (`locale_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- locale_contents
DROP TABLE IF EXISTS `locale_contents`;
CREATE TABLE IF NOT EXISTS `locale_contents` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `en` TEXT NOT NULL,
  `created_user_id` INT(11) NOT NULL,
  `created` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `locale_contents` (`id`, `en`, `created_user_id`, `created`) 
SELECT `id`, `en`, `created_user_id`, `created`
FROM `z_3674_translations`;

INSERT INTO `locales` (`id`, `iso`, `name`, `editable`, `direction`, `created_user_id`, `created`)
VALUES (1, 'zh', 'Chinese',0,'ltr', 1, NOW()),
(2, 'ar', 'Arabic',0,'rtl', 1, NOW()),
(3, 'fr', 'French',0,'ltr', 1, NOW()),
(4, 'es', 'Spanish',0,'ltr', 1, NOW()),
(5, 'ru', 'Russian',0,'ltr', 1, NOW());

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`) 
SELECT `Z`.`zh`, `L`.`id`, 1, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`) 
SELECT `Z`.`ar`, `L`.`id`, 2, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`) 
SELECT `Z`.`fr`, `L`.`id`, 3, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`) 
SELECT `Z`.`es`, `L`.`id`, 4, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;

INSERT INTO `locale_content_translations` (`translation`,`locale_content_id`, `locale_id`,`created_user_id`,`created`) 
SELECT `Z`.`ru`, `L`.`id`, 5, `Z`.`created_user_id`, `Z`.`created`
FROM `z_3674_translations` AS `Z`
INNER JOIN `locale_contents` `L` ON `L`.`id` = `Z`.`id`
WHERE `Z`.`en` = `L`.`en`
;



