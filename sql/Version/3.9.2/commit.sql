-- POCOR-3647
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3647', NOW());

-- institution_textbooks
ALTER TABLE `institution_textbooks`
ADD COLUMN `education_grade_id` INT(11) NULL AFTER `academic_period_id`,
ADD INDEX `education_grade_id` (`education_grade_id`);

UPDATE `institution_textbooks`
INNER JOIN `textbooks` ON `institution_textbooks`.`textbook_id` = `textbooks`.`id`
SET `institution_textbooks`.`education_grade_id` = `textbooks`.`education_grade_id`;

ALTER TABLE `institution_textbooks`
CHANGE COLUMN `education_grade_id` `education_grade_id` INT(11) NOT NULL ;


-- POCOR-3535
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3535', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('04865131-e90e-11e6-a68b-525400b263eb', 'SurveyQuestions', 'name', 'Survey -> Questions', 'Question', '1', '1', NOW());


-- POCOR-3537
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3537', NOW());

-- labels
INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('ec92914b-e913-11e6-a68b-525400b263eb', 'RubricTemplates', 'name', 'Rubric -> Templates', 'Template', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('f3a106b5-e913-11e6-a68b-525400b263eb', 'RubricSections', 'name', 'Rubric -> Sections', 'Section', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('0823fd83-e914-11e6-a68b-525400b263eb', 'RubricCriterias', 'name', 'Rubric -> Criterias', 'Criteria', '1', '1', NOW());

INSERT INTO `labels` (`id`, `module`, `field`, `module_name`, `field_name`, `visible`, `created_user_id`, `created`)
VALUES ('017c68d8-e914-11e6-a68b-525400b263eb', 'RubricTemplateOptions', 'name', 'Rubric -> Options', 'Option', '1', '1', NOW());


-- POCOR-3647
-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3647', NOW());

-- institution_textbooks
ALTER TABLE `institution_textbooks`
ADD COLUMN `education_grade_id` INT(11) NULL AFTER `academic_period_id`,
ADD INDEX `education_grade_id` (`education_grade_id`);

UPDATE `institution_textbooks`
INNER JOIN `textbooks` ON `institution_textbooks`.`textbook_id` = `textbooks`.`id`
SET `institution_textbooks`.`education_grade_id` = `textbooks`.`education_grade_id`;

ALTER TABLE `institution_textbooks`
CHANGE COLUMN `education_grade_id` `education_grade_id` INT(11) NOT NULL ;


-- 3.9.2
UPDATE config_items SET value = '3.9.2' WHERE code = 'db_version';
UPDATE system_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
SET @maxId := 0;
SELECT max(id) + 1 INTO @maxId FROM system_updates;
INSERT IGNORE INTO system_updates (id, version, date_released, date_approved, approved_by, status, created) VALUES
(
  @maxId,
  (SELECT value FROM config_items WHERE code = 'db_version'),
  NOW(), NOW(), 1, 2, NOW()
);
