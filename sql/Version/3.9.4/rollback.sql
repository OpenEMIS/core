-- POCOR-3797
-- restore competency_criterias
DROP TABLE IF EXISTS `competency_criterias`;
RENAME TABLE `z_3797_competency_criterias` TO `competency_criterias`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3797';


-- POCOR-3772
-- security_group_areas
DROP TABLE IF EXISTS `security_group_areas`;
RENAME TABLE `z_3772_security_group_areas` TO `security_group_areas`;

-- security_group_institutions
DROP TABLE IF EXISTS `security_group_institutions`;
RENAME TABLE `z_3772_security_group_institutions` TO `security_group_institutions`;

-- security_group_users
DROP TABLE IF EXISTS `security_group_users`;
RENAME TABLE `z_3772_security_group_users` TO `security_group_users`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3772';


-- POCOR-3692
-- examination_item_results
DROP TABLE IF EXISTS `examination_item_results`;
RENAME TABLE `z_3692_examination_item_results` TO `examination_item_results`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3692';


-- POCOR-3717
-- config_item_options
UPDATE `config_item_options` SET `value` = 'dS F Y' WHERE `config_item_options`.`id` = 7;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3717';


-- 3.9.3
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.3' WHERE code = 'db_version';
