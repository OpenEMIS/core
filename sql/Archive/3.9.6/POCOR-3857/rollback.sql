-- security_functions
DELETE FROM `config_items` WHERE `id` IN (126, 127);

-- system_patches
DELETE FROM `system_patches` WHERE `issue` = 'POCOR-3857';
