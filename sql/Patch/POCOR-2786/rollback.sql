-- security_users
UPDATE `security_users` INNER JOIN `z_2786_security_users` ON `z_2786_security_users`.`id` = `security_users`.`id`
SET `security_users`.`date_of_birth` = `z_2786_security_users`.`date_of_birth`, `security_users`.`gender_id` = `z_2786_security_users`.`gender_id`;

DROP TABLE IF EXISTS `z_2786_security_users`;

-- institution_students
UPDATE `institution_students` 
INNER JOIN `z_2786_institution_students` 
    ON `institution_students`.`id` = `z_2786_institution_students`.`id`
SET `institution_students`.`start_date` = `academic_periods`.`start_date`,
	`institution_students`.`end_date` = `academic_periods`.`end_date`,
	`institution_students`.`start_year` = `academic_periods`.`start_year`,
	`institution_students`.`end_year` = `academic_periods`.`end_year`;

DROP TABLE IF EXISTS `z_2786_institution_students`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2786';