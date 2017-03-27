-- restore alert_logs
DROP TABLE IF EXISTS `alert_logs`;
RENAME TABLE `z_3726_alert_logs` TO `alert_logs`;


ALTER TABLE `workflows` DROP `message`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3726';

