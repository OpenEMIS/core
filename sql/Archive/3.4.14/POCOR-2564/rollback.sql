-- institution_section_students
UPDATE `institution_section_students`
INNER JOIN `z_2564_institution_section_students` ON `z_2564_institution_section_students`.`id` = `institution_section_students`.`id`
SET `institution_section_students`.`student_status_id` = `z_2564_institution_section_students`.`student_status_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2564';