-- POCOR-1968
--
DROP TABLE qualification_levels;
DROP TABLE qualification_specialisations;
DROP TABLE qualification_specialisation_subjects;

-- need to rollback the staff qualifications
DROP TABLE staff_qualifications;
RENAME TABLE z1968_staff_qualifications TO staff_qualifications;

UPDATE field_options SET params = NULL WHERE code = 'QualificationSpecialisations';
UPDATE field_options SET params = NULL WHERE code = 'QualificationLevels';

SET @fieldOptionId := 0;
SELECT `id` INTO @fieldOptionId FROM field_options WHERE code = 'QualificationSpecialisations';
UPDATE field_option_values SET visible = 1 WHERE field_option_id = @fieldOptionId;

SET @fieldOptionId := 0;
SELECT `id` INTO @fieldOptionId FROM field_options WHERE code = 'QualificationLevels';
UPDATE field_option_values SET visible = 1 WHERE field_option_id = @fieldOptionId;

DELETE FROM `db_patches` WHERE `issue`='POCOR-1968';


-- POCOR-2232
--
ALTER TABLE `translations` DROP `editable`;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2232';


-- POCOR-2491
--
UPDATE `security_functions` SET `_edit` = 'Sessions.edit' WHERE `security_functions`.`id` = 5040;

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2491';

-- institution_section_students
UPDATE `institution_section_students`
INNER JOIN `z_2564_institution_section_students` ON `z_2564_institution_section_students`.`id` = `institution_section_students`.`id`
SET `institution_section_students`.`student_status_id` = `z_2564_institution_section_students`.`student_status_id`;


-- POCOR-2564
-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2564';

-- Restore table
DROP TABLE `institution_infrastructures`;
RENAME TABLE `z_2571_institution_infrastructures` TO `institution_infrastructures`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2571';


-- 3.4.13
--
UPDATE config_items SET value = '3.4.13' WHERE code = 'db_version';
