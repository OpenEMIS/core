-- POCOR-2612
-- institution_staff
ALTER TABLE `institution_staff` 
DROP COLUMN `security_group_user_id`,
DROP INDEX `security_group_user_id` ;

-- staff_position_titles
UPDATE `staff_position_titles` 
INNER JOIN `z_2612_staff_position_titles`
	ON `z_2612_staff_position_titles`.`id` = `staff_position_titles`.`id`
SET `staff_position_titles`.`security_role_id` = `z_2612_staff_position_titles`.`security_role_id`;

DROP TABLE `z_2612_staff_position_titles`;

-- security_group_users
DROP TABLE `security_group_users`;
ALTER TABLE `z_2612_security_group_users` 
RENAME TO  `security_group_users` ;

-- system_processes
DROP TABLE IF EXISTS `system_processes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2612';


-- POCOR-2683
-- survey_questions
UPDATE `survey_questions` SET `params` = NULL WHERE `field_type` =  'STUDENT_LIST';

-- Restore table
RENAME TABLE `z_2683_survey_question_params` TO `survey_question_params`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2683';
