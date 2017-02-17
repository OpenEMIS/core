ALTER TABLE `alert_logs` DROP `feature`;

ALTER TABLE `workflows` DROP `message`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3726';

