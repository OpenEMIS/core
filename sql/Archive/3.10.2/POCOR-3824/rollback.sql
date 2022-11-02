-- field options
DROP TABLE IF EXISTS `training_need_competencies`;
DROP TABLE IF EXISTS `training_need_standards`;
DROP TABLE IF EXISTS `training_need_sub_standards`;

-- staff_training_needs
DROP TABLE IF EXISTS `staff_training_needs`;
RENAME TABLE `z_3824_staff_training_needs` TO `staff_training_needs`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3824';
