-- Drop New tables
DROP TABLE IF EXISTS `training_courses`;
DROP TABLE IF EXISTS `training_courses_target_populations`;
DROP TABLE IF EXISTS `training_courses_providers`;
DROP TABLE IF EXISTS `training_courses_prerequisites`;
DROP TABLE IF EXISTS `training_courses_result_types`;

DROP TABLE IF EXISTS `training_sessions`;
DROP TABLE IF EXISTS `training_session_trainers`;
DROP TABLE IF EXISTS `training_sessions_trainees`;
DROP TABLE IF EXISTS `training_session_results`;
DROP TABLE IF EXISTS `training_session_trainee_results`;

-- labels
DELETE FROM `labels` WHERE `module` = 'TrainingCourses' AND `field` = 'number_of_months';
DELETE FROM `labels` WHERE `module` = 'TrainingCourses' AND `field` = 'file_content';
DELETE FROM `labels` WHERE `module` = 'TrainingCourses' AND `field` = 'training_field_of_study_id';
DELETE FROM `labels` WHERE `module` = 'TrainingCourses' AND `field` = 'training_course_type_id';
DELETE FROM `labels` WHERE `module` = 'TrainingCourses' AND `field` = 'training_mode_of_delivery_id';

-- Restore Admin - training tables
RENAME TABLE `z_1992_training_courses` TO `training_courses`;
RENAME TABLE `z_1992_training_course_attachments` TO `training_course_attachments`;
RENAME TABLE `z_1992_training_course_experiences` TO `training_course_experiences`;
RENAME TABLE `z_1992_training_course_prerequisites` TO `training_course_prerequisites`;
RENAME TABLE `z_1992_training_course_providers` TO `training_course_providers`;
RENAME TABLE `z_1992_training_course_result_types` TO `training_course_result_types`;
RENAME TABLE `z_1992_training_course_specialisations` TO `training_course_specialisations`;
RENAME TABLE `z_1992_training_course_target_populations` TO `training_course_target_populations`;
RENAME TABLE `z_1992_training_credit_hours` TO `training_credit_hours`;

RENAME TABLE `z_1992_training_sessions` TO `training_sessions`;
RENAME TABLE `z_1992_training_session_results` TO `training_session_results`;
RENAME TABLE `z_1992_training_session_trainees` TO `training_session_trainees`;
RENAME TABLE `z_1992_training_session_trainee_results` TO `training_session_trainee_results`;
RENAME TABLE `z_1992_training_session_trainers` TO `training_session_trainers`;

-- workflow_models
DELETE FROM `workflow_models` WHERE `model` = 'Training.TrainingCourses';
DELETE FROM `workflow_models` WHERE `model` = 'Training.TrainingSessions';

-- field_options
UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'TrainingAchievementTypes';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingCourseTypes';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingFieldStudies';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingLevels';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingModeDeliveries';
UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'TrainingNeedCategories';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingPriorities';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingProviders';
UPDATE `field_options` SET `plugin` = NUll WHERE `code` = 'TrainingRequirements';
UPDATE `field_options` SET `plugin` = 'Training' WHERE `code` = 'TrainingResultTypes';
UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffPositionTitles';

UPDATE `field_options` SET `visible` = 0 WHERE parent = 'Training';

-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5039;
DELETE FROM `security_functions` WHERE `id` = 5040;
DELETE FROM `security_functions` WHERE `id` = 5041;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1992';
