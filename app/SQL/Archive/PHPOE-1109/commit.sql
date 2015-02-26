ALTER TABLE `students` ADD `third_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `middle_name`;

ALTER TABLE `student_history` ADD `third_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `middle_name`;

ALTER TABLE `staff` ADD `third_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `middle_name`;

ALTER TABLE `staff_history` ADD `third_name` VARCHAR(100) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL AFTER `middle_name`;