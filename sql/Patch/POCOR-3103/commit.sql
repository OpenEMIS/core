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
