-- 
-- POCOR-1694
-- 

DROP TABLE IF EXISTS `institution_subjects`;
ALTER TABLE `z_1694_institution_classes` RENAME `institution_classes`;

DROP TABLE IF EXISTS `institution_subject_staff`;
ALTER TABLE `z_1694_institution_class_staff` RENAME `institution_class_staff`;

DROP TABLE IF EXISTS `institution_subject_students`;
ALTER TABLE `z_1694_institution_class_students` RENAME `institution_class_students`;

DROP TABLE IF EXISTS `institution_section_subjects`;
ALTER TABLE `z_1694_institution_section_classes` RENAME `institution_section_classes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1694';
