-- POCOR-3457
-- assessment_grading_options
ALTER TABLE `assessment_grading_options` DROP `description`;

-- examination_grading_options
ALTER TABLE `examination_grading_options` DROP `description`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3457';


-- POCOR-3661
-- import_mapping
DELETE FROM `import_mapping`
WHERE `model` = 'Examination.ExaminationCentreRooms';

-- security_functions
DELETE FROM `security_functions` WHERE `security_functions`.`id` = 5057;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3661';


-- POCOR-3605
-- security_functions
DELETE FROM `security_functions` WHERE `id` = 5056;
UPDATE `security_functions` SET `order` = 5009 WHERE `id` = 5009;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3605';


-- POCOR-3539
-- translations
DELETE FROM `translations` WHERE `en` = 'Student has been transferred to';
DELETE FROM `translations` WHERE `en` = 'after registration';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3539';


-- POCOR-2944
-- update institution_quality_visits
ALTER TABLE `institution_quality_visits` CHANGE `institution_subject_id` `institution_subject_id` INT(11) NOT NULL COMMENT 'links to institution_subjects.id';
ALTER TABLE `institution_quality_visits` CHANGE `staff_id` `staff_id` INT(11) NOT NULL COMMENT 'links to security_users.id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-2944';


-- 3.8.1.1
UPDATE config_items SET value = '3.8.1.1' WHERE code = 'db_version';
