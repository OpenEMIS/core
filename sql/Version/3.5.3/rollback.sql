-- POCOR-2450
DELETE FROM `custom_field_types` WHERE `id`=10;

DROP TABLE IF EXISTS `custom_modules`;
RENAME TABLE `z_2450_custom_modules` TO `custom_modules`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2450';


-- POCOR-2863
-- institution_class_students
DROP TABLE `institution_class_students`;

ALTER TABLE `z_2863_institution_class_students`
RENAME TO  `institution_class_students`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2863';


-- POCOR-2786
-- security_users
UPDATE `security_users` INNER JOIN `z_2786_security_users` ON `z_2786_security_users`.`id` = `security_users`.`id`
SET `security_users`.`date_of_birth` = `z_2786_security_users`.`date_of_birth`, `security_users`.`gender_id` = `z_2786_security_users`.`gender_id`;

DROP TABLE IF EXISTS `z_2786_security_users`;

-- institution_students
UPDATE `institution_students`
INNER JOIN `z_2786_institution_students`
    ON `institution_students`.`id` = `z_2786_institution_students`.`id`
SET `institution_students`.`start_date` = `z_2786_institution_students`.`start_date`,
        `institution_students`.`end_date` = `z_2786_institution_students`.`end_date`,
        `institution_students`.`start_year` = `z_2786_institution_students`.`start_year`,
        `institution_students`.`end_year` = `z_2786_institution_students`.`end_year`,
        `institution_students`.`created` = `z_2786_institution_students`.`created`;

DROP TABLE IF EXISTS `z_2786_institution_students`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2786';


-- 3.5.2
UPDATE config_items SET value = '3.5.2' WHERE code = 'db_version';
