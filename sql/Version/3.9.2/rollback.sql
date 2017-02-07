-- POCOR-3647
-- institution_textbooks
ALTER TABLE `institution_textbooks`
DROP COLUMN `education_grade_id`,
DROP INDEX `education_grade_id` ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3647';


-- POCOR-3535
-- labels
DELETE FROM `labels` WHERE `module` = 'SurveyQuestions' AND `field` = 'name';

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3535';


-- POCOR-3537
-- labels
DELETE FROM `labels` WHERE `module` = 'RubricTemplates' AND `field` = 'name';
DELETE FROM `labels` WHERE `module` = 'RubricSections' AND `field` = 'name';
DELETE FROM `labels` WHERE `module` = 'RubricCriterias' AND `field` = 'name';
DELETE FROM `labels` WHERE `module` = 'RubricTemplateOptions' AND `field` = 'name';

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3537';


-- POCOR-3647
-- institution_textbooks
ALTER TABLE `institution_textbooks`
DROP COLUMN `education_grade_id`,
DROP INDEX `education_grade_id` ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3647';


-- 3.9.1
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.1' WHERE code = 'db_version';
