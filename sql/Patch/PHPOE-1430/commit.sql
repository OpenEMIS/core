-- db_patches
INSERT INTO `db_patches` VALUES ('PHPOE-1430');

-- student_custom_field_values
ALTER TABLE `student_custom_field_values` 
ADD INDEX `student_custom_field_id` (`student_custom_field_id` ASC)  COMMENT '',
ADD INDEX `security_user_id` (`security_user_id` ASC)  COMMENT '';

-- staff_custom_field_values
ALTER TABLE `staff_custom_field_values` 
ADD INDEX `staff_custom_field_id` (`staff_custom_field_id` ASC)  COMMENT '',
ADD INDEX `security_user_id` (`security_user_id` ASC)  COMMENT '';

