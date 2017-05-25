-- competency
DROP TABLE IF EXISTS `training_need_competencies`;
DROP TABLE IF EXISTS `training_need_standards`;
DROP TABLE IF EXISTS `training_need_sub_standards`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3824';
