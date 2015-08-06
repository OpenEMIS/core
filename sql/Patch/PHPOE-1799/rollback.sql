-- institution_student_transfers
ALTER TABLE `institution_student_transfers` DROP `academic_period_id`;
ALTER TABLE `institution_student_transfers` CHANGE `education_grade_id` `education_programme_id` INT(11) NOT NULL;

-- institution_students
DROP TABLE IF EXISTS `institution_students`;

-- institution_site_grades
ALTER TABLE `institution_site_grades` ADD `status` INT(1) NOT NULL AFTER `id`;
ALTER TABLE `institution_site_grades` DROP `start_date`;
ALTER TABLE `institution_site_grades` DROP `start_year`;
ALTER TABLE `institution_site_grades` DROP `end_date`;
ALTER TABLE `institution_site_grades` DROP `end_year`;

-- student_statuses
DELETE FROM `student_statuses` WHERE `code` IN ('PROMOTED', 'REPEATED');

DROP TABLE IF EXISTS `security_user_types`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'PHPOE-1799';
