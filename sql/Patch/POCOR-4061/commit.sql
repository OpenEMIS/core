-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-4061', NOW());

-- system_errors
ALTER TABLE `system_errors`
CHANGE COLUMN `code` `code` VARCHAR(10) NOT NULL ;
