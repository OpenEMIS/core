-- delete new table and temp

DROP TABLE IF EXISTS `institution_subject_students`;

-- db_rollback

ALTER TABLE `z_2784_institution_subject_students` 
RENAME TO  `institution_subject_students`;

-- db_patches

DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2784';