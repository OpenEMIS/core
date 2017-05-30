-- POCOR-3241
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3241';


-- POCOR-3081
UPDATE `institution_students`
INNER JOIN `z_3081_institution_students`
        ON `institution_students`.`id` = `z_3081_institution_students`.`id`
SET `institution_students`.`end_date` = `z_3081_institution_students`.`end_date`;

DROP TABLE `z_3081_institution_students`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3081';


-- POCOR-3179
-- StudentBehaviourCategories student_behaviour_categories
DROP TABLE `student_behaviour_categories`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentBehaviourCategories');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StudentBehaviourCategories';


-- StudentTransferReasons student_transfer_reasons
DROP TABLE `student_transfer_reasons`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentTransferReasons');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StudentTransferReasons';


-- StudentDropoutReasons student_dropout_reasons
DROP TABLE `student_dropout_reasons`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StudentDropoutReasons');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StudentDropoutReasons';


-- StaffBehaviourCategories staff_behaviour_categories
DROP TABLE `staff_behaviour_categories`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffBehaviourCategories');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StaffBehaviourCategories';


-- StaffTrainingCategories staff_training_categories
DROP TABLE `staff_training_categories`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'StaffTrainingCategories');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StaffTrainingCategories';


-- StudentAbsenceReasons & StaffAbsenceReasons
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StudentAbsenceReasons';
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'StaffAbsenceReasons';


-- SalaryAdditionTypes salary_addition_types
DROP TABLE `salary_addition_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'SalaryAdditionTypes');
UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'SalaryAdditionTypes';


-- SalaryDeductionTypes salary_deduction_types
DROP TABLE `salary_deduction_types`;
UPDATE `field_option_values` set `visible`=1 WHERE `field_option_id`=(SELECT `id` FROM `field_options` WHERE `code` = 'SalaryDeductionTypes');

UPDATE `field_options` SET `plugin` = 'FieldOption' WHERE `code` = 'SalaryDeductionTypes';


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3179';


-- POCOR-3198
-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3198';

-- import_mapping
DELETE FROM `import_mapping`
WHERE `model` = 'Institution.StaffAbsences'
AND `column_name` = 'absence_type_id';

DELETE FROM `import_mapping`
WHERE `model` = 'Institution.InstitutionStudentAbsences'
AND `column_name` = 'absence_type_id';


-- 3.5.11
UPDATE config_items SET value = '3.5.11' WHERE code = 'db_version';
