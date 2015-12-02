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
UPDATE `security_functions` SET `_execute`='ImportInstitutionSurveys.add|ImportInstitutionSurveys.template|ImportInstitutionSurveys.results' WHERE `id`='1024';
