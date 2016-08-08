-- PHPOE-1577
ALTER TABLE `translations`
DROP PRIMARY KEY,
CHANGE COLUMN `en` `eng` TEXT NOT NULL,
CHANGE COLUMN `ar` `ara` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `zh` `chi` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `es` `spa` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `fr` `fre` TEXT NULL DEFAULT NULL,
CHANGE COLUMN `ru` `rus` TEXT NULL DEFAULT NULL;
DELETE FROM `labels` WHERE `module`='Translations' and`field`='en';
DELETE FROM `labels` WHERE `module`='Translations' and`field`='ar';
DELETE FROM `labels` WHERE `module`='Translations' and`field`='zh';
DELETE FROM `labels` WHERE `module`='Translations' and`field`='fr';
DELETE FROM `labels` WHERE `module`='Translations' and`field`='ru';
DELETE FROM `labels` WHERE `module`='Translations' and`field`='es';
-- end PHPOE-1577

-- PHPOE-1669
DELETE FROM labels where module = 'StaffPositions' and field = 'security_user_id' and en = 'Staff';
-- end PHPOE01669

-- DB version
UPDATE `config_items` SET `value` = '3.0.2' WHERE `code` = 'db_version';
-- end DB version
