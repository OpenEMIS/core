-- PHPOE-1577
ALTER TABLE `translations` 
ADD PRIMARY KEY (`id`),
CHANGE COLUMN `eng` `en` TEXT NOT NULL,
CHANGE COLUMN `ara` `ar` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `chi` `zh` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `spa` `es` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `fre` `fr` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `rus` `ru` TEXT NULL DEFAULT NULL;
INSERT INTO `labels` (`module`, `created_user_id`, `created`, `field`, `en`) VALUES
('Translations', 1, Now(), 'en', 'English'),
('Translations', 1, Now(), 'ar', 'العربية'),
('Translations', 1, Now(), 'zh', ' 中文'),
('Translations', 1, Now(), 'fr', 'Français'),
('Translations', 1, Now(), 'ru', 'русский'),
('Translations', 1, Now(), 'es', 'español');
-- end PHPOE-1577

-- PHPOE-1669
INSERT INTO `labels` (`module`, `field`, `en`, `created_user_id`, `created`) VALUES ('StaffPositions', 'security_user_id', 'Staff', '1', NOW());
-- end PHPOE-1669

-- DB version
UPDATE `config_items` SET `value` = '3.0.3' WHERE `code` = 'db_version';
-- end DB version
