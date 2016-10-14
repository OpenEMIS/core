-- config_item_options
CREATE TABLE `z_3444_temp_language_mapping` (
  `lang_old` VARCHAR(3) NOT NULL COMMENT '',
  `lang_new` VARCHAR(3) NOT NULL COMMENT '',
  PRIMARY KEY (`lang_old`));

INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('eng', 'en');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('chi', 'zh');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('ara', 'ar');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('fre', 'fr');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('spa', 'es');
INSERT INTO `z_3444_temp_language_mapping` (`lang_old`, `lang_new`) VALUES ('ru', 'ru');

UPDATE `config_item_options`
INNER JOIN `z_3444_temp_language_mapping`
    ON `config_item_options`.`option_type` = 'language'
    AND `config_item_options`.`value` = `z_3444_temp_language_mapping`.`lang_new`
SET `config_item_options`.`value` = `z_3444_temp_language_mapping`.`lang_old`;

-- config_items
UPDATE `config_items`
INNER JOIN `z_3444_temp_language_mapping`
    ON `config_items`.`code` = 'language'
    AND `config_items`.`default_value` = `z_3444_temp_language_mapping`.`lang_new`
SET `config_items`.`default_value` = `z_3444_temp_language_mapping`.`lang_old`;

UPDATE `config_items`
INNER JOIN `z_3444_temp_language_mapping`
    ON `config_items`.`code` = 'language'
    AND `config_items`.`value` = `z_3444_temp_language_mapping`.`lang_new`
SET `config_items`.`value` = `z_3444_temp_language_mapping`.`lang_old`;

DROP TABLE `z_3444_temp_language_mapping`;

-- security_users
ALTER TABLE `security_users`
DROP COLUMN `login_language`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3444';
