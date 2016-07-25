-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-3179', NOW());


-- StudentBehaviourCategories student_behaviour_categories
DROP TABLE IF EXISTS `student_behaviour_categories`;
CREATE TABLE `student_behaviour_categories` LIKE `institution_network_connectivities`;
INSERT INTO `student_behaviour_categories`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentBehaviourCategories');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentBehaviourCategories');

UPDATE `field_options` SET `plugin` = 'Student' WHERE `code` = 'StudentBehaviourCategories';


-- StudentTransferReasons student_transfer_reasons
DROP TABLE IF EXISTS `student_transfer_reasons`;
CREATE TABLE `student_transfer_reasons` LIKE `institution_network_connectivities`;
INSERT INTO `student_transfer_reasons`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentTransferReasons');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentTransferReasons');

UPDATE `field_options` SET `plugin` = 'Student' WHERE `code` = 'StudentTransferReasons';


-- StudentDropoutReasons student_dropout_reasons
DROP TABLE IF EXISTS `student_dropout_reasons`;
CREATE TABLE `student_dropout_reasons` LIKE `institution_network_connectivities`;
INSERT INTO `student_dropout_reasons`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons');

UPDATE `field_options` SET `plugin` = 'Student' WHERE `code` = 'StudentDropoutReasons';


-- StaffBehaviourCategories staff_behaviour_categories
DROP TABLE IF EXISTS `staff_behaviour_categories`;
CREATE TABLE `staff_behaviour_categories` LIKE `institution_network_connectivities`;
INSERT INTO `staff_behaviour_categories`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffBehaviourCategories');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffBehaviourCategories');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffBehaviourCategories';


-- StaffTrainingCategories staff_training_categories
DROP TABLE IF EXISTS `staff_training_categories`;
CREATE TABLE `staff_training_categories` LIKE `institution_network_connectivities`;
INSERT INTO `staff_training_categories`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTrainingCategories');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTrainingCategories');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'StaffTrainingCategories';


-- StudentAbsenceReasons & StaffAbsenceReasons
UPDATE `field_options` SET `plugin` = 'Institution' WHERE `code` = 'StudentAbsenceReasons';
UPDATE `field_options` SET `plugin` = 'Institution' WHERE `code` = 'StaffAbsenceReasons';


-- SalaryAdditionTypes salary_addition_types
DROP TABLE IF EXISTS `salary_addition_types`;
CREATE TABLE `salary_addition_types` LIKE `institution_network_connectivities`;
INSERT INTO `salary_addition_types`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'SalaryAdditionTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'SalaryAdditionTypes');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'SalaryAdditionTypes';


-- SalaryDeductionTypes salary_deduction_types
DROP TABLE IF EXISTS `salary_deduction_types`;
CREATE TABLE `salary_deduction_types` LIKE `institution_network_connectivities`;
INSERT INTO `salary_deduction_types`
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
WHERE `fov`.`field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'SalaryDeductionTypes');

UPDATE `field_option_values` set `visible`=0 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'SalaryDeductionTypes');

UPDATE `field_options` SET `plugin` = 'Staff' WHERE `code` = 'SalaryDeductionTypes';


-- Drop table
DROP TABLE batch_processes;


