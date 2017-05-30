-- POCOR-2498
-- code here
DELETE FROM `security_functions` WHERE `id` = 1055;
DELETE FROM `security_functions` WHERE `id` = 2032;
DELETE FROM `security_functions` WHERE `id` = 5066;
ALTER TABLE `student_behaviours` DROP `academic_period_id`;
ALTER TABLE `student_behaviour_categories` DROP `behaviour_classification_id`;
DROP TABLE student_indexes_criterias;
DROP TABLE institution_student_indexes;
DROP TABLE behaviour_classifications;
DROP TABLE indexes_criterias;
DROP TABLE institution_indexes;
DROP TABLE indexes;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-2498';


-- POCOR-3644
-- security_functions
DELETE FROM `security_functions` WHERE `id` IN (2031, 7051);

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 2018 AND `order` < 3000;

UPDATE `security_functions`
SET `order` = `order` - 1
WHERE `order` >= 7019 AND `order` < 8000;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3644';


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
