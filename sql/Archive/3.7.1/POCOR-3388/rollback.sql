-- institution_students
ALTER TABLE `institution_students` DROP `previous_institution_student_id`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3388';