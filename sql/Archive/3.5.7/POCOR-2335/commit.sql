-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2335', NOW());

-- staff_training_needs
CREATE TABLE `z_2335_staff_training_needs` LIKE `staff_training_needs`;
INSERT INTO `z_2335_staff_training_needs` SELECT * FROM `staff_training_needs`;

DELETE FROM `staff_training_needs` WHERE NOT EXISTS (
	SELECT 1 FROM `training_courses` 
	WHERE `training_courses`.`id` = `staff_training_needs`.`course_id`
	)
AND `staff_training_needs`.`course_id` <> 0
AND `staff_training_needs`.`training_need_category_id` = 0;
