-- drop params column
ALTER TABLE `custom_fields` DROP `params`;
ALTER TABLE `institution_custom_fields` DROP `params`;
ALTER TABLE `student_custom_fields` DROP `params`;
ALTER TABLE `staff_custom_fields` DROP `params`;
ALTER TABLE `infrastructure_custom_fields` DROP `params`;
ALTER TABLE `survey_questions` DROP `params`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2445';
