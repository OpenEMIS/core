-- PHPOE-2086
INSERT INTO `db_patches` VALUES ('PHPOE-2086', NOW());

CREATE TABLE `z2086_import_mapping` LIKE `import_mapping`;
INSERT INTO `z2086_import_mapping` SELECT * FROM `import_mapping`;

INSERT INTO `import_mapping` (`model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`) 
values
('InstitutionSiteSurveys', 'survey_form_id', '', '1', '2', 'Survey', 'SurveyForms', 'id')
;

CREATE TABLE `z2086_survey_forms` LIKE `survey_forms`;
INSERT INTO `z2086_survey_forms` SELECT * FROM `survey_forms`;

ALTER TABLE `survey_forms`  ADD `code` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  AFTER `id`;
UPDATE `survey_forms` set `code`=(LEFT(UUID(), 8)) where 1;

CREATE TABLE `z2086_survey_questions` LIKE `survey_questions`;
INSERT INTO `z2086_survey_questions` SELECT * FROM `survey_questions`;

ALTER TABLE `survey_questions`  ADD `code` VARCHAR(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL  AFTER `id`;
UPDATE `survey_questions` set `code`=(LEFT(UUID(), 8)) where 1;

-- security_functions
CREATE TABLE `z_2086_security_functions` LIKE `security_functions`;

INSERT INTO `z_2086_security_functions` 
SELECT * FROM `security_functions` WHERE `id` IN (1024, 1025);

UPDATE `security_functions` SET `name`='Import', `_view`=NULL, `_edit`=NULL, `_delete`=NULL, `_execute` = 'ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results' WHERE `id`=1024;
UPDATE `security_functions` SET `name`='Surveys', `_view`='Surveys.index|Surveys.view', `_edit`='Surveys.edit', `_delete`='Surveys.remove', `_execute` = 'Survey.excel' WHERE `id`=1025;

-- security_role_functions
CREATE TABLE `z_2086_security_role_functions` LIKE `security_role_functions`;

INSERT INTO `z_2086_security_role_functions`
SELECT * FROM `security_role_functions` WHERE `security_function_id` IN (1024, 1025);

UPDATE `security_role_functions` SET `security_function_id` = 0 WHERE `security_function_id` IN (1024, 1025);