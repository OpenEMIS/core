-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3714', NOW());

ALTER TABLE `system_errors` ADD `code` INT(5) NOT NULL AFTER `id`;
ALTER TABLE `system_errors` ADD `request_method` VARCHAR(10) NOT NULL AFTER `error_message`;
ALTER TABLE `system_errors` ADD `server_info` TEXT NOT NULL AFTER `stack_trace`;
