-- POCOR-3388
-- config_items
DELETE FROM `config_items` WHERE `code` = 'external_data_source_type';

-- config_item_options
DELETE FROM `config_item_options` WHERE `option_type` = 'external_data_source_type';

-- external_data_source_attributes
DROP TABLE `external_data_source_attributes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2827';


-- POCOR-3388
-- institution_students
ALTER TABLE `institution_students` DROP `previous_institution_student_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3388';


-- POCOR-3444
-- config_item_options
CREATE TABLE `z_3444_temp_language_mapping` (
  `lang_old` VARCHAR(3) NOT NULL COMMENT '',
  `lang_new` VARCHAR(3) NOT NULL COMMENT '',
  PRIMARY KEY (`lang_old`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 DEFAULT COLLATE utf8mb4_unicode_ci;

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

UPDATE `config_items`
SET name = 'Show Language Option during Login', label = 'Show Language Option during Login'
WHERE type = 'System' AND code = 'language_menu';

DROP TABLE `z_3444_temp_language_mapping`;

ALTER TABLE config_items CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;

ALTER TABLE config_item_options CONVERT TO CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- security_users
ALTER TABLE `security_users`
DROP COLUMN `preferred_language`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3444';


-- POCOR-3253
-- Restore tables
DROP TABLE IF EXISTS `workflow_models`;
RENAME TABLE `z_3253_workflow_models` TO `workflow_models`;

DROP TABLE IF EXISTS `workflow_steps`;
RENAME TABLE `z_3253_workflow_steps` TO `workflow_steps`;

DROP TABLE IF EXISTS `workflow_actions`;
RENAME TABLE `z_3253_workflow_actions` TO `workflow_actions`;

RENAME TABLE `z_3253_workflow_records` TO `workflow_records`;

DROP TABLE IF EXISTS `workflow_transitions`;
RENAME TABLE `z_3253_workflow_transitions` TO `workflow_transitions`;

DROP TABLE IF EXISTS `institution_surveys`;
RENAME TABLE `z_3253_institution_surveys` TO `institution_surveys`;

DROP TABLE IF EXISTS `institution_staff_leave`;
RENAME TABLE `z_3253_staff_leaves` TO `staff_leaves`;

DROP TABLE IF EXISTS `institution_positions`;
RENAME TABLE `z_3253_institution_positions` TO `institution_positions`;

DROP TABLE IF EXISTS `institution_staff_position_profiles`;
RENAME TABLE `z_3253_institution_staff_position_profiles` TO `institution_staff_position_profiles`;

DROP TABLE IF EXISTS `training_courses`;
RENAME TABLE `z_3253_training_courses` TO `training_courses`;

DROP TABLE IF EXISTS `training_sessions`;
RENAME TABLE `z_3253_training_sessions` TO `training_sessions`;

DROP TABLE IF EXISTS `training_session_results`;
RENAME TABLE `z_3253_training_session_results` TO `training_session_results`;

DROP TABLE IF EXISTS `staff_training_needs`;
RENAME TABLE `z_3253_staff_training_needs` TO `staff_training_needs`;

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5049;

UPDATE `security_functions` SET `controller` = 'Staff', `_view` = 'Leave.index|Leave.view', `_edit` = 'Leave.edit', `_add` = 'Leave.add', `_delete` = 'Leave.remove', `_execute` = 'Leave.download' WHERE `id` = 3016;

-- labels
DELETE FROM `labels` WHERE `id` = 'de65853b-9054-11e6-88cb-525400b263eb';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3253';


-- 3.6.7
UPDATE config_items SET value = '3.6.7' WHERE code = 'db_version';
