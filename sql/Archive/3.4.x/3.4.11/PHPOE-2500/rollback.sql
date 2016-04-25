-- institution_section_students
UPDATE `institution_section_students` 
INNER JOIN `z_2500_institution_section_students` 
	ON `z_2500_institution_section_students`.`student_id` = `institution_section_students`.`student_id`
	AND `z_2500_institution_section_students`.`institution_section_id` = `institution_section_students`.`institution_section_id`
	AND `z_2500_institution_section_students`.`education_grade_id` = `institution_section_students`.`education_grade_id`
SET `institution_section_students`.`id` = `z_2500_institution_section_students`.`id`;

DROP TABLE `z_2500_institution_section_students`;

-- security_groups
UPDATE `security_groups` INNER JOIN `z_2500_security_groups` ON `z_2500_security_groups`.`id` = `security_groups`.`id`
SET `security_groups`.`name` = `z_2500_security_groups`.`name`;

DROP TABLE `z_2500_security_groups`;

-- db_patches
DELETE FROM db_patches WHERE `issue` = 'PHPOE-2500';