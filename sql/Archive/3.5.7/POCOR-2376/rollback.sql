-- institution_student_admission
ALTER TABLE `institution_student_admission` 
DROP COLUMN `new_education_grade_id`,
DROP INDEX `new_education_grade_id` ;

-- labels
DELETE FROM `labels` WHERE `module` = 'TransferApprovals' AND `field` = 'new_education_grade_id';
DELETE FROM `labels` WHERE `module` = 'TransferRequests' AND `field` = 'new_education_grade_id';
DELETE FROM `labels` WHERE `module` = 'TransferApprovals' AND `field` = 'previous_institution_id';

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2376';