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