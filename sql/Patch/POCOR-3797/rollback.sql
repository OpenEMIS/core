-- restore competency_criterias
DROP TABLE IF EXISTS `competency_criterias`;
RENAME TABLE `z_3797_competency_criterias` TO `competency_criterias`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3797';
