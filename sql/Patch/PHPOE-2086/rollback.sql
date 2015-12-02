-- PHPOE-2086

DROP TABLE `import_mapping`;
ALTER TABLE `z2086_import_mapping` RENAME `import_mapping`;

DROP TABLE `survey_forms`;
ALTER TABLE `z2086_survey_forms` RENAME `survey_forms`;

DROP TABLE `survey_questions`;
ALTER TABLE `z2086_survey_questions` RENAME `survey_questions`;

-- security_functions
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
