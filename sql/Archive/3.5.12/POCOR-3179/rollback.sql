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
