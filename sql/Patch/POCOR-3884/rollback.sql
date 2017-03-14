-- config_items
DELETE FROM `config_items` WHERE `id` = 1004;

-- webhooks
DROP TABLE `webhooks`;

-- webhook_events
DROP TABLE `webhook_events`;

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3884';
