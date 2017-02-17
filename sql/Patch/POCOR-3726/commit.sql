-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-3726', NOW());


ALTER TABLE `alert_logs` ADD `feature` VARCHAR(100) NOT NULL AFTER `id`;

ALTER TABLE `workflows` ADD `message` TEXT NULL AFTER `name`;

