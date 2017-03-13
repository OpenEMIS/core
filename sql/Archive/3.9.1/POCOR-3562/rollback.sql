-- restore assessment_item_results
DROP TABLE IF EXISTS `assessment_item_results`;
RENAME TABLE `z_3562_assessment_item_results` TO `assessment_item_results`;

-- restore institution_subject_students
DROP TABLE IF EXISTS `institution_subject_students`;
RENAME TABLE `z_3562_institution_subject_students` TO `institution_subject_students`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3562';
