-- Update 3.4.15a to 3.4.15.1 for sorting consistency
UPDATE `db_patches` SET `version`='3.4.15.1' WHERE `version`='3.4.15a';


-- POCOR-3103
-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3103', NOW());


-- code here
-- user_comment table
ALTER TABLE `user_comments` ADD `comment_type_id` int(11) NOT NULL AFTER `comment_date`;

-- field_options table
INSERT INTO `field_options` (`plugin`, `code`, `name`, `parent`, `params`, `order`, `visible`, `modified_user_id`, `modified`, `created_user_id`, `created`) VALUES ('User', 'CommentTypes', 'Comment Types', 'Others', '{"model":"User.CommentTypes"}', '60', '1', NULL, NULL, '1', NOW());


-- CommentTypes comment_types
DROP TABLE IF EXISTS `comment_types`;
CREATE TABLE `comment_types` LIKE `institution_network_connectivities`;
INSERT INTO `comment_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'CommentTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'CommentTypes');


-- TrainingAchievementTypes training_achievement_types
DROP TABLE IF EXISTS `training_achievement_types`;
CREATE TABLE `training_achievement_types` LIKE `institution_network_connectivities`;
INSERT INTO `training_achievement_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingAchievementTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingAchievementTypes');




-- TrainingCourseTypes training_course_types
DROP TABLE IF EXISTS `training_course_types`;
CREATE TABLE `training_course_types` LIKE `institution_network_connectivities`;
INSERT INTO `training_course_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingCourseTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingCourseTypes');




-- TrainingFieldStudies training_field_of_studies
DROP TABLE IF EXISTS `training_field_of_studies`;
CREATE TABLE `training_field_of_studies` LIKE `institution_network_connectivities`;
INSERT INTO `training_field_of_studies`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingFieldStudies');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingFieldStudies');




-- TrainingLevels training_levels
DROP TABLE IF EXISTS `training_levels`;
CREATE TABLE `training_levels` LIKE `institution_network_connectivities`;
INSERT INTO `training_levels`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingLevels');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingLevels');




-- TrainingModeDeliveries training_mode_deliveries
DROP TABLE IF EXISTS `training_mode_deliveries`;
CREATE TABLE `training_mode_deliveries` LIKE `institution_network_connectivities`;
INSERT INTO `training_mode_deliveries`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingModeDeliveries');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingModeDeliveries');




-- TrainingNeedCategories training_need_categories
DROP TABLE IF EXISTS `training_need_categories`;
CREATE TABLE `training_need_categories` LIKE `institution_network_connectivities`;
INSERT INTO `training_need_categories`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingNeedCategories');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingNeedCategories');




-- TrainingPriorities training_priorities
DROP TABLE IF EXISTS `training_priorities`;
CREATE TABLE `training_priorities` LIKE `institution_network_connectivities`;
INSERT INTO `training_priorities`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingPriorities');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingPriorities');




-- TrainingProviders training_providers
DROP TABLE IF EXISTS `training_providers`;
CREATE TABLE `training_providers` LIKE `institution_network_connectivities`;
INSERT INTO `training_providers`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingProviders');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingProviders');




-- TrainingRequirements training_requirements
DROP TABLE IF EXISTS `training_requirements`;
CREATE TABLE `training_requirements` LIKE `institution_network_connectivities`;
INSERT INTO `training_requirements`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingRequirements');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingRequirements');




-- TrainingResultTypes training_result_types
DROP TABLE IF EXISTS `training_result_types`;
CREATE TABLE `training_result_types` LIKE `institution_network_connectivities`;
INSERT INTO `training_result_types`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingResultTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingResultTypes');




-- TrainingSpecialisations training_specialisations
DROP TABLE IF EXISTS `training_specialisations`;
CREATE TABLE `training_specialisations` LIKE `institution_network_connectivities`;
INSERT INTO `training_specialisations`
SELECT
    `fov`.`id` as `id`,
    `fov`.`name` as `name`,
    `fov`.`order` as `order`,
    `fov`.`visible` as `visible`,
    `fov`.`editable` as `editable`,
    `fov`.`default` as `default`,
    `fov`.`international_code` as `international_code`,
    `fov`.`national_code` as `national_code`,
    `fov`.`modified_user_id` as `modified_user_id`,
    `fov`.`modified` as `modified`,
    `fov`.`created_user_id` as `created_user_id`,
    `fov`.`created` as `created`
FROM `field_option_values` as `fov`
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingSpecialisations');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'TrainingSpecialisations');


-- POCOR-3101
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3101', NOW());

CREATE TABLE `z_3101_institution_students` LIKE `institution_students`;

-- Backup affected student record
INSERT INTO `z_3101_institution_students`
SELECT intStud2.*
FROM institution_students intStud2
INNER JOIN (
        SELECT intStud1.id, intStud1.created, intStud1.student_id, intStud1.institution_id, intStud1.academic_period_id
        FROM institution_students intStud1
                INNER JOIN (
                        SELECT student_id
                        FROM institution_students
                        WHERE institution_students.student_status_id =
                        (
                                SELECT id
                                FROM student_statuses
                                WHERE code = 'CURRENT'
                        )
                        GROUP BY student_id
                        HAVING COUNT(student_id) > 1
                ) dup
                        ON intStud1.student_id = dup.student_id
) stud
        ON stud.student_id = intStud2.student_id
    AND intStud2.created < stud.created
    AND stud.institution_id = intStud2.institution_id
    AND stud.academic_period_id = intStud2.academic_period_id
WHERE intStud2.student_status_id =
(
        SELECT id
        FROM student_statuses
        WHERE code = 'CURRENT'
)
GROUP BY intStud2.id;

-- Patch enrolled records for each student that has a count of more than 1
UPDATE institution_students
INNER JOIN z_3101_institution_students
        ON institution_students.id = z_3101_institution_students.id
SET institution_students.student_status_id = (
        SELECT id
        FROM student_statuses
        WHERE code = 'TRANSFERRED'
);


-- POCOR-2902
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-2902', NOW());

-- workflow_actions
CREATE TABLE z_2902_workflow_actions LIKE workflow_actions;

INSERT INTO z_2902_workflow_actions
SELECT workflow_actions.*
FROM workflow_actions
INNER JOIN workflow_steps WorkflowSteps
        ON WorkflowSteps.id = workflow_actions.workflow_step_id
    AND WorkflowSteps.stage = 0
INNER JOIN workflow_steps NextWorkflowSteps
        ON NextWorkflowSteps.id = workflow_actions.next_workflow_step_id
    AND NextWorkflowSteps.name = 'Closed'
WHERE workflow_actions.action = 1;

UPDATE workflow_actions
INNER JOIN workflow_steps WorkflowSteps
        ON WorkflowSteps.id = workflow_actions.workflow_step_id
    AND WorkflowSteps.stage = 0
INNER JOIN workflow_steps NextWorkflowSteps
        ON NextWorkflowSteps.id = workflow_actions.next_workflow_step_id
    AND NextWorkflowSteps.name = 'Closed'
SET workflow_actions.event_key = 'Workflow.onDeleteRecord'
WHERE workflow_actions.action = 1;


-- 3.5.11
UPDATE config_items SET value = '3.5.11' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
