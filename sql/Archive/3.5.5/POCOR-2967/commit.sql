-- db_patches
INSERT INTO `db_patches` (issue, created) VALUES ('POCOR-2967', NOW());


-- code here
ALTER TABLE `institution_subject_students` ADD INDEX `institution_id` (`institution_id`);
ALTER TABLE `institution_subject_students` ADD INDEX `student_id` (`student_id`);
ALTER TABLE `institution_subject_students` ADD INDEX `institution_class_id` (`institution_class_id`);
ALTER TABLE `institution_subject_students` ADD INDEX `academic_period_id` (`academic_period_id`);
ALTER TABLE `institution_subject_students` ADD INDEX `education_subject_id` (`education_subject_id`);