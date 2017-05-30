-- POCOR-3031
-- remove db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3031';


-- POCOR-2802
-- code here
UPDATE `security_functions` SET _view = 'Fees.index' WHERE id = 2019;


-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2802';


-- POCOR-2781
DROP TABLE institution_subject_staff;
RENAME TABLE z_2781_institution_subject_staff TO institution_subject_staff;

-- SELECT * FROM `security_functions` WHERE `name` LIKE '%subject%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff subjects 3014
UPDATE security_functions SET `_add` = NULL WHERE id = 3014;

-- SELECT * FROM `security_functions` WHERE `name` LIKE '%classes%' AND `category` LIKE 'Staff - Career' AND controller = 'Staff';
-- add for institution staff classes 3013
UPDATE security_functions SET `_add` = NULL WHERE id = 3013;

-- db_patches
DELETE FROM db_patches where `issue` = 'POCOR-2781';


-- POCOR-2997
-- delete new table
DROP TABLE `staff_training_needs`;

-- rename back the backup table
ALTER TABLE `z_2997_staff_training_needs`
RENAME TO  `staff_training_needs` ;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2997';


-- POCOR-2714
SET @fieldOptionOrder := 0;
SELECT field_options.order INTO @fieldOptionOrder FROM field_options WHERE code = 'Nationalities';
UPDATE field_options SET field_options.order = field_options.order-1 WHERE field_options.order >= @fieldOptionOrder;
DELETE FROM field_options WHERE code = 'Nationalities';

-- Fix: re-create the user_nationalities table with the correct columns instead of altering table as it might take a long time
-- ALTER TABLE `user_nationalities` CHANGE `nationality_id` `country_id` INT(11) NOT NULL;
DROP TABLE user_nationalities;
RENAME TABLE z_2714_user_nationalities TO user_nationalities;

DROP TABLE `nationalities`;

DROP TABLE `countries`;
RENAME TABLE z_2714_countries TO countries;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2714';


-- 3.5.5.2
UPDATE config_items SET value = '3.5.5.2' WHERE code = 'db_version';
