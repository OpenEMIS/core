-- POCOR-2449
-- custom_field_types
DELETE FROM `custom_field_types` WHERE `code` = 'FILE';

-- custom_modules
UPDATE `custom_modules` SET `supported_field_types` = REPLACE(`supported_field_types`, ',FILE', '') WHERE `model` = 'Institution.Institutions';
UPDATE `custom_modules` SET `supported_field_types` = REPLACE(`supported_field_types`, ',FILE', '') WHERE `model` = 'Student.Students';
UPDATE `custom_modules` SET `supported_field_types` = REPLACE(`supported_field_types`, ',FILE', '') WHERE `model` = 'Staff.Staff';
UPDATE `custom_modules` SET `supported_field_types` = REPLACE(`supported_field_types`, ',FILE', '') WHERE `model` = 'Institution.InstitutionInfrastructures';

-- Restore tables
DROP TABLE IF EXISTS `custom_field_values`;
RENAME TABLE `z_2449_custom_field_values` TO `custom_field_values`;

DROP TABLE IF EXISTS `institution_custom_field_values`;
RENAME TABLE `z_2449_institution_custom_field_values` TO `institution_custom_field_values`;

DROP TABLE IF EXISTS `student_custom_field_values`;
RENAME TABLE `z_2449_student_custom_field_values` TO `student_custom_field_values`;

DROP TABLE IF EXISTS `staff_custom_field_values`;
RENAME TABLE `z_2449_staff_custom_field_values` TO `staff_custom_field_values`;

DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
RENAME TABLE `z_2449_infrastructure_custom_field_values` TO `infrastructure_custom_field_values`;

DROP TABLE IF EXISTS `institution_survey_answers`;
RENAME TABLE `z_2449_institution_survey_answers` TO `institution_survey_answers`;

DROP TABLE IF EXISTS `institution_student_survey_answers`;
RENAME TABLE `z_2449_institution_student_survey_answers` TO `institution_student_survey_answers`;

DROP TABLE IF EXISTS `user_activities`;
RENAME TABLE `z_2449_user_activities` TO `user_activities`;

DROP TABLE IF EXISTS `institution_activities`;
RENAME TABLE `z_2449_institution_activities` TO `institution_activities`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2449';


-- POCOR-2450
DELETE FROM `custom_field_types` WHERE `id`=10;

DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_2450_custom_modules` TO `custom_modules`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2450';


-- POCOR-2588
ALTER TABLE `academic_period_levels` DROP `editable`;

DROP TABLE institution_students;
RENAME TABLE z_2588_institution_students TO institution_students;
DROP TABLE z_2588_academic_period_parent;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2588';


-- 3.5.3
UPDATE config_items SET value = '3.5.3' WHERE code = 'db_version';
