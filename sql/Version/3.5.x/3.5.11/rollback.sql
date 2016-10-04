-- POCOR-3103
-- code here
-- TrainingAchievementTypes training_achievement_types
DROP TABLE `training_achievement_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingAchievementTypes');


-- TrainingCourseTypes training_course_types
DROP TABLE `training_course_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingCourseTypes');


-- TrainingFieldStudies training_field_of_studies
DROP TABLE `training_field_of_studies`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingFieldStudies');


-- TrainingLevels training_levels
DROP TABLE `training_levels`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingLevels');


-- TrainingModeDeliveries training_mode_deliveries
DROP TABLE `training_mode_deliveries`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingModeDeliveries');


-- TrainingNeedCategories training_need_categories
DROP TABLE `training_need_categories`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingNeedCategories');


-- TrainingPriorities training_priorities
DROP TABLE `training_priorities`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingPriorities');


-- TrainingProviders training_providers
DROP TABLE `training_providers`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingProviders');


-- TrainingRequirements training_requirements
DROP TABLE `training_requirements`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingRequirements');


-- TrainingResultTypes training_result_types
DROP TABLE `training_result_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingResultTypes');


-- TrainingSpecialisations training_specialisations
DROP TABLE `training_specialisations`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingSpecialisations');


-- field_options table
DROP TABLE `comment_types`;
DELETE FROM `field_options` WHERE `code` = 'CommentTypes';

-- user_comments table
ALTER TABLE `user_comments` DROP COLUMN `comment_type_id`;



-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3103';


-- POCOR-3101
UPDATE institution_students
INNER JOIN z_3101_institution_students
        ON institution_students.id = z_3101_institution_students.id
SET institution_students.student_status_id = z_3101_institution_students.student_status_id;

DROP TABLE z_3101_institution_students;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3101';


-- POCOR-2902
-- workflow_actions
UPDATE workflow_actions
INNER JOIN z_2902_workflow_actions
        ON z_2902_workflow_actions.id = workflow_actions.id
SET workflow_actions.event_key = z_2902_workflow_actions.event_key;

DROP TABLE z_2902_workflow_actions;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`= 'POCOR-2902';


-- 3.5.10.1
UPDATE config_items SET value = '3.5.10.1' WHERE code = 'db_version';
