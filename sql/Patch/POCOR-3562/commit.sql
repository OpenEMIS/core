-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3562', NOW());

-- create backup institution_subject_students table
CREATE TABLE IF NOT EXISTS `z_3562_institution_subject_students`
LIKE `institution_subject_students`;

INSERT INTO `z_3562_institution_subject_students`
SELECT * FROM `institution_subject_students`;

-- institution_subject_students
ALTER TABLE `institution_subject_students`
DROP COLUMN `status`,
ADD COLUMN `student_status_id` INT(11) NOT NULL COMMENT 'links to student_statuses.id' AFTER `education_subject_id`;

-- patch student_status_id from institution_class_students
UPDATE `institution_subject_students`
INNER JOIN `institution_class_students`
ON (`institution_subject_students`.`student_id` = `institution_class_students`.`student_id`
AND `institution_subject_students`.`institution_class_id` = `institution_class_students`.`institution_class_id`
AND `institution_subject_students`.`academic_period_id` = `institution_class_students`.`academic_period_id`
AND `institution_subject_students`.`institution_id` = `institution_class_students`.`institution_id`)
SET `institution_subject_students`.`student_status_id` = `institution_class_students`.`student_status_id`;
