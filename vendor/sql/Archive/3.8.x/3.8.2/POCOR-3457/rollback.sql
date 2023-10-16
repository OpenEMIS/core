-- assessment_grading_options
ALTER TABLE `assessment_grading_options` DROP `description`;

-- examination_grading_options
ALTER TABLE `examination_grading_options` DROP `description`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue`='POCOR-3457';
