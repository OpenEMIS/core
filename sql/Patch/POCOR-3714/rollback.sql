ALTER TABLE `system_errors` DROP `code`;
ALTER TABLE `system_errors` DROP `request_method`;
ALTER TABLE `system_errors` DROP `server_info`;

DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3714';
