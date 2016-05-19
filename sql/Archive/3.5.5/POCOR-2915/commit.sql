-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2915', NOW());


-- code here
ALTER TABLE `institution_class_students` ADD INDEX `student_id` (`student_id`);
ALTER TABLE `institution_class_students` ADD INDEX `institution_class_id` (`institution_class_id`);
ALTER TABLE `institution_class_students` ADD INDEX `education_grade_id` (`education_grade_id`);
