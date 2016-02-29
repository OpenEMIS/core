-- 
-- POCOR-1694
-- 

DROP TABLE IF EXISTS `institution_subjects`;
ALTER TABLE `z_1694_institution_classes` RENAME `institution_classes`;

DROP TABLE IF EXISTS `institution_subject_staff`;
ALTER TABLE `z_1694_institution_class_staff` RENAME `institution_class_staff`;

DROP TABLE IF EXISTS `institution_subject_students`;
ALTER TABLE `z_1694_institution_class_students` RENAME `institution_class_students`;

DROP TABLE IF EXISTS `institution_class_subjects`;
ALTER TABLE `z_1694_institution_section_classes` RENAME `institution_section_classes`;

DROP TABLE IF EXISTS `institution_classes`;
ALTER TABLE `z_1694_institution_sections` RENAME `institution_sections`;

DROP TABLE IF EXISTS `institution_class_grades`;
ALTER TABLE `z_1694_institution_section_grades` RENAME `institution_section_grades`;

DROP TABLE IF EXISTS `institution_class_students`;
ALTER TABLE `z_1694_institution_section_students` RENAME `institution_section_students`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-1694';
