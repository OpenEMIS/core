ALTER TABLE `institution_site_student_absences` ADD `institution_site_id` INT( 11 ) NOT NULL AFTER `institution_site_section_id` ;

UPDATE `institution_site_student_absences` 
JOIN `institution_site_sections` ON `institution_site_sections`.`id` = `institution_site_student_absences`.`institution_site_section_id`
SET `institution_site_student_absences`.`institution_site_id` = IFNULL(`institution_site_sections`.`institution_site_id`, 0);

-- Absence
ALTER TABLE `institution_site_student_absences` CHANGE `first_date_absent` `start_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `last_date_absent` `end_date` DATE NULL NOT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `start_time_absent` `start_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `end_time_absent` `end_time` VARCHAR( 15 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT NULL ;
ALTER TABLE `institution_site_student_absences` CHANGE `full_day_absent` `full_day` INT( 1 ) NOT NULL ;
ALTER TABLE `institution_site_student_absences` DROP `institution_site_section_id` ;
ALTER TABLE `institution_site_student_absences` DROP `absence_type` ;
