-- Drop New tables
DROP TABLE IF EXISTS `training_courses`;
DROP TABLE IF EXISTS `training_sessions`;

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

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1992';
