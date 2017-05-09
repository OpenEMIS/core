-- custom_field_types
DROP TABLE IF EXISTS `custom_field_types`;
RENAME TABLE `z_3516_custom_field_types` TO `custom_field_types`;


-- custom_field_values
DROP TABLE IF EXISTS `custom_field_values`;
RENAME TABLE `z_3516_custom_field_values` TO `custom_field_values`;


-- institution_custom_field_values
DROP TABLE IF EXISTS `institution_custom_field_values`;
RENAME TABLE `z_3516_institution_custom_field_values` TO `institution_custom_field_values`;


-- infrastructure_custom_field_values
DROP TABLE IF EXISTS `infrastructure_custom_field_values`;
RENAME TABLE `z_3516_infrastructure_custom_field_values` TO `infrastructure_custom_field_values`;


-- room_custom_field_values
DROP TABLE IF EXISTS `room_custom_field_values`;
RENAME TABLE `z_3516_room_custom_field_values` TO `room_custom_field_values`;


-- staff_custom_field_values
DROP TABLE IF EXISTS `staff_custom_field_values`;
RENAME TABLE `z_3516_staff_custom_field_values` TO `staff_custom_field_values`;


-- student_custom_field_values
DROP TABLE IF EXISTS `student_custom_field_values`;
RENAME TABLE `z_3516_student_custom_field_values` TO `student_custom_field_values`;


-- institution_survey_answers
DROP TABLE IF EXISTS `institution_survey_answers`;
RENAME TABLE `z_3516_institution_survey_answers` TO `institution_survey_answers`;


-- institution_student_survey_answers
DROP TABLE IF EXISTS `institution_student_survey_answers`;
RENAME TABLE `z_3516_institution_student_survey_answers` TO `institution_student_survey_answers`;


-- institution_repeater_survey_answers
DROP TABLE IF EXISTS `institution_repeater_survey_answers`;
RENAME TABLE `z_3516_institution_repeater_survey_answers` TO `institution_repeater_survey_answers`;


-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3516';
