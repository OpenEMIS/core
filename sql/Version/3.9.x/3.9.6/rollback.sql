-- POCOR-3845
-- institution_student_absences
DROP TABLE IF EXISTS `institution_student_absences`;
RENAME TABLE `z_3845_institution_student_absences` TO `institution_student_absences`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3845';


-- POCOR-3846
-- config_product_lists
ALTER TABLE `config_product_lists`
ADD COLUMN `deletable` INT(1) NOT NULL DEFAULT 1 AFTER `url`;

UPDATE `config_product_lists`
INNER JOIN `z_3846_config_product_lists` ON `z_3846_config_product_lists`.`id` = `config_product_lists`.`id`
SET `config_product_lists`.`deletable` = `z_3846_config_product_lists`.`deletable`;

ALTER TABLE `config_product_lists`
CHANGE COLUMN `deletable` `deletable` INT(1) NOT NULL ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3846';


-- POCOR-2059
-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-2509';


-- POCOR-3680
-- security_group_users
DROP TABLE IF EXISTS `security_group_users`;
RENAME TABLE `z_3680_security_group_users` TO `security_group_users`;

DROP TABLE IF EXISTS `institution_staff`;
RENAME TABLE `z_3680_institution_staff` TO `institution_staff`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3680';


-- POCOR-3849
-- room_types
DROP TABLE IF EXISTS `room_types`;
RENAME TABLE `z_3849_room_types` TO `room_types`;

-- db_patches
DELETE FROM `system_patches` WHERE `issue`='POCOR-3849';


-- POCOR-3857
-- security_functions
DELETE FROM `config_items` WHERE `id` IN (126, 127);

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3857';


-- 3.9.5
DELETE FROM system_updates WHERE version = (SELECT value FROM config_items WHERE code = 'db_version');
UPDATE config_items SET value = '3.9.5' WHERE code = 'db_version';
