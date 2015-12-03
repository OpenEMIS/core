-- Drop New tables
DROP TABLE IF EXISTS `staff_training_needs`;

-- Restore Admin - training tables
RENAME TABLE `z_1978_staff_training_needs` TO `staff_training_needs`;

-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Staff.TrainingNeeds';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1978';

-- area_administratives
ALTER TABLE `area_administratives` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NOT NULL COMMENT '',
DROP COLUMN `is_main_country`;

ALTER TABLE `areas` 
CHANGE COLUMN `parent_id` `parent_id` INT(11) NOT NULL COMMENT '';

DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-2069';


-- PHPOE-2086

DROP TABLE `import_mapping`;
ALTER TABLE `z2086_import_mapping` RENAME `import_mapping`;

DROP TABLE `survey_forms`;
ALTER TABLE `z2086_survey_forms` RENAME `survey_forms`;

DROP TABLE `survey_questions`;
ALTER TABLE `z2086_survey_questions` RENAME `survey_questions`;

DELETE FROM `security_functions` WHERE `id` IN (1024, 1025);

INSERT INTO `security_functions` SELECT * FROM `z_2086_security_functions`;

DROP TABLE `z_2086_security_functions`;

-- security_role_functions
DELETE FROM `security_role_functions` WHERE `id` IN (
    SELECT `id` FROM `z_2086_security_role_functions`
);

INSERT INTO `security_role_functions`
SELECT * FROM `z_2086_security_role_functions`;

DROP TABLE `z_2086_security_role_functions`;

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2086';

UPDATE config_items SET value = '3.3.6' WHERE code = 'db_version';
