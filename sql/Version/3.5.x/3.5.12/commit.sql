-- POCOR-3241
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES ('POCOR-3241', NOW());

-- report_progress
DELETE FROM report_progress
WHERE `expiry_date` = NULL
AND `file_path` = NULL
AND `current_records` = 0
AND `status` = 1;


-- POCOR-3081
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3081', NOW());

-- backup institution_students
DROP TABLE IF EXISTS `z_3081_institution_students`;
CREATE TABLE IF NOT EXISTS `z_3081_institution_students` (
  `id` char(36) NOT NULL,
  `end_date` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Indexes for table `institution_students`
ALTER TABLE `z_3081_institution_students`
  ADD PRIMARY KEY (`id`);

-- copy to backup table
INSERT INTO `z_3081_institution_students`
SELECT I1.`id`, I1.`end_date`
FROM `institution_students` I1
INNER JOIN `institution_students` I2
        ON I2.`start_date` = I1.`end_date`
        AND I2.`student_id` = I1.`student_id`
        AND I2.`student_status_id` <> I1.`student_status_id`
    AND I2.`start_date` <>  I2.`end_date`
    AND I2.`created` > I1.`created`
WHERE I1.`student_status_id` = 3;

-- patch the end_date for transfered records
UPDATE `institution_students` I1
INNER JOIN `institution_students` I2
        ON I2.`start_date` = I1.`end_date`
        AND I2.`student_id` = I1.`student_id`
        AND I2.`student_status_id` <> I1.`student_status_id`
        AND I2.`start_date` <>  I2.`end_date`
        AND I2.`created` > I1.`created`
SET I1.`end_date` = DATE_ADD(I1.`end_date`, INTERVAL -1 DAY)
WHERE I1.`student_status_id` = 3;

-- 2nd patch for records that has more than once transfer process and excluded on the query above before
INSERT INTO `z_3081_institution_students`
SELECT I1.`id`, I1.`end_date`
FROM `institution_students` I1
INNER JOIN `institution_students` I2
        ON I2.`start_date` = I1.`end_date`
        AND I2.`student_id` = I1.`student_id`
        AND I2.`start_date` <>  I2.`end_date`
        AND I2.`created` > I1.`created`
WHERE I1.`student_status_id` = 3;

UPDATE `institution_students` I1
INNER JOIN `institution_students` I2
        ON I2.`start_date` = I1.`end_date`
        AND I2.`student_id` = I1.`student_id`
        AND I2.`start_date` <>  I2.`end_date`
        AND I2.`created` > I1.`created`
SET I1.`end_date` = DATE_ADD(I1.`end_date`, INTERVAL -1 DAY)
WHERE I1.`student_status_id` = 3;


-- POCOR-3179
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


-- POCOR-3198
-- db_patches
INSERT INTO `db_patches` (`issue`, `created`) VALUES('POCOR-3198', NOW());

-- import_mapping
UPDATE `import_mapping`
SET `order` = '6',
`lookup_plugin` = 'Institution'
WHERE `model` = 'Institution.StaffAbsences'
AND `column_name` = 'staff_absence_reason_id'
AND `lookup_plugin` = 'FieldOption'
AND `id` = 55;

UPDATE `import_mapping`
SET `order` = '6',
`lookup_plugin` = 'Institution'
WHERE `model` = 'Institution.InstitutionStudentAbsences'
AND `column_name` = 'student_absence_reason_id'
AND `lookup_plugin` = 'FieldOption'
AND `id` = 60;

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (87, 'Institution.StaffAbsences', 'absence_type_id', 'Code', '5', '2', 'Institution', 'AbsenceTypes', 'code');

INSERT INTO `import_mapping` (`id`, `model`, `column_name`, `description`, `order`, `foreign_key`, `lookup_plugin`, `lookup_model`, `lookup_column`)
VALUES (88, 'Institution.InstitutionStudentAbsences', 'absence_type_id', 'Code', '5', '2', 'Institution', 'AbsenceTypes', 'code');


-- 3.5.12
UPDATE config_items SET value = '3.5.12' WHERE code = 'db_version';
UPDATE db_patches SET version = (SELECT value FROM config_items WHERE code = 'db_version') WHERE version IS NULL;
