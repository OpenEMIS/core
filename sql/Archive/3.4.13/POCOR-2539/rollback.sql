-- staff_position_titles
ALTER TABLE `staff_position_titles` 
DROP COLUMN `security_role_id`,
DROP INDEX `security_role_id` ;

-- db_patches
DELETE `db_patches` WHERE `issue` = 'POCOR-2539';