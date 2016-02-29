-- db_patches
INSERT INTO `db_patches` VALUES ('POCOR-2564', NOW());

-- institution_section_students
CREATE TABLE `z_2564_institution_section_students` (
  `id` char(36) NOT NULL,
  `student_status_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `student_status_id` (`student_status_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `z_2564_institution_section_students`
SELECT `institution_section_students`.`id`, `institution_section_students`.`student_status_id` FROM  `institution_section_students` 
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` ON `institution_sections`.`academic_period_id` = `institution_students`.`academic_period_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
    AND `institution_section_students`.`education_grade_id` = `institution_students`.`education_grade_id`
GROUP BY `institution_section_students`.`id`;

UPDATE `institution_section_students` 
INNER JOIN `institution_sections` ON `institution_sections`.`id` = `institution_section_students`.`institution_section_id`
INNER JOIN `institution_students` 
	ON `institution_sections`.`academic_period_id` = `institution_students`.`academic_period_id`
	AND `institution_students`.`education_grade_id` = `institution_section_students`.`education_grade_id`
    AND `institution_students`.`student_id` = `institution_section_students`.`student_id`
    AND `institution_students`.`institution_id` = `institution_sections`.`institution_id`
SET `institution_section_students`.`student_status_id` = `institution_students`.`student_status_id`;