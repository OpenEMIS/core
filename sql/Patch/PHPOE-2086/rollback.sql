-- PHPOE-2086

DROP TABLE `import_mapping`;
ALTER TABLE `z2086_import_mapping` RENAME `import_mapping`;

DROP TABLE `survey_forms`;
ALTER TABLE `z2086_survey_forms` RENAME `survey_forms`;

DROP TABLE `survey_questions`;
ALTER TABLE `z2086_survey_questions` RENAME `survey_questions`;

-- security_functions
UPDATE `security_functions` SET `_execute`='' WHERE `id`='1024';

DELETE FROM `db_patches` WHERE `issue`='PHPOE-2086';
