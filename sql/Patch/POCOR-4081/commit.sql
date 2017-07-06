-- system_patches
INSERT INTO `system_patches` (`issue`, `created`) VALUES ('POCOR-4081', NOW());

ALTER TABLE `user_identities`
ADD INDEX `number` (`number`);
