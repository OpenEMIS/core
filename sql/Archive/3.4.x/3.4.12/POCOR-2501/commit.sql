-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2501', NOW());

-- institution_section_students
ALTER TABLE `institution_section_students` 
ADD COLUMN `student_status_id` INT NULL DEFAULT 0 COMMENT '' AFTER `education_grade_id`;

CREATE TABLE `z_2501_institution_section_students` LIKE `institution_section_students`;

INSERT INTO `z_2501_institution_section_students`
SELECT `institution_section_students`.*  FROM  `institution_section_students` 
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` ON `institution_sections`.`academic_period_id` = `institution_students`.`academic_period_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
    AND `institution_section_students`.`education_grade_id` <> `institution_students`.`education_grade_id`
GROUP BY `institution_section_students`.`id`;

UPDATE `institution_section_students`
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` 
	ON `institution_students`.`academic_period_id` = `institution_sections`.`academic_period_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
SET `institution_section_students`.`education_grade_id` = `institution_students`.`education_grade_id`;

UPDATE `institution_section_students` 
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` 
	ON `institution_sections`.`academic_period_id` = `institution_students`.`academic_period_id`
	AND `institution_students`.`education_grade_id` = `institution_section_students`.`education_grade_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
SET `institution_section_students`.`student_status_id` = `institution_students`.`student_status_id`;

ALTER TABLE `institution_section_students` 
CHANGE COLUMN `student_status_id` `student_status_id` INT(11) NOT NULL COMMENT '' ,
ADD INDEX `student_status_id` (`student_status_id`);
