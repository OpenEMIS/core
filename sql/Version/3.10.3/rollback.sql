-- POCOR-4025
-- education_grades_subjects
ALTER TABLE `education_grades_subjects` CHANGE `hours_required` `hours_required` INT(5) NOT NULL;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-4025';


-- 3.10.2
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.10.2' WHERE code = 'db_version';
