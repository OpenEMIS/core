-- db_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES('POCOR-3851', NOW());

ALTER TABLE `contact_types` ADD `validation_pattern` VARCHAR(100) NULL AFTER `name`;
