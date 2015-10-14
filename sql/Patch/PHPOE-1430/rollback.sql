
-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
DROP INDEX `security_user_id`;

ALTER TABLE `student_custom_field_values` 
DROP INDEX `student_custom_field_id` ;

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
DROP INDEX `security_user_id`;

ALTER TABLE `staff_custom_field_values` 
DROP INDEX `staff_custom_field_id` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1430';