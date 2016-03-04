-- institution_staff
ALTER TABLE `institution_staff` 
DROP COLUMN `security_group_user_id`,
DROP INDEX `security_group_user_id` ;

-- role_update_progresses
DROP TABLE `role_update_processes`;

-- security_group_users
DROP TABLE `security_group_users`;
ALTER TABLE `z_2612_security_group_users` 
RENAME TO  `security_group_users` ;

-- system_processes
DROP TABLE IF EXISTS `system_processes`;

-- db_patches
DELETE FROM `db_patches` WHERE `issue` = 'POCOR-2612';