-- replace deletd records into institution_subjects table
INSERT INTO `institution_subjects`
SELECT * FROM `z_3193_institution_subjects`;

-- remove backup table
DROP TABLE `z_3193_institution_subjects`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-3193';