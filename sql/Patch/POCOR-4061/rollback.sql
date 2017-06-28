-- system_errors
ALTER TABLE `system_errors`
CHANGE COLUMN `code` `code` INT(5) NULL ;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-4061';
